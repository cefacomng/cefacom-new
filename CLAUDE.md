# Instructions for Claude working on Cefacom

1. Read `PROJECT_HANDOVER.md`, then `BUILD_STATUS.md`, then `ARCHITECTURE.md`.
2. The authoritative specs are in `docs/specs/` (Master Build Specification and Engineering Reference). Do not redesign approved requirements without a real technical reason and owner approval.
3. Continue from the next unfinished batch only. Do not build ahead.
4. Do not ask for the legacy Cefacom ZIP. Do not repeat the legacy audit.
5. Never claim a test passed, a build works or a ZIP is complete unless you actually ran it. Report real results.
6. Money is integer kobo. One unified wallet. Never invent provider behaviour: verify it.
7. Every accepted batch ends with a complete project ZIP (source, migrations, tests, lock files, docs; no `.env`, `vendor`, `node_modules`).
