# Batch 0 Test Report

Environment: Ubuntu 24.04 sandbox, PHP 8.3.6, MySQL 8.0.46 (NOT 8.4), Redis 7.0.15, Node 22.23.4.

## Automated PHP tests: 8 passed, 30 assertions (real MySQL `cefacom_test`, real Redis)
- Foundation: home page renders the `Foundation` Inertia component with expected props; `/up` responds; base tables exist after migration; money config is NGN / 100 kobo / Africa/Lagos.
- Health: all checks pass on healthy infrastructure; queue check fails when queue is not redis; database failure details do not leak the password or host; `cefacom:check` command succeeds when healthy.
- Safety guard: `tests/TestCase.php` refuses to run against any database not ending in `_test`.

## Other checks
- `npm run typecheck`: clean. `npm run build`: succeeded (CSS about 32 kB, JS about 358 kB before gzip).
- HTTP: `/` and `/up` returned 200 from the unpacked ZIP; page carried the Inertia payload and built asset links.
- Queue: closure job dispatched to Redis was executed by a running Horizon worker; queue length returned to 0.

## Defects found by the clean-room run and fixed
1. ZIP excluded tracked `storage/framework/*/.gitignore` placeholders, so views could not compile after unzipping. ZIP is now built from `git ls-files` plus `.git`.
2. `tests/Unit` was empty and untracked, so PHPUnit would not start from a fresh checkout. Fixed with a tracked placeholder.

## Not tested
MySQL 8.4, live Packagist install, Windows/macOS, production deployment, any provider integration (none exists yet).
