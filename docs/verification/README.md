# Verification scripts

Repeatable checks run in real Chromium (Playwright for Python) against a running app.

```bash
pip install playwright && playwright install chromium     # once
npm install --prefix /tmp/axe axe-core                     # once, for the accessibility audit
npm run build && php artisan serve --port=8000             # MySQL and Redis must be running; APP_URL must be http://localhost:8000
CEFACOM_BASE=http://localhost:8000 python3 docs/verification/auth_flow_check.py
```

`auth_flow_check.py` registers a customer, proves unverified customers are blocked from messaging areas, verifies via a signed link, edits the profile, signs out and in (wrong and right password), checks customers get 404 on `/admin`, signs in a Super Admin (13 sections, kept out of `/app`), and runs axe-core (WCAG 2.0/2.1 A and AA plus best practice) on the login, register, forgot/reset, settings, overview screens and the open account menu. Use `localhost` (the host in `APP_URL`): signed links are only valid for that host.

`cleanroom.sh` (sandbox-specific paths) rebuilds the ZIP from tracked files, unpacks it in an empty directory, installs, migrates, runs PHP and frontend tests, type-check and build, and with the argument `browser` runs the script above against the unpacked copy. Set `CEFACOM_DB_PASSWORD` to the MySQL password (never commit it).

NOT covered: the Batch 1 layout checks (mobile drawer, modal focus, table behaviour on the signed-in shell) have not been re-run since authentication was added; screen readers; real devices.
