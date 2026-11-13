import base64
import importlib.util
import json
import threading
import time
from http.client import HTTPConnection
from pathlib import Path
from tempfile import TemporaryDirectory


root = Path(__file__).resolve().parent
spec = importlib.util.spec_from_file_location("x3_monitor", root / "monitor.py")
monitor = importlib.util.module_from_spec(spec)
spec.loader.exec_module(monitor)


with TemporaryDirectory(prefix="x3-monitor-test-") as folder:
    temp = Path(folder)
    proc, sysfs = temp / "proc", temp / "sys"
    stats = sysfs / "class/net/eth0/statistics"
    stats.mkdir(parents=True)
    (proc / "net").mkdir(parents=True)
    (proc / "sys/net/netfilter").mkdir(parents=True)
    (proc / "net/snmp").write_text("Udp: InDatagrams RcvbufErrors\nUdp: 100 12\n")
    (proc / "net/netstat").write_text("TcpExt: ListenDrops ListenOverflows\nTcpExt: 5 4\n")
    (proc / "sys/net/netfilter/nf_conntrack_count").write_text("80")
    (proc / "sys/net/netfilter/nf_conntrack_max").write_text("100")
    (proc / "stat").write_text("cpu 100 0 100 800 0 0 0 0 0 0\n")
    (proc / "meminfo").write_text("MemTotal: 10000 kB\nMemAvailable: 6500 kB\n")
    for name in monitor.COUNTERS:
        (stats / name).write_text("100")
    before = monitor.read_node("eth0", proc, sysfs)
    (stats / "rx_bytes").write_text("5000100")
    (stats / "rx_packets").write_text("5100")
    (stats / "rx_dropped").write_text("20")
    (proc / "stat").write_text("cpu 120 0 110 870 0 0 0 0 0 0\n")
    after = monitor.read_node("eth0", proc, sysfs)
    sample = monitor.rates(before, after, 5)
    assert sample["rx_mbps"] == 8 and sample["rx_pps"] == 1000
    assert sample["rx_drop_ps"] is None and sample["cpu_pct"] == 30
    assert sample["conntrack_pct"] == 80 and sample["memory_pct"] == 35
    assert after["tcp_drop"] == 5 and after["udp_buffer"] == 12
    (proc / "sys/net/netfilter/nf_conntrack_max").unlink()
    missing = monitor.read_node("eth0", proc, sysfs)
    assert monitor.rates(before, missing, 5)["conntrack_pct"] is None
    print("PASS Linux counter fixtures, deltas, resets and missing metrics")

    settings = monitor.load_settings(root / "config.json", False)
    node = monitor.Monitor(settings, temp / "test.sqlite", False)
    assert node.status(15)["stale"]
    now = time.time()
    node.record([{"ts": now - 90000, **sample}, {"ts": now - 2, **sample}], purge_before=now - 86400)
    output = node.status(60)
    assert len(output["history"]) == 1 and output["alerts"] == ["conntrack_pct"]
    assert output["mode"] == "live" and output["latest"]["rx_mbps"] == 8
    print("PASS retention, thresholds, stale state and live data separation")

    auth = "Basic " + base64.b64encode(b"operator:fixture-only").decode()
    with monitor.ThreadingHTTPServer(("127.0.0.1", 0), monitor.RequestHandler) as server:
        server.monitor = node
        server.authorization = auth.encode()
        thread = threading.Thread(target=server.serve_forever, daemon=True)
        thread.start()
        try:
            def request(path, headers=None, method="GET"):
                client = HTTPConnection("127.0.0.1", server.server_port, timeout=4)
                try:
                    client.request(method, path, headers=headers or {})
                    response = client.getresponse()
                    return response.status, response.read(), dict(response.getheaders())
                finally:
                    client.close()

            assert request("/")[0] == 401
            assert request("/api/status")[0] == 401
            assert request("/app.js")[0] == 401
            credentials = {"Authorization": auth}
            assert request("/", {"Authorization": "Basic invalid"})[0] == 401
            assert request("/", {"Authorization": auth, "Host": "unrelated.example"})[0] == 400
            assert request("/api/status?minutes=1", credentials)[0] == 400
            assert request("/../../monitor.py", credentials)[0] == 404
            assert request("/api/status", credentials, "POST")[0] == 501
            assert request("/", credentials, "HEAD")[1] == b""
            status, data, headers = request("/api/status?minutes=1440", credentials)
            assert status == 200 and json.loads(data)["mode"] == "live"
            assert headers["Cache-Control"] == "no-store" and "frame-ancestors 'none'" in headers["Content-Security-Policy"]
            for asset in ("/", "/app.css", "/app.js"):
                assert request(asset, credentials)[0] == 200
            print("PASS authenticated access, DNS rebinding guard, read-only routes and response headers")
        finally:
            server.shutdown()
            thread.join()
