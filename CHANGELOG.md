# Changelog

## Batch 2 - Authentication, roles and permissions
- Registration, sign-in with rate limiting, sign-out, email verification, password reset, account settings, email change with confirmation.
- Customer and staff areas; Super Admin and restricted roles; per-permission gates; server-filtered admin navigation.
- Staff-management service with privilege-escalation rules, immutable secret-redacting audit log, customer-ownership trait and policy, Super Admin command, permission sync command.
- Horizon dashboard restricted to Super Admins; preview gate from Batch 1 removed; open-redirect in post-login redirect found by tests and fixed.
- 132 PHP tests, 54 frontend tests, real-browser end-to-end check with axe-core.

## Batch 1 - Design system and application shell
- Responsive customer shell (7-item sidebar, mobile drawer, wallet chip), separate admin shell (13 sections), login layout.
- Reusable components: buttons, inputs, tables with search/filter/pagination, modal, financial confirmation dialog, notifications, loading/empty/error states, alerts, badges.
- Server-defined navigation, shell-preview gate (off in production), development-only demo data, integer-only Naira formatting.
- 21 PHP tests, 43 frontend tests; real-browser layout checks and an axe-core audit (0 violations) with scripts in `docs/verification/`.

## Batch 0 - Project foundation
- New Laravel 13 project with Inertia 3, React 19, TypeScript, Tailwind 4, Vite.
- MySQL and Redis configuration, Horizon installed, `.env.example` with placeholders only.
- Domain folder structure, `HealthChecker` service and `php artisan cefacom:check`.
- Test configuration on real MySQL with a safety guard; 8 PHP tests.
- Docs: README, ARCHITECTURE, BUILD_STATUS, PROJECT_HANDOVER, CEFACOM_ENGINEERING_REFERENCE, specs in `docs/specs/`.
