# Web Phase 3 — Online Zarinpal and fallback manual receipts

**Date: 2026-11-15 · Payment-facing implementation merged in [PR #79](https://github.com/DenaSon/Denapax/pull/79). Production activation requires a verified gateway merchant and end-to-end live tests.**

> **Handoff status:** Code and mock-API CI passed (67 tests / 416 assertions); there is no verified live Zarinpal settlement, OTP provider activation, deployment or restore. **Phase 4 remains required.** Refer to [delivery ledger](39-web-roadmap-status.md) and [Phase 4 release plan](38-web-phase4-release-readiness.md).

## User experience

The customer sees a single order page immediately after submitting the order: number, final amount, primary "Pay securely with Zarinpal" button, secondary collapsed card-to-card receipt form, and payment/fulfillment status. An order list, private receipt link, shipment tracking and internal notification inbox are accessible through the store header. No second registration or new identity step is added.

## Implementation

- Zarinpal v4 request/verify transport with allowlisted production/sandbox domains; hosted payment redirect, untrusted GET callback and server-to-server verification of saved authority plus **exact stored integer IRR amount**. Optional `currency=IRR` is explicit in payment request.
- `gateway_payment_attempts` table: unique authority and reference ID; amount, gateway state, verification progress, audit timestamps, conflict state. A 45-minute active-attempt window prevents accidental second payments or simultaneous manual receipt submissions.
- Verified gateway payment transitions existing order `awaiting_payment` → `confirmed` and payment `awaiting_receipt/rejected` → `approved` with immutable history and exactly one outbox payment event. Duplicate callback does not trigger a second approval.
- Gateway error and timeout never mark an order paid. Provider-confirmed receipt conflicting with a previously finalized order goes to `requires_review` for manual reconciliation instead of overwriting or reapproving.
- Manual method remains under a collapsed alternative; validates JPG/PNG/WebP up to 8MB, accepts private storage only, uses existing transaction-safe `SubmitPaymentReceipt`, and leaves approval to authorized staff via existing `ReviewPaymentReceipt`.
- Customer `/orders` list and owner-scoped order/receipt views, current shipment and tracking information, and paginated inbox with per-recipient read state.

## Known implementation limits / release blockers

- **Priority P0: unresolved/expired attempt reconciliation.** `StartGatewayPayment` can age out a `redirected` or `verification_pending` attempt after 45 minutes and allow a new payment attempt. This must be changed: a timeout/age does **not** prove the prior transaction was unpaid. Implement explicit merchant/provider reconciliation and a safe retry policy before production payments.
- **Priority P0: operator exceptions.** `requires_review`, `verification_pending`, gateway conflicts and unmatched settlement records do not yet have a dedicated admin reconciliation interface/workflow; existing `/admin/payments` reviews manual image receipts. Add financial auditability and resolution ownership.
- **Priority P0: staging integration.** Confirm Zarinpal currency/amount semantics (IRR vs IRT), merchant account activation, real HTTPS callback and duplicate/retry behavior with the official provider and an authorized real transaction; CI currently mocks HTTP.
- **Priority P0: deployment.** Configure OTP SMS, test web migration on target-like MySQL, protect private receipt media and execute backup/restore.
- **Priority UX:** Validate purchase/payment recovery on actual phones, keyboard/a11y, cart → address → confirmation friction, session expiry during redirect, and manual receipt fallbacks. Outbound SMS notifications are not implemented; customer web inbox is.

## Environment

```env
ZARINPAL_ENABLED=false
ZARINPAL_MERCHANT_ID=
ZARINPAL_SANDBOX=false
```

**Do not enable without active official merchant credentials and an HTTPS callback host.** Sandbox and production merchant credentials must never be confused. Do not commit credentials. The gateway is disabled by default. Enable in a staging environment first, verify real Zarinpal v4 account currency requirements (IRR), callback DNS/HTTPS, duplicate callbacks, timeouts and transaction reconciliation before any production cutover. Real gateway transactions were not performed by repository CI.

Payment receipt files are stored under the existing private `receipts` disk. Never expose a direct public URL or allow arbitrary paths. Migrations are additive; back up the database and receipt directory and confirm phase 1/2 deployment prerequisites first.

## Acceptance and operational gates

Run CI (unit/feature tests, Composer audit, Pint, npm build) and staging acceptance:
- Payment start sends exact IRR amount and verified merchant; callback does not trust `Status=OK` alone
- Provider verification, repeated callbacks, unavailable verification, retry, duplicate attempts and post-payment conflicts
- Upload image validation, owner-only private receipt retrieval, staff manual approval and shipment tracking
- Expired or conflicting gateway attempts: manually reconcile any paid-but-unrecorded bank events; do not suggest repaying while uncertain
- Install/confirm queue worker and scheduler for customer notifications
- Runflare deployment, real SMS OTP and Zarinpal test plus small live authorized end-to-end before public sales launch

Do not advertise the store as live merely because CI passed. A live merchant account, SMS provider and audited production environment remain deployment requirements.
