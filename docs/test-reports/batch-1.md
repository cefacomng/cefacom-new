# Batch 1 Test Report

Environment: Ubuntu 24.04 sandbox, PHP 8.3.6, MySQL 8.0.46 (NOT 8.4), Redis 7.0.15, Node 22.23.4, Chromium via Playwright. All results below come from the project unpacked from the ZIP into an empty directory.

## PHP: 21 passed, 180 assertions
Batch 0 tests (8) plus `ShellPreviewTest` (13): preview defaults on only for local/testing; every shell route (login, design system, `/app`, `/admin` and all 19 section paths) returns 404 when preview is off; demo data and shared navigation are not sent when off; customer navigation has exactly the 7 required items in order; admin has the 13 required sections in order; every navigation link resolves; placeholder pages name their delivering batch; unknown sections are 404; demo amounts are integers (kobo); the wallet prop is null.

## Frontend (Vitest, jsdom): 43 passed in 11 files
`formatNaira` (fractions, grouping, bigint, negatives, rejects fractional/NaN/unsafe numbers), navigation matching, Button (default type, loading disables and ignores clicks), form fields (label association, hint/error wiring, aria-invalid), Modal (labelled dialog, close paths, autofocus, scroll lock), ConfirmDialog (shows exact amounts, Cancel focused first, single execution while pending, cannot be dismissed mid-action), Toast (polite vs alert, dismiss, auto-dismiss, max four, provider guard), DataTable (headers, loading, empty, error with retry, search, pagination, focusable scroll region), EmptyState/ErrorState heading levels, ShellLayout (7 items, one current page, skip link, mobile menu open/close/Escape, wallet chip dash vs kobo amounts, admin variant), AuthLayout landmarks. jsdom lacks native modal behaviour, so dialog focus handling was verified in a real browser (below).

## Real browser (Chromium): `docs/verification/browser_check.py`
- 8 pages x 2 widths (1280, 390): all 200, no horizontal overflow, no console errors.
- Mobile: sidebar hidden when closed, visible when open, page behind inert, Escape closes, menu closes after navigating, correct item marked current.
- Desktop: sidebar visible, hamburger hidden. Modal: focus moves into the dialog, Tab never reaches the page behind (focus rests on `<body>` once per cycle, the browser's standard behaviour), Escape closes, focus returns to the trigger.
- Confirm dialog: Cancel focused first, Cancel disabled while the action runs, toast after completion.
- Table: search narrows rows, no-match empty state with clear-filters, pagination to page 2 of 3.

## Accessibility: axe-core, `docs/verification/axe_check.py`
0 violations (WCAG 2.0/2.1 A and AA plus best-practice rules) on 6 pages x 2 widths. Not a substitute for screen-reader testing.

## Defects found by these checks and fixed
1. Low-contrast helper text on the login layout (2.6:1) now meets contrast.
2. Login layout content sat outside landmarks; now banner, main and contentinfo.
3. Placeholder pages skipped a heading level; Empty/Error states now take a heading level.
4. The wide table's scroll area could not be reached by keyboard; it is now a labelled, focusable region.
5. The navigation label was on the sidebar container instead of the navigation landmark.
Also during setup: my own verification script mis-detected two things (hidden-element text, focus resting on body); the saved scripts were corrected.

## Not tested
MySQL 8.4, live Packagist install, Safari/Firefox, real devices, screen readers, print, dark mode, production deployment.
