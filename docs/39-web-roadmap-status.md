# Denapax — authoritative web delivery ledger and remaining backlog

**Last reviewed: 2026-11-17.** Source: code merged into `master`, GitHub PRs and [green baseline CI at SHA `6edcfdad0c1691dc3771d4b5c63314ec4ec9883f`](https://github.com/DenaSon/Denapax/actions/runs/43726671634), plus the product-gallery PR. **Code complete ≠ staged ≠ production-deployed ≠ merchant-settled ≠ release-approved.** This ledger supersedes earlier Telegram-era scope/progress lists. No claim is made that a live web migration, real SMS, real gateway payment or production restore was run.

## Payment operating mode — owner decision (2026-11-16)

The next release uses **manual card-to-card transfer and private receipt upload as primary while `ZARINPAL_ENABLED=false`**. Approved staff check bank credit in `/admin/payments` before confirming the order. The Zarinpal code remains intact, and configuring an activated merchant ID plus `ZARINPAL_ENABLED=true` after real acceptance restores online-primary / manual-fallback UX without a code change. This is an operating-mode decision and must not be mistaken for proof of deployment or complete P0 release gates. [Rollout guide](45-manual-receipt-first-rollout.md).

## Completed in repository (merged, automated CI evidence)

| Workstream | Delivered scope (implementation only) | Evidence |
| --- | --- | --- |
| Phase 1 — web identity / Telegram retirement | Independent customer authentication guard, Iran mobile + hashed OTP with limits, fail-closed SMS transport, guest/persistent cart merge, web storefront integration, Telegram runtime and Nutgram removed, web notification persistence, irreversible guarded schema cutover | [PR #77](https://github.com/DenaSon/Denapax/pull/77), `f294a4cde42c6a96023f9a249f190bc04fd3ea79`, [docs/35](35-web-phase1-cutover.md) |
| Phase 2 — storefront + checkout | Persian catalog search, guest/cart operations, saved addresses, shipping quote with weight, session-bound price fingerprint, idempotent order snapshot and customer-scoped order confirmation | [PR #78](https://github.com/DenaSon/Denapax/pull/78), `18a1e5d4c719ec40796a9b85ce586884534e8fe0`, [docs/36](36-web-phase2-checkout.md) |
| Phase 3 — payments + orders | Zarinpal v4 request/verify adapter and attempt ledger; online-first order page; fallback manual receipt upload/private retrieval/approval/rejection; order list, shipment tracking and customer web inbox | [PR #79](https://github.com/DenaSon/Denapax/pull/79), `181793753b20dfd1cc4a0d7a5f41d306c433e589`, [docs/37](37-web-phase3-payments.md) |
| Phase 4A — financial exception safety | No automatic timeout-based retry, inquiry and protected operator reconciliation, fenced verification, no competing manual payment; **real provider acceptance pending** | [PR #82](https://github.com/DenaSon/Denapax/pull/82), `7d9e93ce78a3cdbd4c9e19e257b0c18c6d79a7bc`, [docs/40](40-web-phase4a-gateway-reconciliation.md) |
| Phase 4B — mobile-first purchase UX | Inline one-screen quote and confirmation, large +/- cart actions, one-form variant selection, simplified SMS code and recipient form; **physical mobile QA pending** | [PR #83](https://github.com/DenaSon/Denapax/pull/83), `3ca052738bb5397f658306669f67ecb37375d55f`, [docs/41](41-web-phase4b-mobile-checkout-ux.md) |
| Preserved staff foundations | Staff Fortify/RBAC, catalog/prices, shipping configuration, immutable order amounts, receipt review, fulfillment and database queue | Existing code + CI; production-like acceptance remains pending |\n| Web inbox/outbox reliability | Atomic event claims, leased dispatch recovery, unique jobs, bounded retries, terminal failure visibility and authorized manual retry | [PR #126](https://github.com/DenaSon/Denapax/pull/126), merge `12283896d7f8a45c674ed91f5f9c8a81e935f374`, [CI](https://github.com/DenaSon/Denapax/actions/runs/44049700399): 121 tests / 851 assertions |

**Verified master baseline before gallery PR:** 84 passed / 580 assertions; Composer production dependency audit and validation, Laravel Pint and Vite build green. Test runner uses SQLite; **staging MySQL and real provider behavior remain unverified**.

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

## Storefront UI/UX polish following Phase 4B (PR #85; code only)

- Corrected Tailwind 4 `source(none)` to compile utilities in customer checkout/cart/address/login/order Blade pages. Added an RTL forest/stone design system, shared header/cards/fields/CTA, Vazirmatn, responsive focus/touch rules and a three-step cart→delivery→payment indicator.
- Payment center now emphasizes the total and warns explicitly about ambiguous gateway results. The order list eagerly loads attempts to show `نتیجه پرداخت در حال بررسی`, with progressive one-tap copy and pending-form feedback where supported.
- Server-side checkout idempotency, verified OTP, gateway reconciliation and private receipts remain unchanged. See [UI polish handoff](42-web-storefront-ui-ux-polish.md).
- **Still pending:** actual mobile-browser visual/a11y acceptance, real merchant/OTP providers and staging/production validation; no screenshots, bank settlements or launch approval are claimed from code CI.

## Admin operational UX polish (PR #86; code-level only)

- Staff panel gets a responsive forest/stone shell, permission-gated unified navigation, operational dashboard and readable Persian order/payment/gateway status labels.
- Responsive orders list, clearer next authorized action on order detail, receipt review linked to an individual receipt and bank reconciliation warnings; notifications report now correctly refers to **web** rather than retired Telegram.
- Financial service methods, permissions, private receipt URLs, gateway retry and checkout rules remain enforced on the server. See [admin UX handoff](43-admin-ui-ux-polish.md).
- **Still pending:** actual staff Android/iOS browser walkthrough and full production-like operations acceptance. Code tests do not prove go-live readiness.

## Mobile-first product gallery (code-only addition)

- Dedicated product permalink supports a responsive carousel: first gallery image as cover, thumbnail strip, arrows, mobile swipe, native full-screen dialog, pinch/double-tap zoom, keyboard/focus support and accurate image switching when selecting a variant with its own photo.
- Product editor already supplies gallery/variant images through Spatie; only public images appear. With JavaScript disabled, regular full-image links and the add-to-cart form still work. No new migration or payment change.
- Core regressions cover no-image/one-image/multiple-image and correct variant-to-photo mapping. [Gallery implementation and device QA](44-product-mobile-gallery.md).
- **Still required:** actually inspect iOS/Android gestures, performance/long names and screenshot-based UX sign-off before declaring mobile acceptance.

## P1 web inbox/outbox reliability — implemented in PR #126 (code-level)

- The dispatcher now locks each outbox event, creates/reuses its unique `web` notification log and claims it before dispatch. The query excludes sent/terminal-failed rows and future leases, so old failures cannot starve later events.
- Database queue jobs remain durable while workers are offline. A 10-minute lease recovers the commit-before-dispatch gap, and a seven-day Laravel `ShouldBeUnique` lock prevents repeated scheduler runs from amplifying an active job.
- The job records actual execution attempts, uses three bounded attempts with 30s / 120s backoff, and writes a terminal `failed` state plus a short last-error summary after exhaustion.
- Active staff with `orders.update` can explicitly retry a failed web notification from the admin report. The action is audited and preserves lifetime attempt history.
- Reuses the existing `notification_logs` fields and unique event/customer/channel constraint; no migration or new table. It is **web inbox only**, not SMS or Telegram.
- Automated evidence: 121 tests / 851 assertions, Composer audit/validation, Pint and Vite build all passed. [PR #126](https://github.com/DenaSon/Denapax/pull/126).
- **Still unverified:** production scheduler cadence, supervised `notifications,default` workers, worker-offline recovery and manual retry in the target runtime. Do not mark this operational acceptance complete from CI alone.

## Remaining P0 — block public web-sales launch

1. **Payment reconciliation live acceptance**: no-double-charge/inquiry/operator-review code is implemented in PR #82; verify this against an activated provider and bank panel, reconcile actual merchant settlements, document manual conflict/refund escalation and ensure no public activation on code CI alone.
2. **Real providers**: activated Zarinpal account, HTTPS callback and verified IRR/IRT handling with a small authorized payment; real SMS OTP delivery configured and tested.
3. **Security / E2E (#24)**: staging MySQL and real browser/device regression, payment callback duplicate/conflict/timeout, receipt rejection/retry, private file permissions, customer/staff isolation.
4. **Operations / resilience (#25)**: target-DB customer count, guarded irreversible migration rehearsal, MySQL and private receipt backups, isolated restore, Runflare HTTPS/secrets/debug validation, supervised queue/schedule and alerts, documented rollback.
5. **Real mobile/browser acceptance / business correctness**: Phase 4B + subsequent store UI polish improved checkout consistency; validate actual iOS/Android, keyboard/zoom, gateway-return/cookie expiry, approved shipping tariff/support details, product availability and oversell policy.
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

1. Read [AGENTS.md](../AGENTS.md), this ledger and [Phase 4 plan](38-web-phase4-release-readiness.md); verify the latest `master` SHA and CI.
2. Keep P0 launch blockers [#11](https://github.com/DenaSon/Denapax/issues/11), [#24](https://github.com/DenaSon/Denapax/issues/24), [#25](https://github.com/DenaSon/Denapax/issues/25) authoritative. The outbox code in #102 is merged, but production worker/scheduler acceptance is still part of #25.
3. The next focused code backlog item is [#103](https://github.com/DenaSon/Denapax/issues/103): enforce one shared sellability rule across public catalog and guest/customer cart quantity changes. Preserve decrement/removal for unavailable lines; do not add numeric stock reservation in this scope.
4. In parallel with code backlog, complete Phase 4A real bank/provider acceptance, Phase 4B physical mobile QA and Phase 4C MySQL/SMS/provider staging before any public-sales GO.
5. Keep tests focused, require green CI before merge, and do not claim a deployment or provider settlement without proof.

Whenever this ledger is updated, distinguish **implemented / unit-tested / staging-verified / production-verified / owner-approved**; never mark unchecked items completed from code inspection alone.
