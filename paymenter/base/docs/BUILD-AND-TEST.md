# Source rebuild and tests

Installation of the shipped release does not require a build. For source edits, use a separate Paymenter v1.5.9 staging checkout with its normal PHP and Node dependencies. Place only the theme source under `themes/x3-hosting` and run the native root build:

```bash
node vite.js x3-hosting
```

Deploy the rebuilt `public/x3-hosting` together with the matching theme source. `vite.config.js` uses the isolated `public/x3-hosting.hot`, avoiding interference from the default theme dev server. Remove your own theme hot file when ending development; it is not shipped here.

For PHP tests, use an isolated database. Copy the three `tests/Feature/X3*.php` files into that staging checkout's `tests/Feature`; `X3CheckoutTest` extends its native `CheckoutTest`. Configure SQLite `:memory:`, array cache/mail, and a local app key as usual. **RefreshDatabase clears a test database; never run these tests on the production database.** Then:

```bash
php vendor/bin/phpunit --filter X3
node --test /path/to/release/tests/comparison.test.mjs
```

The installer/check tools are CLI-only. Keep the extracted release outside `public/`. Installer preflight requires the deployed `config/app.php` to identify 1.5.9. An official source checkout may identify itself as `development`; do not change production version metadata to bypass this check.

Regression paths: empty catalog; hidden/missing product IDs; no specs; malformed metadata; unavailable currency; free/one-time/recurring plans; setup fee; changed billing cycle; cart quantity; one-time coupon; pending/paid/partially paid invoices; account ownership; future and overdue renewals; disabled tickets; both languages; Escape/close of comparison; mobile navigation; light/dark/system; reduced motion.
