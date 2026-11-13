# Batch 2 Test Report

Environment: Ubuntu 24.04 sandbox, PHP 8.3.6, MySQL 8.0.46 (NOT 8.4), Redis 7.0.15, Node 22.23.4, Chromium via Playwright. Results come from the project unpacked from the ZIP into an empty directory.

## PHP: 132 tests (real MySQL and Redis)
Registration (hashing, trimming, duplicates, weak passwords, mass-assignment of roles/verified flags ignored, mail failure keeps the account, rate limit); sign-in (generic errors, per-email lockout that clears on success, remember cookie, session id rotation, intended-URL rules, CSRF); password reset (identical response for unknown emails, one-time tokens, all sessions revoked, rate limit); email verification (signed links only, wrong user/hash/expired rejected, resend limits, unverified customers blocked from messaging areas); account settings (profile and canonical phone, cross-account tampering ignored, email change flow with both notifications and audit entry, preferences, password change and other-device sign-out); access control (customers have nothing, 404 on admin, staff redirected from customer area, every admin section enforces exactly its own permission, navigation filtered per person); staff-management rules (no self-change, no Super Admin grant by non-Super Admins, cannot assign a role stronger than yourself, last-Super-Admin guard, audited before/after); audit log (immutable, redacts nested secrets); Super Admin command; customer isolation foundation (other customers and staff get "not found"); Horizon gate; phone normalisation; health checks.

## Frontend: 54 tests (Vitest)
Everything from Batch 1 plus password show/hide, account menu (open, Escape, outside click, sign out by POST, staff roles), verification banner, auth layout landmarks and inline status.

## Real browser (Chromium): docs/verification/auth_flow_check.py
15 behaviour checks all true; axe-core 0 violations on login, register, forgot password, reset password, settings, overview and the open account menu; no unexpected console errors.

## Defects found by these checks and fixed
1. **Open redirect:** the post-login redirect validated the path of a stored "intended" URL but then followed the whole URL, so `https://evil.example/app` would have been followed. Now only a local path inside the person's area is used; covered by six hostile inputs.
2. Staff service allowed only two roles, so every staff member got identical permissions; redesigned around roles as data with custom restricted roles.
3. Cached permissions survived a model refresh; fixed.
4. Navigation label was on the sidebar container instead of the navigation landmark (earlier batch).

## Not tested
Email appearance and real delivery, HTTPS cookies, MySQL 8.4, other browsers, devices, screen readers, concurrent staff changes, and the Batch 1 layout checks on the signed-in shell.
