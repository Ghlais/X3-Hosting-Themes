#!/usr/bin/env bash
set -euo pipefail

source_dir=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
app_dir=/opt/x3-network-monitor
config_dir=/etc/x3-network-monitor
unit=/etc/systemd/system/x3-network-monitor.service

if (( EUID != 0 )); then
    printf 'Run with sudo on the Ubuntu node.\n' >&2
    exit 1
fi

for utility in python3 systemctl systemd-analyze install flock; do
    command -v "$utility" >/dev/null
done

exec 9>/run/lock/x3-network-monitor-install.lock
flock -n 9
if [[ -e "$unit" || -L "$unit" || -e "$app_dir" || -L "$app_dir" || -e "$config_dir" || -L "$config_dir" ]]; then
    printf 'An existing installation or staging directory was found. Review it before reinstalling.\n' >&2
    exit 1
fi

PYTHONDONTWRITEBYTECODE=1 python3 - "$source_dir" <<'PY'
import socket
import sys
from pathlib import Path
sys.path.insert(0, sys.argv[1])
from monitor import load_settings, read_node
settings = load_settings(Path(sys.argv[1]) / "config.json", False)
if read_node(settings["interface"])["rx_bytes"] is None:
    raise SystemExit("Cannot read the configured Linux network interface.")
with socket.socket() as listener:
    listener.bind(("127.0.0.1", 8787))
PY

rollback() {
    local status=$?
    if (( status == 0 )); then
        return
    fi
    set +e
    printf 'Installation failed. Removing its newly staged files.\n' >&2
    if [[ -f "$unit" ]]; then
        systemctl disable --now x3-network-monitor.service >/dev/null 2>&1
        rm -f -- "$unit"
        systemctl daemon-reload
    fi
    rm -f -- "$app_dir/monitor.py" "$app_dir/web/index.html" "$app_dir/web/app.css" "$app_dir/web/app.js"
    rm -f -- "$config_dir/config.json" "$config_dir/dashboard-password"
    rmdir -- "$app_dir/web" "$app_dir" "$config_dir" 2>/dev/null
    return "$status"
}
trap rollback EXIT

install -d -m 0755 "$app_dir" "$app_dir/web" "$config_dir"
install -m 0644 "$source_dir/monitor.py" "$app_dir/monitor.py"
for asset in index.html app.css app.js; do
    install -m 0644 "$source_dir/web/$asset" "$app_dir/web/$asset"
done
install -m 0644 "$source_dir/config.json" "$config_dir/config.json"
(
    umask 077
    python3 -c 'import secrets; print(secrets.token_urlsafe(36))' > "$config_dir/dashboard-password"
)
install -m 0644 "$source_dir/x3-network-monitor.service" "$unit"
systemd-analyze verify "$unit"
systemctl daemon-reload
systemctl enable --now x3-network-monitor.service
systemctl is-active x3-network-monitor.service
python3 - "$config_dir/dashboard-password" <<'PY'
import base64
import json
import sys
import time
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen

password = Path(sys.argv[1]).read_text().strip()
auth = base64.b64encode(f"operator:{password}".encode()).decode()
url = "http://127.0.0.1:8787/api/status"
for _ in range(15):
    try:
        try:
            with urlopen(url, timeout=2):
                raise SystemExit("Unauthenticated monitoring API access was accepted.")
        except HTTPError as denied:
            if denied.code != 401:
                raise
        with urlopen(Request(url, headers={"Authorization": "Basic " + auth}), timeout=2) as response:
            snapshot = json.load(response)
        if snapshot["mode"] == "live" and not snapshot["stale"] and snapshot["latest"]["rx_mbps"] is not None:
            print("Authenticated live sample verified.")
            break
    except (OSError, URLError, ValueError, KeyError):
        pass
    time.sleep(1)
else:
    raise SystemExit("The monitor did not produce an authenticated live sample.")
PY
printf 'Open http://127.0.0.1:8787 through SSH local port forwarding. User: operator.\n'
