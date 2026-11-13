# Cefacom Engineering Reference (condensed)

Condensed from `docs/specs/CEFACOM_ENGINEERING_REFERENCE_AND_LEGACY_AUDIT_v1.0.docx`. The legacy ZIP is not needed and must not be requested. Legacy findings are lessons, not current provider documentation.

## What the legacy app got wrong (do not repeat)
| Area | Legacy problem | New rule |
|---|---|---|
| Money | Float balances; separate SMS/Email/WhatsApp credits; read-modify-write without row locks | Integer kobo, one wallet, `SELECT ... FOR UPDATE` inside a transaction, immutable ledger |
| Refunds | Returned segments of one message, not recipients x segments; not reliably invoked | Recipient-level settlement; refunds reference the original reservation/charge; unique financial references |
| SMS status | Messages stayed pending/processing after submission; no delivery-report lifecycle | Per-recipient records; states Queued, Processing, Submitted, Delivered, Failed, Unknown; stale-processing detection |
| SMS dispatch | Overlapping delayed-queue and cron scheduling (double-send risk); retries after lost responses | One scheduler with atomic claiming; unique dispatch references; timeout after possible submission means Unknown, reconcile, never blind retry |
| Diagnostics | Exception logging commented out | Structured per-attempt logging with error classes; no secrets or needless personal data |
| Phones | Weak Nigerian normalisation | Canonical `+234...`; validate before confirmation; keep original input |
| Email status | Provider "accepted" marked DELIVERED | Only provider delivery evidence sets Delivered; handle bounce, complaint, unsubscribe, duplicates, out-of-order events |
| Domains | Simplistic DNS checks, provider-specific SPF, hard-coded DKIM selector | Compare DNS to the exact records the provider returns; support TXT chunks, propagation delay, rechecks |
| Paystack | Weak amount check, no complete idempotent finalisation | Verify server-side: reference, status, exact amount (kobo), currency, customer; one idempotent finaliser for callback and webhook; verify webhook signature on raw body |

## Keep / replace / remove
Keep: Laravel, MySQL, Redis, Horizon, Termii (initial SMS), UniOne (initial Email), Paystack (only payment provider), sender ID workflow, contact groups, CSV import, domain management concept. Rebuild: templates, visual editor, admin pricing (versioned), staff permissions. Replace: all wallet/refund/status/scheduling/DNS logic. Remove: WhatsApp and its Node service, Android SIM gateways, WordPress, subscriptions, credit packages, extra payment/email providers.

## Retry policy by error class
Invalid recipient: no retry. Invalid sender ID: no retry until fixed. Authentication failure: stop and alert. Rate limit: back off per provider. Confirmed temporary rejection or network failure before submission: retry where safe. Timeout after possible submission: Unknown, reconcile. Duplicate webhook: ignore after idempotency check.

## Verify against current official docs before production (record URL, date, version)
Termii: auth, request schema, batch limits, sender ID rules, response meaning, delivery report format, webhook auth, status lookup, idempotency, rate limits, billing, DND/route rules.
UniOne: auth, send schema, batch limits, accepted-response meaning, message IDs, domain verification, SPF/DKIM expected records, delivery/bounce/complaint/unsubscribe events, webhook auth, status lookup, limits, billing.
Paystack: initialise, amount units, currency, verify response, webhook signature, events, retries/duplicates, refunds, settlement.

## Open business decisions (owner approval required; none block Batch 0)
- SMS: user-chosen or automatic route; per-route pricing; route visibility; route restrictions vs eligibility.
- Sender IDs: mandatory per customer or shared default; routes per sender ID.
- Unknown outcomes: how long under reconciliation; evidence that makes a message billable; what if no final report ever arrives. Never auto-refund for a missing report.
- Email: shared verified domain, per-customer domain, or both.
- Payments: minimum funding amount; pass processing fees to customers; tax/regulatory charges; staff manual adjustment permissions.
- Compliance: consent evidence, retention periods, SMS/Email opt-out rules, Nigerian regulatory and data-protection requirements.
Document each decision before the affected production feature is enabled.

## Security handling
Never commit `.env`, keys, dumps or provider credentials; never put them in handover docs. Rotate any legacy credentials that were live and exposed.
