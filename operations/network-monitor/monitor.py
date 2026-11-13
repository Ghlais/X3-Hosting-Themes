#!/usr/bin/env python3
"""Read-only network and host monitoring for a single Linux node."""

import argparse
import base64
import hmac
import json
import logging
import math
import re
import sqlite3
import threading
import time
from contextlib import closing
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from urllib.parse import parse_qs, urlsplit


ASSETS = Path(__file__).resolve().parent / "web"
ROUTES = {
    "/": ("index.html", "text/html; charset=utf-8"),
    "/app.css": ("app.css", "text/css; charset=utf-8"),
    "/app.js": ("app.js", "text/javascript; charset=utf-8"),
}
COUNTERS = ("rx_bytes", "tx_bytes", "rx_packets", "tx_packets", "rx_dropped", "rx_errors")
METRICS = ("rx_mbps", "tx_mbps", "rx_pps", "tx_pps", "rx_drop_ps", "rx_error_ps", "udp_buffer_ps",
           "tcp_drop_ps", "conntrack_pct", "cpu_pct", "memory_pct")
WINDOWS = {15, 60, 360, 1440}


def read_int(path):
    try:
        return int(path.read_text().strip())
    except (OSError, ValueError):
        return None


def read_table(path, group):
    try:
        lines = path.read_text().splitlines()
    except OSError:
        return {}
    for header, values in zip(lines, lines[1:]):
        if header.startswith(group + ":") and values.startswith(group + ":"):
            names, numbers = header.split()[1:], values.split()[1:]
            try:
                return dict(zip(names, map(int, numbers), strict=True))
            except ValueError:
                return {}
    return {}


def read_node(interface, proc=Path("/proc"), sysfs=Path("/sys")):
    statistics = sysfs / "class" / "net" / interface / "statistics"
    result = {name: read_int(statistics / name) for name in COUNTERS}
    result["udp_buffer"] = read_table(proc / "net/snmp", "Udp").get("RcvbufErrors")
    result["tcp_drop"] = read_table(proc / "net/netstat", "TcpExt").get("ListenDrops")
    conntrack = proc / "sys/net/netfilter"
    result["conntrack"] = read_int(conntrack / "nf_conntrack_count")
    result["conntrack_max"] = read_int(conntrack / "nf_conntrack_max")
    try:
        cpu = [int(item) for item in (proc / "stat").read_text().splitlines()[0].split()[1:9]]
        result["cpu_total"] = sum(cpu)
        result["cpu_idle"] = cpu[3] + cpu[4]
    except (OSError, ValueError, IndexError):
        result["cpu_total"] = result["cpu_idle"] = None
    try:
        memory = {}
        for line in (proc / "meminfo").read_text().splitlines():
            key, value = line.split(":", 1)
            if key in {"MemTotal", "MemAvailable"}:
                memory[key] = int(value.split()[0])
        result["memory_pct"] = round(100 * (1 - memory["MemAvailable"] / memory["MemTotal"]), 2)
    except (OSError, KeyError, ValueError, IndexError, ZeroDivisionError):
        result["memory_pct"] = None
    return result


def delta(previous, current, key):
    a, b = previous.get(key), current.get(key)
    return b - a if a is not None and b is not None and b >= a else None


def rates(previous, current, seconds):
    mapping = {
        "rx_mbps": ("rx_bytes", 8 / 1_000_000), "tx_mbps": ("tx_bytes", 8 / 1_000_000),
        "rx_pps": ("rx_packets", 1), "tx_pps": ("tx_packets", 1),
        "rx_drop_ps": ("rx_dropped", 1), "rx_error_ps": ("rx_errors", 1),
        "udp_buffer_ps": ("udp_buffer", 1), "tcp_drop_ps": ("tcp_drop", 1),
    }
    values = {}
    for field, (counter, factor) in mapping.items():
        change = delta(previous, current, counter)
        values[field] = round(change * factor / seconds, 2) if change is not None and seconds > 0 else None
    total = delta(previous, current, "cpu_total")
    idle = delta(previous, current, "cpu_idle")
    values["cpu_pct"] = round(100 * (total - idle) / total, 2) if total and idle is not None and idle <= total else None
    count, limit = current.get("conntrack"), current.get("conntrack_max")
    values["conntrack_pct"] = round(100 * count / limit, 2) if count is not None and limit else None
    values["memory_pct"] = current.get("memory_pct")
    return values


def demo_sample(start, elapsed):
    wave = (1 + math.sin(elapsed / 7)) / 2
    pulse = max(0, 1 - abs(elapsed % 110 - 77) / 13)
    result = {name: 0 for name in METRICS}
    result.update(
        ts=start + elapsed, rx_mbps=round(17 + 16 * wave + 70 * pulse, 2),
        tx_mbps=round(7 + 10 * wave, 2), rx_pps=round(1600 + 2700 * wave + 5600 * pulse, 2),
        tx_pps=round(900 + 2300 * wave, 2), rx_drop_ps=round(31 * pulse, 2),
        udp_buffer_ps=round(8 * pulse, 2), conntrack_pct=round(29 + 12 * wave + 33 * pulse, 2),
        cpu_pct=round(13 + 24 * wave + 15 * pulse, 2), memory_pct=round(46 + 2 * wave, 2),
    )
    return result


class Monitor:
    def __init__(self, settings, database, demo):
        self.settings = settings
        self.database = database
        self.demo = demo
        self.stop = threading.Event()
        with closing(self.connect()) as connection, connection:
            connection.execute("PRAGMA journal_mode=WAL")
            columns = ", ".join(f"{name} REAL" for name in METRICS)
            connection.execute(f"CREATE TABLE IF NOT EXISTS samples (ts REAL PRIMARY KEY, {columns})")

    def connect(self):
        connection = sqlite3.connect(self.database, timeout=4)
        connection.row_factory = sqlite3.Row
        return connection

    def record(self, samples, purge_before=None):
        with closing(self.connect()) as connection, connection:
            connection.executemany(
                f"INSERT OR REPLACE INTO samples VALUES ({', '.join('?' for _ in range(1 + len(METRICS)))})",
                ((sample["ts"], *(sample.get(key) for key in METRICS)) for sample in samples),
            )
            if purge_before is not None:
                connection.execute("DELETE FROM samples WHERE ts < ?", (purge_before,))

    def collect(self):
        interface, interval = self.settings["interface"], self.settings["interval_seconds"]
        start = time.time() - 1800
        if self.demo:
            self.record(demo_sample(start, t) for t in range(0, 1801, interval))
        previous = {} if self.demo else read_node(interface)
        before = time.monotonic()
        while not self.stop.wait(interval):
            now, clock = time.time(), time.monotonic()
            if self.demo:
                sample = demo_sample(start, now - start)
            else:
                current = read_node(interface)
                sample = {"ts": now, **rates(previous, current, clock - before)}
                previous, before = current, clock
            try:
                self.record([sample], purge_before=now - 86400)
            except sqlite3.Error:
                logging.exception("Unable to save a monitoring sample")

    def status(self, minutes):
        now = time.time()
        bucket = max(self.settings["interval_seconds"], math.ceil(minutes * 60 / 240))
        values = ", ".join(f"MAX({name}) AS {name}" for name in METRICS)
        with closing(self.connect()) as connection:
            last = connection.execute(
                "SELECT * FROM samples WHERE ts <= ? ORDER BY ts DESC LIMIT 1", (now,)
            ).fetchone()
            timeline = connection.execute(
                f"SELECT MAX(ts) AS ts, {values} FROM samples WHERE ts >= ? AND ts <= ? "
                "GROUP BY CAST(ts / ? AS INTEGER) ORDER BY ts",
                (now - minutes * 60, now, bucket),
            ).fetchall()
        latest = dict(last) if last is not None else None
        stale = latest is None or abs(now - latest["ts"]) > self.settings["interval_seconds"] * 3
        alerts = []
        if stale:
            alerts.append("stale")
        elif latest["rx_mbps"] is None:
            alerts.append("interface_missing")
        else:
            for field, limit in self.settings["thresholds"].items():
                if latest.get(field) is not None and latest[field] >= limit:
                    alerts.append(field)
        return {
            "mode": "demo" if self.demo else "live",
            "node": self.settings["node"],
            "interface": self.settings["interface"],
            "latest": latest,
            "history": [dict(row) for row in timeline],
            "alerts": alerts,
            "stale": stale,
            "interval": self.settings["interval_seconds"],
        }


def load_settings(path, demo):
    settings = json.loads(path.read_text(encoding="utf-8"))
    if not isinstance(settings, dict):
        raise ValueError("config must be an object")
    settings.setdefault("node", "Contabo node")
    settings.setdefault("interface", "eth0")
    settings.setdefault("interval_seconds", 5)
    settings.setdefault("thresholds", {})
    if not isinstance(settings["node"], str) or not 1 <= len(settings["node"]) <= 100:
        raise ValueError("invalid node label")
    interface = settings["interface"]
    if not isinstance(interface, str) or not re.fullmatch(r"[A-Za-z0-9_.:-]{1,15}", interface) or interface in {".", ".."}:
        raise ValueError("invalid interface name")
    if type(settings["interval_seconds"]) is not int or not 3 <= settings["interval_seconds"] <= 60:
        raise ValueError("interval_seconds must be an integer from 3 to 60")
    thresholds = settings["thresholds"]
    if not isinstance(thresholds, dict) or any(
        name not in METRICS or type(value) not in {int, float} or not math.isfinite(value) or value <= 0
        for name, value in thresholds.items()
    ):
        raise ValueError("invalid thresholds")
    if demo:
        settings["node"] = "X3 · Demo"
        settings["thresholds"].setdefault("rx_mbps", 70)
    return settings


class RequestHandler(BaseHTTPRequestHandler):
    def setup(self):
        self.request.settimeout(8)
        super().setup()

    def reply(self, status, body, content_type, authenticate=False):
        self.send_response(status)
        self.send_header("Content-Type", content_type)
        self.send_header("Content-Length", str(len(body)))
        self.send_header("Cache-Control", "no-store")
        self.send_header("X-Content-Type-Options", "nosniff")
        self.send_header("Referrer-Policy", "no-referrer")
        self.send_header("X-Frame-Options", "DENY")
        self.send_header("Content-Security-Policy",
                         "default-src 'none'; style-src 'self'; script-src 'self'; connect-src 'self'; frame-ancestors 'none'")
        if authenticate:
            self.send_header("WWW-Authenticate", 'Basic realm="X3 Network Monitor", charset="UTF-8"')
        self.end_headers()
        if self.command != "HEAD":
            self.wfile.write(body)

    def do_HEAD(self):
        self.do_GET()

    def do_GET(self):
        port = self.server.server_port
        if self.headers.get("Host") not in {f"127.0.0.1:{port}", f"localhost:{port}"}:
            self.reply(400, b"Invalid host", "text/plain")
            return
        expected = self.server.authorization
        if expected is not None and not hmac.compare_digest(
            self.headers.get("Authorization", "").encode("utf-8"), expected
        ):
            self.reply(401, b"Authentication required", "text/plain", authenticate=True)
            return
        try:
            route = urlsplit(self.path)
        except ValueError:
            self.reply(400, b"Invalid URL", "text/plain")
            return
        if route.path in ROUTES:
            name, media_type = ROUTES[route.path]
            self.reply(200, (ASSETS / name).read_bytes(), media_type)
            return
        if route.path == "/api/status":
            try:
                window = int(parse_qs(route.query, max_num_fields=4).get("minutes", ["60"])[0])
                if window not in WINDOWS:
                    raise ValueError("Invalid period")
            except (ValueError, IndexError):
                self.reply(400, b"Invalid period", "text/plain")
                return
            try:
                data = self.server.monitor.status(window)
            except sqlite3.Error:
                self.reply(503, b"Monitoring data temporarily unavailable", "text/plain")
                return
            self.reply(200, json.dumps(data, ensure_ascii=False, allow_nan=False).encode(), "application/json")
            return
        self.reply(404, b"Not found", "text/plain")

    def log_message(self, _format, *_args):
        pass


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--config", type=Path, required=True)
    parser.add_argument("--state", type=Path, required=True)
    parser.add_argument("--password-file", type=Path)
    parser.add_argument("--port", type=int, default=8787)
    parser.add_argument("--demo", action="store_true")
    args = parser.parse_args()
    if not 1024 <= args.port <= 65535:
        parser.error("port must be in 1024..65535")
    if not args.demo and args.password_file is None:
        parser.error("--password-file is required in live mode")
    settings = load_settings(args.config, args.demo)
    if not args.demo and read_node(settings["interface"])["rx_bytes"] is None:
        parser.error("Cannot read the selected Linux interface")
    authorization = None
    if args.password_file:
        password = args.password_file.read_text().strip()
        if not re.fullmatch(r"[A-Za-z0-9_-]{24,128}", password):
            parser.error("Use a random password of at least 24 URL-safe characters")
        authorization = b"Basic " + base64.b64encode(f"operator:{password}".encode())
    args.state.mkdir(parents=True, exist_ok=True)
    database = args.state / ("demo.sqlite3" if args.demo else f"{settings['interface']}.sqlite3")
    monitor = Monitor(settings, database, args.demo)
    with ThreadingHTTPServer(("127.0.0.1", args.port), RequestHandler) as server:
        server.monitor = monitor
        server.authorization = authorization
        worker = threading.Thread(target=monitor.collect, daemon=True)
        worker.start()
        print(f"X3 Network Monitor · {'demo' if args.demo else 'live'} · http://127.0.0.1:{args.port}", flush=True)
        try:
            server.serve_forever()
        except KeyboardInterrupt:
            pass
        finally:
            monitor.stop.set()
            worker.join(timeout=5)


if __name__ == "__main__":
    main()
