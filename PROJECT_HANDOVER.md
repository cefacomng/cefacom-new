# Cefacom Project Handover

## Start a new Claude chat
1. Upload the latest complete project ZIP (and, optionally, the two specs from `docs/specs/`).
2. Say: "Read PROJECT_HANDOVER.md, BUILD_STATUS.md and ARCHITECTURE.md, inspect the project, then continue from the next unfinished batch."
3. Do not upload or request the legacy ZIP.

## What this is
A new, independent Laravel + React/Inertia SMS and Email platform with one unified Naira wallet (integer kobo), pay-as-you-go pricing set by admins, per-recipient message tracking and an immutable ledger. Full requirements: `docs/specs/`. Legacy lessons: `CEFACOM_ENGINEERING_REFERENCE.md`.

## Where things are
`BUILD_STATUS.md` (what is done and verified), `ARCHITECTURE.md` (structure and decisions), `CHANGELOG.md`, `docs/test-reports/`, `README.md` (setup).

## Ground rules
Build only the agreed batch. Test everything and report only real results. Stop and ask before changing a business rule, financial rule, provider billing rule or security model. Produce a complete ZIP at every accepted batch.

## Working in the Claude sandbox
Packagist and getcomposer.org do not resolve there, but GitHub does. A fresh `composer install` works from the committed `composer.lock` (dist URLs point to GitHub). Adding a new Composer package in that sandbox needs its GitHub repository added temporarily as a `vcs` repository; do not commit those repositories.

## Sandbox tips (learned the hard way)
- MySQL, Redis and `php artisan serve` do not survive between tool calls. Start them in the same command that uses them (see `docs/verification/cleanroom.sh`), then wait until they answer.
- Never use `pkill -f` with a pattern that appears in your own command; use `pkill -x php`.
- Signed links only work on the host in `APP_URL` (use `localhost:8000` in browser checks, not `127.0.0.1`).
- Always verify from the unpacked ZIP (`docs/verification/cleanroom.sh browser`). It caught real packaging bugs in Batches 0 and 1.
- Playwright (Python) and Chromium are available for real-browser checks; scripts are in `docs/verification/`.

## Next task
See "Next" in `BUILD_STATUS.md`.
