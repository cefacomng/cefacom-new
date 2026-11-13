# Cefacom Architecture

Source of truth for requirements: `docs/specs/`. This file records how the code is organised and the decisions made.

## Shape
Laravel modular monolith. No microservices. Inertia serves React/TypeScript pages from Laravel routes (no separate API app).

## Stack (verified versions in `BUILD_STATUS.md`)
Laravel 13, PHP 8.3+, React 19, TypeScript, Inertia 3, Tailwind 4 (brand tokens in `resources/css/app.css`: navy `#01406d`, teal `#01b4ba`, orange `#ff7a0f`), Vite, MySQL, Redis, Horizon.

## Code organisation
Standard Laravel folders with domain subfolders. Controllers stay thin; business logic lives in services, actions, jobs and policies.

| Area | Location (planned unless noted) |
|---|---|
| System health (Batch 0, real) | `app/Services/System/HealthChecker.php`, `php artisan cefacom:check` |
| Wallet, Ledger, Payments, Pricing | `app/Services/{Wallet,Ledger,Payments,Pricing}` |
| Campaigns, Sms, Email, Contacts | `app/Services/{Campaigns,Sms,Email,Contacts}` |
| Providers (adapters + interfaces) | `app/Services/Providers` (`SmsProviderInterface`, `EmailProviderInterface`, `PaymentProviderInterface`) |
| Webhooks, Reports, Admin, Audit | `app/Services/{Webhooks,Reports,Admin,Audit}` |
| Single-purpose operations | `app/Actions`; queue work `app/Jobs`; `app/Enums`, `app/Events`, `app/Listeners`, `app/Policies`, `app/Http/Requests` |
| Frontend pages | `resources/js/pages/**` (lowercase `pages` is the Inertia convention) |

## Non-negotiable rules (from the spec)
One unified wallet; integer kobo only; immutable ledger with compensating entries; reserve then capture/release; row locks + idempotency keys; per-recipient message records; submitted is not delivered; ambiguous outcomes become `unknown` and are reconciled, never auto-refunded or blindly retried; tenant isolation and staff permissions enforced server-side; provider secrets encrypted and never logged.

## Infrastructure decisions (Batch 0)
- Database: MySQL, UTF-8 (`utf8mb4`). Timestamps stored in UTC; `Africa/Lagos` is a display/scheduling setting (`config/cefacom.php`).
- Queue and cache: Redis. Sessions: database (spec has a `sessions` table).
- Horizon is installed; its dashboard is locked outside `local` until Batch 2 provides an admin gate.
- Tests run on a real MySQL database that must end in `_test`; `tests/TestCase.php` refuses to run otherwise.
- PHP tests stub Vite (`withoutVite()`); the frontend build and typecheck are verified separately.
- Planned queues (configured when used): dispatch, sms, email, webhooks, imports, domain-verification, reconciliation, scheduled.

## Environment notes
- Production target is MySQL 8.4 LTS. Development and tests so far ran on MySQL 8.0 only.
- Composer resolved Symfony 7.x because the build environment used PHP 8.3 (Symfony 8 needs PHP 8.4).

## Frontend design system (Batch 1)
- **Component foundation (decision):** hand-built components on native HTML (`<dialog>` for modals, `<label>`/`<input>` fields, semantic tables), Tailwind 4 and `lucide-react` icons. No Radix/shadcn: fewer dependencies, native focus trapping and accessibility. Revisit only if a complex widget (combobox, date picker) needs it in Batches 6-7.
- **Location:** `resources/js/components/ui` (Button, Field/Input/Select/Textarea/Checkbox, Badge, Card/StatTile, Feedback: Spinner/Skeleton/LoadingBlock/EmptyState/ErrorState/Alert, Modal, ConfirmDialog, Toast, DataTable, Pagination, SearchInput, PageHeader), `components/layout` (ShellLayout with AppLayout and AdminLayout, AuthLayout, WalletChip, Logo), `lib` (`money.ts`, `navigation.ts`, `icons.tsx`, `cn.ts`).
- **Colour rules:** navy structural; teal-strong `#007c82` for primary actions (white text about 5:1); brand teal `#01b4ba` only for focus rings and the active-nav marker; orange only for warnings and attention (always with dark text on a tint); danger red for errors.
- **Money:** `formatNaira(kobo)` is integer-only (BigInt); it throws on fractional or unsafe numbers. Money never passes through floating point in the UI.
- **Navigation:** defined once on the server (`app/Support/Navigation.php`), shared as an Inertia prop. Batch 2 filters it by permission server-side. Customer area `/app/*` (7 items), admin area `/admin/*` (13 sections), visually distinct.
- **Financial confirmation:** `ConfirmDialog` focuses Cancel first, shows the exact amounts, runs the confirm action at most once while pending, and cannot be dismissed mid-action. Server-side idempotency remains the real protection (Batch 4).
- **Shell preview gate:** because no authentication exists, `/app`, `/admin`, `/login`, `/design-system` answer 404 unless `CEFACOM_SHELL_PREVIEW` is true (default: only `local` and `testing`). Batch 2 replaces this with authentication and permission middleware. Demo data (`app/Support/Demo`) is sent only to `/design-system` in that mode and is never bundled into the JavaScript.
- **No invented data:** the Overview shows dashes and empty states until real records exist. The wallet prop is `null` until Batch 4.
- **Tests:** Vitest + Testing Library (`npm test`) for components; PHP feature tests for gating, navigation and demo-data containment; browser and axe scripts in `docs/verification/`.

## Authentication and access control (Batch 2)
- **No new Composer packages.** Built on Laravel core (auth, password broker, signed URLs, rate limiter, notifications) because Packagist is unreachable in the build sandbox.
- **Identity:** one `users` table. No roles = customer; any role = staff. Tables: `roles`, `permissions`, `role_permissions`, `user_roles` (spec names). `App\Enums\PermissionKey` and `RoleKey` are the source of truth; `cefacom:sync-permissions` creates missing rows and never edits what a Super Admin configured.
- **Checks:** `User::hasPermission()` (Super Admin implicit), one Gate per permission, `EnsureCustomer` and `EnsureStaff` middleware, `verified` for messaging areas. Hiding a link is never the control; `Navigation::admin($user)` filters links and the routes enforce the same rule.
- **Changing access:** only `StaffAccessService` (rules in its docblock), always audited in the same transaction.
- **Audit log:** `audit_logs` is append-only (model refuses update/delete) and redacts keys that look like secrets.
- **Customer isolation:** `BelongsToCustomer` + `OwnedByCustomerPolicy`. Owner comes from the session, never from input; other customers and staff get "not found"; staff access to customer data must be added explicitly per permission.
- **Sessions:** database sessions; password reset/change and "sign out other devices" delete the person's session rows and rotate the remember token.
- **Post-login redirect** only ever uses a local path inside the person's own area.
- **Production settings:** session cookie `secure` by default when `APP_ENV=production`, `URL::forceHttps()` in production, `TRUSTED_PROXIES` env for load balancers.
- **System emails** are sent synchronously by the configured mailer (log in development). Production transport is an open business decision.
