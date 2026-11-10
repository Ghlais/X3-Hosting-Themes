# X3 Hosting — Rackline 1.1.0

Target: Paymenter **1.5.9**, PHP **8.3+**. Theme-only upgrade retaining the `x3-hosting` slug. Prebuilt assets and a local licensed Arabic font are included. No Composer/npm commands are needed for installation.

Extract the ZIP **outside the theme and public directories**, such as `/tmp/x3-release-1.1.0`. Enter its `X3-Hosting-Paymenter-Rackline-v1.1.0` folder and run:

```bash
php install.php --root=/var/www/paymenter
php install.php --root=/var/www/paymenter --apply
php tools/check.php --root=/var/www/paymenter
```

The first command performs preflight only. Apply validates checksums/version, stages both payload directories, backs up the previous directories under `storage/app/x3-theme-backups`, installs them and clears compiled views. It restores the previous directories if clearing views fails. It does not write database settings, `.env`, default theme, core, routes, vendor or migrations. Select the theme in administration on a first install. Existing X3 settings remain stored.

Configure footer links/button, Discord/support, product IDs, public specifications, category art and five bilingual FAQ entries in the native theme settings screen. `docs/SETTINGS.md` contains examples. Display metadata does not modify provisioning resources. Hidden products remain excluded. No demo products, accounts or gateways are imported.

Rollback using the exact backup path printed by the installer:

```bash
php install.php --root=/var/www/paymenter --rollback=/absolute/printed/backup
php install.php --root=/var/www/paymenter --rollback=/absolute/printed/backup --apply
```

Rollback preserves the replaced version. If no X3 theme existed before the first install, switch to `default` before rollback. Settings are independent of file rollback. Full Arabic installation/recovery instructions are in `README-AR.md`.

Validation: see `docs/VALIDATION.md`. Production PHP-FPM, provider/gateway APIs, real payments and provisioning were not accessed. These require acceptance on the user's own deployment.
