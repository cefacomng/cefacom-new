# Cefacom Build Status

Last updated: Batch 2 checkpoint.

| Batch | Scope | Status |
|---|---|---|
| 0 | Foundation | Complete, verified |
| 1 | Design system and app shell | Complete, verified |
| 2 | Authentication, roles, permissions | Complete, verified (see below) |
| 3 | Contacts | Next |
| 4 | Wallet and ledger | Not started |
| 5 | Paystack funding | Not started |
| 6 | SMS | Not started |
| 7 | Email | Not started |
| 8 | Reports | Not started |
| 9 | Admin | Not started |
| 10 | Hardening and launch checks | Not started |

## What exists (Batch 2)
Registration, sign-in, sign-out, email verification, password reset, account settings (profile, email change with confirmation, email preferences, password, sign out other devices), customer and staff areas, Super Admin and restricted staff roles, permission gates, staff-management service, immutable audit log, customer-ownership foundation, Super Admin command, Horizon locked to Super Admins. The temporary shell-preview gate from Batch 1 is gone; `/app` and `/admin` now require real sign-in.

Commands: `php artisan cefacom:sync-permissions` (run after every migrate/deploy; safe to repeat), `php artisan cefacom:make-super-admin you@example.com` (hidden password prompt; the only way to create the first staff account).

## Decisions made in Batch 2 (review these)
1. A customer is a user with no roles; staff is a user with at least one role. Staff use `/admin` only (visiting `/app` redirects to `/admin`); customers get a 404 for every `/admin` URL.
2. Public registration can only ever create customers. The first Super Admin is created from the command line.
3. Roles are data. Super Admin holds every permission implicitly. "Admin Staff" exists by default with no permissions until granted. Restricted roles (for example "Support Agent") can be created through `StaffAccessService`; there is no screen for it until Batch 9.
4. Thirteen permissions: your twelve examples plus `system_settings.manage` (needed because System Settings is an admin section).
5. Unverified customers can sign in, see the overview and manage their account. SMS, Email, Contacts, Reports and Wallet require a verified email address.
6. Staff-management rules enforced on the server and audited: needs `staff.manage`; only a Super Admin can grant or revoke Super Admin or change another Super Admin; nobody can change their own roles; the last Super Admin cannot be removed; you cannot grant or assign permissions you do not hold.
7. Passwords: 10 to 72 characters with letters and numbers (bcrypt ignores characters past 72). Not checked against breached-password lists.
8. Rate limits: sign-in 5 failures per minute per email and address (plus 20 per minute per address); registration 6 per minute per address; reset requests 5 per minute per address; verification emails 6 per minute.
9. Email change needs the current password, a confirmation link sent to the NEW address, and a warning sent to the OLD address; the email stays unchanged until confirmed. Password change and "sign out other devices" end all other sessions and remember-me cookies.
10. Registration reveals whether an email already has an account (a common trade-off). Sign-in and password reset do not.
11. Email preferences are three toggles I chose (low balance alerts, campaign reports, product updates). Nothing sends them yet.
12. Phone numbers are stored as +234XXXXXXXXXX, mobile prefixes 70x, 71x, 80x, 81x, 90x, 91x only. Batch 3 must re-check these against current NCC numbering.

## OPEN BUSINESS DECISION (blocks production, not development)
**Which service sends system emails (verification, password reset, email change)?** Today the mailer is `log` in development. Production needs a real transport (SMTP or UniOne transactional). Until decided, sign-up emails will not reach customers in production.

## Verified (actually run, from the ZIP unpacked in an empty directory)
`composer install`, `npm ci`, migrations, `cefacom:check`, PHP tests (132), frontend tests (54), type-check, production build. In real Chromium against the unpacked copy (`docs/verification/auth_flow_check.py`): register, blocked while unverified, verify, edit profile (phone stored as +234...), invalid phone error, sign out, wrong password gives the generic error, sign in, customer gets 404 on `/admin`, Super Admin sees 13 sections and is kept out of `/app`, staff account page; axe-core 0 violations on login, register, forgot and reset password, settings, overview and the open account menu. Details: `docs/test-reports/batch-2.md`.

## NOT verified
- The Batch 1 layout checks (mobile drawer, modal focus, table behaviour) have NOT been re-run on the signed-in shell since authentication, the account menu and the verification banner were added. The old scripts were removed because they cannot sign in.
- Email content and real delivery (tests check what is sent and to whom, not how it looks or whether it arrives). Verification links in the browser check were generated directly, not read from a mailbox.
- HTTPS-only session cookies, forced HTTPS and trusted proxies (configured for production but never run over HTTPS).
- MySQL 8.4 (all runs on 8.0.46), live Packagist install, browsers other than Chromium, real devices, screen readers.
- Concurrency of staff changes (the last-Super-Admin lock is written but only exercised in a single process).
- Two-factor authentication (not in the spec) and account suspension (Batch 9).

## Known notes
- `StaffAccessService` has no screens yet; role and permission management UI is Batch 9.
- Staff accounts must be verified to use `/admin`; the command marks them verified.
- Unused default `cache` table migration remains.

## Next: Batch 3 (contacts)
Contacts, groups, CSV import, phone validation, duplicate handling, search, tenant isolation tests using `BelongsToCustomer` and `OwnedByCustomerPolicy`. Replace the demo-data class with real seeders.
