import argparse
import hashlib
import os
from pathlib import Path
import re
import stat
import subprocess
import tempfile
from urllib.parse import urlsplit

parser = argparse.ArgumentParser(description='Install the Discord widget in X3 Rackline Studio')
parser.add_argument('--root', type=Path, default=Path('/var/www/paymenter'))
mode = parser.add_mutually_exclusive_group(required=True)
mode.add_argument('--check', action='store_true')
mode.add_argument('--apply', action='store_true')
mode.add_argument('--rollback', type=Path)
parser.add_argument('--guild-id')
parser.add_argument('--invite')
args = parser.parse_args()

root = args.root.resolve(strict=True)
theme = root / 'themes/x3-hosting'
view = theme / 'views/home.blade.php'
if not (root / 'artisan').is_file() or not view.is_file() or view.resolve() != view:
    parser.error('X3 Studio home view was not found in the supplied Paymenter root')
original = view.read_bytes()
anchor = b"    {!! hook('pages.home') !!}"
if args.rollback:
    backup = args.rollback.resolve(strict=True)
    if backup.parent != (root / 'storage/app/x3-discord-backups').resolve():
        parser.error('Use a backup created by this installer')
    replacement = backup.read_bytes()
    expected = backup.with_suffix('.sha256').read_text(encoding='ascii').strip()
    if hashlib.sha256(original).hexdigest() != expected:
        parser.error('The home view changed after installation. Review the backup and merge manually.')
else:
    guild = args.guild_id or ''
    invite = args.invite or ''
    url = urlsplit(invite)
    if not re.fullmatch(r'[0-9]{17,20}', guild):
        parser.error('Supply the numeric Discord server ID with --guild-id')
    if (not re.fullmatch(r'https://[A-Za-z0-9.-]+(?:/[A-Za-z0-9_./-]*)?', invite)
            or not url.hostname or '.' not in url.hostname):
        parser.error('Supply an HTTPS Discord invite with --invite')
    if b'x3-studio-home' not in original or original.count(anchor) != 1 or b'id="x3-discord"' in original:
        parser.error('Expected an unmodified X3 Studio home view without an existing widget')
    template = Path(__file__).with_name('section.blade.php').read_bytes()
    section = template.replace(b'GUILD_ID_PLACEHOLDER', guild.encode()).replace(b'INVITE_PLACEHOLDER', invite.encode())
    replacement = original.replace(anchor, section.rstrip() + b'\n' + anchor, 1)
if args.check:
    print('Preflight passed. Use --apply with the same arguments.')
    raise SystemExit(0)

def replace(content):
    info = view.stat()
    fd, filename = tempfile.mkstemp(prefix='.x3-discord-', dir=view.parent)
    temp = Path(filename)
    try:
        with os.fdopen(fd, 'wb') as handle:
            handle.write(content)
        os.chmod(temp, stat.S_IMODE(info.st_mode))
        if hasattr(os, 'geteuid') and os.geteuid() == 0:
            os.chown(temp, info.st_uid, info.st_gid)
        temp.replace(view)
    finally:
        temp.unlink(missing_ok=True)

if not args.rollback:
    base = root / 'storage/app/x3-discord-backups'
    base.mkdir(mode=0o700, exist_ok=True)
    fd, filename = tempfile.mkstemp(prefix='home-', suffix='.blade.php', dir=base)
    backup = Path(filename)
    with os.fdopen(fd, 'wb') as handle:
        handle.write(original)
    with backup.with_suffix('.sha256').open('x', encoding='ascii') as checksum:
        checksum.write(hashlib.sha256(replacement).hexdigest() + '\n')
if view.read_bytes() != original:
    raise SystemExit('Home view changed during the operation')
replace(replacement)
try:
    subprocess.run(['php', 'artisan', 'view:clear'], cwd=root, check=True)
except (OSError, subprocess.CalledProcessError):
    if view.read_bytes() == replacement:
        replace(original)
    raise
if args.rollback:
    print('Restored the previous home view.')
else:
    print(f'Installed. Backup: {backup}')
