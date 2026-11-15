# Denapax — authoritative web delivery ledger and remaining backlog

**Last reviewed: 2026-11-15.** Source: code merged into `master`, GitHub PRs and [green CI at SHA `181793753b20dfd1cc4a0d7a5f41d306c433e589`](https://github.com/DenaSon/Denapax/actions/runs/43718573628). **Code complete ≠ staged ≠ production-deployed ≠ merchant-settled ≠ release-approved.** This ledger supersedes earlier Telegram-era scope/progress lists. No claim is made that a live web migration, real SMS, real gateway payment or production restore was run.

## Completed in repository (merged, automated CI evidence)

| Workstream | Delivered scope (implementation only) | Evidence |
| --- | --- | --- |
| Phase 1 — web identity / Telegram retirement | Independent customer authentication guard, Iran mobile + hashed OTP with limits, fail-closed SMS transport, guest/persistent cart merge, web storefront integration, Telegram runtime and Nutgram removed, web notification persistence, irreversible guarded schema cutover | [PR #77](https://github.com/DenaSon/Denapax/pull/77), `f294a4cde42c6a96023f9a249f190bc04fd3ea79`, [docs/35](35-web-phase1-cutover.md) |
| Phase 2 — storefront + checkout | Persian catalog search, guest/cart operations, saved addresses, shipping quote with weight, session-bound price fingerprint, idempotent order snapshot and customer-scoped order confirmation | [PR #78](https://github.com/DenaSon/Denapax/pull/78), `18a1e5d4c719ec40796a9b85ce586884534e8fe0`, [docs/36](36-web-phase2-checkout.md) |
| Phase 3 — payments + orders | Zarinpal v4 request/verify adapter and attempt ledger; online-first order page; fallback manual receipt upload/private retrieval/approval/rejection; order list, shipment tracking and customer web inbox | [PR #79](https://github.com/DenaSon/Denapax/pull/79), `181793753b20dfd1cc4a0d7a5f41d306c433e589`, [docs/37](37-web-phase3-payments.md) |
| Preserved staff foundations | Staff Fortify/RBAC, catalog/prices, shipping configuration, immutable order amounts, receipt review, fulfillment, transactional outbox and database queue | Existing code + CI; still needs production-like acceptance |

**Latest verified master CI:** 67 passed / 416 assertions; Composer production dependency audit and validation, Laravel Pint and Vite build green. Test runner uses SQLite; **staging MySQL and real provider behavior remain unverified**.

## What is implemented but not activated

| Component | In code | Real environment / release evidence |
| --- | --- | --- |
| OTP authentication | Yes; provider default `disabled` | No verified configured SMS adapter or actual delivery |
| Zarinpal payment | Yes; `ZARINPAL_ENABLED=false` by default | No verified activated merchant, currency contract, live gateway callback or settlement |
| Manual receipt processing | Yes; owner-scoped private storage and admin review | No current web production upload/reconciliation and backup/restore acceptance |
| Order and shipment UI | Yes | No new web E2E customer/order/fulfillment test in production-like MySQL |
| Web inbox/outbox dispatch | Yes | No demonstrated supervised production worker/scheduler end-to-end delivery |
| Customer web database migration | Guarded migration exists | No proof it was safely run against target production database |
| Sales launch | Not a code-only deliverable | **NO-GO pending Phase 4** |

## Phase 4A financial-safety implementation (PR #82; code-level only)

- Removed the original automatic retry after 45 minutes and blocked concurrent receipt/manual payment and order cancellation while an online attempt is unresolved.
- Implemented v4 Zarinpal inquiry, fenced verifier ownership, conservative ambiguous results, late settlement conflict handling and permission-gated operator reconciliation with mandatory evidence in `/admin/payments/gateway`.
- Added targeted tests for stale payment, no-Authority provider error, inquiry FAILED/PAID, permission denial, bank conflict and old verification response; see [Phase 4A runbook](40-web-phase4a-gateway-reconciliation.md).
- **Remaining external proof:** activated merchant, actual provider contract/currency, controlled settlement/reconciliation and real delayed callback tests. This remains **NO-GO for public sales**.

## Phase 4B mobile UX implementation (PR #83; code-level only)

- Changed the normal web purchase path to a one-page server-quoted checkout with a saved default address and a single order-confirm action; the previous separate review route remains compatible.
- Reworked product variant selection into one simple form, cart +/- into one tap, OTP into focused steps with Persian digit acceptance, mobile account navigation and the delivery form with customer mobile prefill and return to edited address.
- Focused tests for quote freshness, refresh/idempotency, address ownership, guest/customer quantity changes and OTP return-to-checkout; [UX technical handoff](41-web-phase4b-mobile-checkout-ux.md).
- **Still pending:** actual iOS/Android browser testing, accessibility/zoom checks and real provider/payment recovery UX. The code PR is not launch acceptance.

## Remaining P0 — block public web-sales launch

1. **Payment reconciliation live acceptance**: no-double-charge/inquiry/operator-review code is implemented in PR #82; verify this against an activated provider and bank panel, reconcile actual merchant settlements, document manual conflict/refund escalation and ensure no public activation on code CI alone.
2. **Real providers**: activated Zarinpal account, HTTPS callback and verified IRR/IRT handling with a small authorized payment; real SMS OTP delivery configured and tested.
3. **Security / E2E (#24)**: staging MySQL and real browser/device regression, payment callback duplicate/conflict/timeout, receipt rejection/retry, private file permissions, customer/staff isolation.
4. **Operations / resilience (#25)**: target-DB customer count, guarded irreversible migration rehearsal, MySQL and private receipt backups, isolated restore, Runflare HTTPS/secrets/debug validation, supervised queue/schedule and alerts, documented rollback.
5. **Mobile simplicity / business correctness**: reduce unnecessary checkout friction, robust return-from-gateway flow, readable toman pricing/feedback, shipping tariff and customer support details, published inventory and oversell policy.
6. **Release approval (#11)**: record operator and owner GO/NO-GO with evidence; mark NO-GO until all criteria pass.

## P1 — after P0 or after controlled launch

- SMS updates for status/order changes (web inbox exists, outbound order SMS not implemented).
- Better customer self-service, conversion measurements and ongoing responsive/a11y improvements.
- Numeric stock control if required by actual sellable inventory; until then only variant availability flags exist.
- Variant price revision history and audit ([#70](https://github.com/DenaSon/Denapax/issues/70)).
- Refund/return procedures and automation, discounts, sophisticated delivery options, if agreed.

## Explicitly retired / excluded

The Telegram customer bot, Nutgram dependency, Telegram webhook, chat-group alerts, Telegram identity columns and Telegram-first order UX are retired. Do not reactivate them. Historical docs/01–34 and old issue histories explain decisions from that period but are **not the active web specification**. There is no Redis or Horizon launch requirement; database queue is the approved implementation.

## Next start point in a new conversation

1. Read [AGENTS.md](../AGENTS.md), this ledger and [Phase 4 plan](38-web-phase4-release-readiness.md).
2. Inspect `master` + latest CI and real open release issues [#11](https://github.com/DenaSon/Denapax/issues/11), [#24](https://github.com/DenaSon/Denapax/issues/24), [#25](https://github.com/DenaSon/Denapax/issues/25).
3. Review **Phase 4A PR #82** and [reconciliation operator runbook](40-web-phase4a-gateway-reconciliation.md), then validate **Phase 4B mobile UX on real devices** and start **4C real provider/MySQL staging**; confirm real bank acceptance before production enablement.
4. Keep tests focused, require green CI before merge, and do not claim a deployment or provider settlement without proof.

Whenever this ledger is updated, distinguish **implemented / unit-tested / staging-verified / production-verified / owner-approved**; never mark unchecked items completed from code inspection alone.
