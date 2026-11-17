# Web Phase 4A — Zarinpal reconciliation, no-double-charge policy and operator runbook

**Implementation:** [PR #82](https://github.com/DenaSon/Denapax/pull/82) · **Date:** 2026-11-15 · **Release gate:** **NO-GO** until the official live merchant contract, real settlement reconciliation, browser checks, MySQL and restore gates are verified.

This document is the current source of truth for **payment attempt safety**. Historical Phase 3 documentation describes the old behavior; do not implement age-based auto-expiry or auto-retry based on it.

## Problem addressed

The previous implementation treated a gateway request as expired after 45 minutes and allowed a new online charge or manual transfer even if the earlier gateway transaction was only timed out or its browser callback had been lost. This is not evidence that the bank failed to collect the money.

Phase 4A replaces this assumption with a **conservative money-safe policy**:

1. **Any unresolved attempt blocks another online charge, manual receipt submission and order cancellation**, regardless of age. This applies to historical `failed`/`expired`, missing Authority, `redirected`, `verifying`, `verification_pending` and `requires_review`. A pre-existing payment result is never silently discarded.
2. An active `redirected` attempt may reuse **the same** Authority within a 45-minute navigation window. After that it requires provider recheck or operator reconciliation; it does **not** create a new attempt.
3. Gateway API errors before Authority receipt are recorded as `request_unknown`; they cannot trigger an immediate new attempt.
4. `verify` response codes 100 and 101 with a valid Ref ID are the only automated success path. Any inconclusive response/timeout remains **verification_pending**, never a confirmed unpaid result.
5. Verification claims have a UUID **fencing token** and 2-minute worker lease. After lease takeover, a delayed previous HTTP response cannot overwrite a newer verification result.
6. A successful late callback following an operator clearance is reconciled: if another active payment or pending manual receipt exists, record `requires_review`, preserve the provider Ref ID, and **do not** approve the order a second time.
7. New payment is permitted only after a role-authorized, auditable `cleared_unpaid` decision: a **recent official inquiry showing FAILED/REVERSED for known Authority** and an independent merchant-panel check recorded in a mandatory resolution note. For `request_unknown` without Authority, wait at least one hour, inspect the merchant panel by time/amount, and record evidence before release. Never clear `verified`, `requires_review`, or in-flight `verifying` attempts.
8. The original `orders` monetary snapshots and outbox event uniqueness remain intact; approval still updates order status/history and emits a single event in a DB transaction.

## Payment attempt states

| State | Meaning | Permit new online/manual payment? | Operator path |
| --- | --- | --- | --- |
| `initializing` | Request created, possibly in flight | **No** | Investigate stalled request; do not assume unpaid |
| `request_unknown` | Gateway response unknown, Authority not stored | **No** | Review merchant panel after 1 h, document and clear if confirmed unpaid |
| `redirected` | Authority issued / user sent to provider | **No** | Same Authority retry within 45 min; afterwards inquiry/reconcile |
| `verifying` | One server-side verification claimed | **No** | Wait for result or lease takeover; never clear in flight |
| `verification_pending` | Verification failed/unknown/temporary HTTP error | **No** | Fresh inquiry; compare merchant panel, verify PAID or clear confirmed unpaid |
| `failed` / `expired` | Historical status, not bank settlement proof | **No** | Same reconciliation as above |
| `requires_review` | Bank reports payment, but order/other attempt conflicts | **No** | Escalate to accounting/support; no automatic retry, no "unpaid" override |
| `verified` | Provider-verified paid / order confirmed | **No** | Fulfillment; verify merchant panel in acceptance |
| `cleared_unpaid` | Reviewer documented no collected payment or refunded reversal | **Yes**, if order itself still permits payment | Audited staff decision, evidence and time |

**Never infer FAILED from a generic non-100/101 `verify` error.** Official `inquiry` statuses are `PAID`, `VERIFIED`, `IN_BANK`, `FAILED`, `REVERSED`. PAID/VERIFIED initiate server-side `verify`; `FAILED`/`REVERSED` are only **eligibility** for a separately audited staff clearance, not automatic clearance. `IN_BANK` is never automatically released.

## Admin operator procedure

Admin route: **`/admin/payments/gateway`**, accessible only to staff with `payments.view`. Actions require `payments.review` and an active staff account. Every clearance writes `resolved_by`, `resolved_at`, `resolution_note` and a Spatie Activitylog record. The customer sees neither bank internals nor other people's payment attempts.

1. Open **تراکنش‌های زرین‌پال** and filter unresolved attempts; find the order, Authority, amount and latest status.
2. If Authority exists, click **استعلام وضعیت از زرین‌پال**. `PAID`/`VERIFIED` automatically attempt server-side verification. If `IN_BANK` or inquiry fails, **do not clear** the attempt.
3. If the **recent** inquiry shows `FAILED` or `REVERSED`, independently confirm the merchant/bank statement, and enter a non-sensitive audit reference plus reason (20–1000 characters). Then use the confirmed **واریز نشده** action. A fresh inquiry is valid for 5 minutes.
4. If Authority was never received (`request_unknown`), wait an hour from attempt creation, cross-check merchant transactions using time, amount and order number, enter verifiable evidence and clear only if unpaid. The system does **not** infer non-payment from the missing Authority.
5. For `requires_review`, contradictory settlement, refunds or already-paid orders, **escalate** to the payment owner. This screen intentionally cannot mark them unpaid. Document manual refund/compensation outside the automated order transition until a dedicated reversal/refund workflow is implemented.
6. Customer order page deliberately shows **در حال بررسی / دوباره پرداخت نکنید** and offers a recheck if Authority exists. If uncertain, direct the customer to configured store support, not to a second payment.

## API and data contract

- `POST /pg/v4/payment/request.json` and `verify.json` (existing), new `inquiry.json` on the Zarinpal allowlisted HTTPS host. Inquiries send merchant UUID + Authority; only a successful response with a known status is trusted.
- Existing `gateway_payment_attempts` ledger extended by migration `2026_11_15_190000_add_gateway_reconciliation_fields.php`: `verification_token`, `inquiry_status`, `inquiry_checked_at`, `resolved_by`, `resolved_at`, `resolution_note`. Additive migration; existing staff/order/receipt rows retained.
- New operator UI under existing Fortify/RBAC admin. Customer route `/orders/{orderNumber}` retains owner checks; public callback still verifies server-side and redirects through protected order route.
- Existing `ZARINPAL_ENABLED=false` and SMS-disabled defaults remain unchanged. Never commit merchant secrets.

## Code validation and remaining real release acceptance

Targeted automated tests cover: stale Authority block, manual receipt conflict, fresh inquiry before clearance, permissions, missing Authority cooling period, PAID/`code=101` recovery, late success with a newer payment and verifier token fencing. All other checkout, admin and catalog regression tests continue to run in CI.

**Not done by this implementation / still requiring separate evidence:** live Zarinpal merchant settlement, actual bank/panel reconciliation, provider production currency contract confirmation (IRR vs IRT), MySQL staging migration, full mobile browser UX or backup/restore. Real SMS.ir OTP delivery and successful customer login were owner-confirmed on 2026-11-17; the exact deployment tier was not recorded (see [SMS.ir acceptance record](49-smsir-otp-integration.md)). The owner does not yet have a Zarinpal merchant account; keep the gateway **disabled** and Issue #81 open until merchant onboarding and live acceptance. See [Phase 4 plan](38-web-phase4-release-readiness.md), [current ledger](39-web-roadmap-status.md), release issues [#11](https://github.com/DenaSon/Denapax/issues/11), [#24](https://github.com/DenaSon/Denapax/issues/24), [#25](https://github.com/DenaSon/Denapax/issues/25) and financial issue [#81](https://github.com/DenaSon/Denapax/issues/81).

## Acceptance scenarios for live staging

- Fresh order → real gateway checkout → callback → amount/Ref ID match → single approval → fulfillment.
- User closes bank tab / loses cookie / duplicate callback / temporary 5xx: no second payment until reconciliation, and correct status after recovery.
- Inquiry `FAILED`/`REVERSED` with independent merchant proof → staff clearance → retry; a later paid callback on the old Authority becomes **requires_review** if a newer attempt is present.
- Unauthorized staff and customers cannot resolve/view other customers' payment information; private receipts remain inaccessible outside authenticated owners/staff.
- PHP/Laravel CI pass is not evidence of merchant settlement; capture sanitized transaction evidence, date, environment, rollback and owner sign-off before public release.
