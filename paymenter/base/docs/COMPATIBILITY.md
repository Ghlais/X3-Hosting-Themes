# Compatibility and boundaries

This release targets the installed Paymenter 1.5.9 contract, checked against:

- https://github.com/Paymenter/Paymenter/tree/v1.5.9
- https://github.com/Paymenter/Paymenter/blob/v1.5.9/themes/default/theme.php
- https://github.com/Paymenter/Paymenter/blob/v1.5.9/app/Classes/FilamentInput.php
- https://github.com/Paymenter/Paymenter/blob/v1.5.9/app/Models/Plan.php
- https://github.com/Paymenter/Paymenter/blob/v1.5.9/app/Models/Traits/HasPlans.php

The source tag resolves to `ee729f7d99ac18942625b79f3f93d8d8b21255e8`. Native theme views, hooks, Livewire actions, routes, session currency, price objects and authorization are retained. 521 files under app/routes/config matched the source snapshot during final verification; none were modified for this theme.

The following belong to the installation, not to a theme: PHP-FPM configuration, storage write permissions, real gateway credentials/webhooks, external extension behavior, existing product pricing/currency data, mail and queues. Changing theme files does not repair an independent Pterodactyl provisioning error such as a missing RCON variable. A successful CLI view lookup alone does not prove an HTTP page works; the checker also verifies the exact X3 home path, and browser/feature tests cover rendering.

No remote server, real customer data, live gateway transaction, live provisioning or production upgrade was accessed in this work. Use the bundled preflight and acceptance checks on your deployment. Versions other than 1.5.9 require a separate compatibility review; the installer rejects them.

Font: local Noto Sans Arabic from the official Google Fonts repository, SIL Open Font License 1.1, included in `themes/x3-hosting/fonts/OFL.txt`. Runtime font requests remain on your own domain. Paymenter MIT attribution is included under licenses.
