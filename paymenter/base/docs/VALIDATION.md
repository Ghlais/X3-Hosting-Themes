# Verification — 2026-10-25

- Paymenter v1.5.9 local source, PHP 8.3.35: **24 tests, 269 assertions, zero failures/errors**.
- Repeated on PHP 8.4.13: 24 tests, 269 assertions, zero failures/errors.
- JavaScript selection/state tests: 3 passed.
- Installer tests: 9 scenarios passed, including preflight, exact hashes, no nesting, rollback, repeat rollback refusal, automatic recovery on cache-clear failure, version rejection and corrupt payload rejection. Fixture roots only; not the user's VPS.
- Vite 8.2.1 production build passed; CSS, JS, lazy editor and local font references resolved.
- 98 Blade views compiled and token parsed; Arabic and English custom translation keys match.
- Native admin field conversion, hidden product handling, malformed metadata, escaped texts/unsafe URLs, native prices/setup/currencies, live plan updates, first-period coupon renewal, pending invoice balances, ownership and service due-date integrity were exercised.
- Browser: real local Paymenter, Arabic/English switching updates direction, comparison opens/closes and returns focus. Visual previews at iframe widths 390/1440; actual RTL page document width 916 and scroll width 916 after footer overflow correction. No captured JS error/warning on the checked catalog session.
- 521 files under app/routes/config match the official source snapshot. No core changes are required or shipped.

Preview data is synthetic and local only. It is not seeded into the release. These checks do not constitute a production server acceptance, real payment, real provisioning or cross-device exhaustive test. Only the supplied v1.5.9 target has been validated.

Machine-readable counts: `validation.json`. The package includes its own payload checksum manifest and file checksums.
