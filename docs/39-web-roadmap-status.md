# Denapax — authoritative web delivery ledger and remaining backlog

**Last reviewed: 2026-11-17.** Current `master`: `16688997d7bcbdb5f84131e3f2a6cf5e1d089181`; [green master CI](https://github.com/DenaSon/Denapax/actions/runs/44076598005) passed with 135 tests / 1,002 assertions. **Code complete ≠ staged ≠ production-deployed ≠ merchant-settled ≠ release-approved.** The owner confirmed a real SMS.ir OTP delivery and successful customer login flow on the configured deployment; the exact deployment tier was not recorded. The owner does not yet have a Zarinpal merchant account, so live gateway acceptance is deferred and `ZARINPAL_ENABLED=false` remains in force. No live Zarinpal settlement, target-MySQL migration or isolated restore is claimed.

## Payment operating mode — owner decision (2026-11-16)

The next release uses **manual card-to-card transfer and private receipt upload as primary while `ZARINPAL_ENABLED=false`**. Approved staff check bank credit in `/admin/payments` before confirming the order. The Zarinpal code remains intact, and configuring an activated merchant ID plus `ZARINPAL_ENABLED=true` after real acceptance restores online-primary / manual-fallback UX without a code change. This is an operating-mode decision and must not be mistaken for proof of deployment or complete P0 release gates. [Rollout guide](45-manual-receipt-first-rollout.md).

## Zarinpal deferral — owner confirmation (2026-11-17)

The owner does not yet have a Zarinpal merchant account. Keep [#81](https://github.com/DenaSon/Denapax/issues/81) OPEN and `ZARINPAL_ENABLED=false`. This is a deferred optional gateway integration, not completed work. The approved interim payment mode remains **manual card-to-card transfer + private receipt + staff bank verification** as documented in [the manual-first rollout guide](45-manual-receipt-first-rollout.md). Resume #81 only after merchant onboarding; do not attempt live gateway calls or enable the flag before the full currency/callback/settlement acceptance.

## Completed in repository (merged, automated CI evidence)

| Workstream | Delivered scope (implementation only) | Evidence |
| --- | --- | --- |
| Phase 1 — web identity / Telegram retirement | Independent customer authentication guard, Iran mobile + hashed OTP with limits, fail-closed SMS transport, guest/persistent cart merge, web storefront integration, Telegram runtime and Nutgram removed, web notification persistence, irreversible guarded schema cutover | [PR #77](https://github.com/DenaSon/Denapax/pull/77), `f294a4cde42c6a96023f9a249f190bc04fd3ea79`, [docs/35](35-web-phase1-cutover.md) |
| Phase 2 — storefront + checkout | Persian catalog search, guest/cart operations, saved addresses, shipping quote with weight, session-bound price fingerprint, idempotent order snapshot and customer-scoped order confirmation | [PR #78](https://github.com/DenaSon/Denapax/pull/78), `18a1e5d4c719ec40796a9b85ce586884534e8fe0`, [docs/36](36-web-phase2-checkout.md) |
| Phase 3 — payments + orders | Zarinpal v4 request/verify adapter and attempt ledger; online-first order page; fallback manual receipt upload/private retrieval/approval/rejection; order list, shipment tracking and customer web inbox | [PR #79](https://github.com/DenaSon/Denapax/pull/79), `181793753b20dfd1cc4a0d7a5f41d306c433e589`, [docs/37](37-web-phase3-payments.md) |
| Phase 4A — financial exception safety | No automatic timeout-based retry, inquiry and protected operator reconciliation, fenced verification, no competing manual payment; **Zarinpal live acceptance deferred until merchant account is available** | [PR #82](https://github.com/DenaSon/Denapax/pull/82), `7d9e93ce78a3cdbd4c9e19e257b0c18c6d79a7bc`, [docs/40](40-web-phase4a-gateway-reconciliation.md) |
| Phase 4B — mobile-first purchase UX | Inline one-screen quote and confirmation, large +/- cart actions, one-form variant selection, simplified SMS code and recipient form; **physical mobile QA pending** | [PR #83](https://github.com/DenaSon/Denapax/pull/83), `3ca052738bb5397f658306669f67ecb37375d55f`, [docs/41](41-web-phase4b-mobile-checkout-ux.md) |
| Preserved staff foundations | Staff Fortify/RBAC, catalog/prices, shipping configuration, immutable order amounts, receipt review, fulfillment and database queue | Existing code + CI; production-like acceptance remains pending |
| Shared catalog/cart sellability | Central variant availability/price/weight and product/category sellability; server-side guest/customer quantity-increase guard; safe decrement/removal | [PR #128](https://github.com/DenaSon/Denapax/pull/128), merge `ea72ef6c1bc84f242a9ab13ba6f10c6cdd34b789`, [CI](https://github.com/DenaSon/Denapax/actions/runs/44051594401): 125 tests / 918 assertions |
| Web inbox/outbox reliability | Atomic event claims, leased dispatch recovery, unique jobs, bounded retries, terminal failure visibility and authorized manual retry | [PR #126](https://github.com/DenaSon/Denapax/pull/126), merge `12283896d7f8a45c674ed91f5f9c8a81e935f374`, [CI](https://github.com/DenaSon/Denapax/actions/runs/44049700399): 121 tests / 851 assertions |

**Historical pre-gallery baseline:** 84 passed / 580 assertions. Current master evidence is recorded at the top of this ledger. Test runner uses SQLite; **staging MySQL and real provider behavior remain unverified**.

## OTP expiry and resend UX — implemented (2026-11-17)

- [PR #133](https://github.com/DenaSon/Denapax/pull/133) adds a visible server-timed five-minute OTP validity countdown and 60-second same-number resend countdown.
- A changed destination number remains available; the UI countdown is guidance only and does not weaken server-side expiry, attempt limits or IP/mobile throttling.
- Native POST forms remain functional without JavaScript. Focused feature coverage is included in the current master baseline (135 tests / 1,002 assertions).
- Issue [#113](https://github.com/DenaSon/Denapax/issues/113) is closed. Real-device acceptance remains part of the wider Phase 4B checklist.

## P1 SMS.ir OTP — implementation and owner operational acceptance complete

- Uses the official `ipe/smsir-php` SDK through an adapter behind the existing `CustomerOtpSender` boundary; `CustomerOtpLogin` remains provider-independent.
- Package auto-discovery is disabled because the SDK provider reads `env()` directly. The app binds the SDK from `config/services.php`, with a fixed HTTPS host, finite connect/request timeouts and redirects disabled.
- Owner-confirmed Verify configuration: template ID `967976`, panel token `#HOURS#`, SDK parameter name `HOURS`.
- Verify template ID and parameter name are config-driven. Success requires API `status = 1` and a positive `data.messageId`; exceptions and malformed/non-success responses fail closed.
- The legacy plaintext OTP log driver has been removed. Existing OTP throttling, cooldown, expiry, attempt limit, hash storage and replay prevention are preserved.
- Automated evidence: 132 tests / 945 assertions; Composer audit/validation, Pint and Vite build passed. [PR #130](https://github.com/DenaSon/Denapax/pull/130), template alignment [PR #131](https://github.com/DenaSon/Denapax/pull/131), [latest master CI](https://github.com/DenaSon/Denapax/actions/runs/44066864675).
- **Owner-reported operational acceptance (2026-11-17):** the real test OTP was delivered, the received code was accepted and the customer flow was tested through completion. The exact deployment tier (staging vs production) was not recorded. Issue [#124](https://github.com/DenaSon/Denapax/issues/124) is closed after owner acceptance. See [setup and acceptance record](49-smsir-otp-integration.md).

## What is implemented and remaining external acceptance

| Component | In code | Real environment / release evidence |
| --- | --- | --- |
| OTP authentication | Official SMS.ir Verify adapter, config-cache-safe SDK binding, template `967976` / parameter `HOURS`, bounded timeouts; implementation in PRs #130/#131 | Owner-confirmed real OTP delivery and successful customer login flow; exact deployment tier not recorded; #124 closed |
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

## P2 cart/catalog sellability consistency — implemented in PR #128

- Added `ProductVariant::availableForSale()` for the variant's availability flag, positive IRR price and positive shipping weight; `ProductVariant::sellable()` additionally requires a publicly published product and active category.
- `Product::publiclyOrderable()`, homepage, product permalink, sitemap and `BrowseCatalog` now use the same valid variant-level requirements. A zero-weight variant is no longer advertised as purchasable.
- Guest session cart and authenticated customer cart both reject a quantity increase if the existing line is no longer sellable. Direct Livewire actions and native `PATCH /cart/items/{variantId}` fallback are covered for both customer types.
- Customers may still decrease or remove unavailable lines. Cart line status and checkout validation use the same model-level sellability predicate.
- Admin cannot publish a product without an active category and at least one variant with valid availability, price and weight.
- No migration and no numeric stock reservation; only the existing `is_available` flag is used.
- CI evidence: 125 tests / 918 assertions; Composer audit/validation, Pint and Vite build passed. [PR #128](https://github.com/DenaSon/Denapax/pull/128).
- This is code-level/automated-test completion only. It does not replace the P0 MySQL staging, provider, real browser or production acceptance gates.

## Remaining P0 — block public web-sales launch

1. **Zarinpal reconciliation**: no-double-charge/inquiry/operator-review code is implemented in PR #82. Live merchant verification, currency contract and bank-panel reconciliation are **deferred** because the owner does not yet have a merchant account. Keep #81 open and the gateway disabled. The manual receipt-first path still requires independent bank-proof, private-media and staff-review acceptance.
2. **Real providers**: SMS.ir OTP delivery and successful customer login have owner-reported acceptance; the exact deployment tier is not recorded. Zarinpal onboarding and authorized live payment will resume after a merchant account is available.
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

## P2 UX backlog — remaining code work

The following issues are open implementation work and can proceed independently of deferred merchant onboarding and production operations:

- **Product purchase and discovery:** [#108](https://github.com/DenaSon/Denapax/issues/108) reactive product-detail add-to-cart/mobile purchase dock; [#117](https://github.com/DenaSon/Denapax/issues/117) product-preview click/navigation conflict; [#118](https://github.com/DenaSon/Denapax/issues/118) submit-driven search and Persian walnut matching; [#116](https://github.com/DenaSon/Denapax/issues/116) gallery sizing/source-resolution audit.
- **Checkout/cart:** [#110](https://github.com/DenaSon/Denapax/issues/110) complete saved-address details; [#121](https://github.com/DenaSon/Denapax/issues/121) fixed mobile cart checkout dock; [#115](https://github.com/DenaSon/Denapax/issues/115) mobile cart-count badge.
- **Admin:** [#111](https://github.com/DenaSon/Denapax/issues/111) responsive product/category lists; [#114](https://github.com/DenaSon/Denapax/issues/114) upload previews/progress and save feedback.
- **Brand and trust:** [#112](https://github.com/DenaSon/Denapax/issues/112) support/policy destinations (policy copy requires owner approval); [#119](https://github.com/DenaSon/Denapax/issues/119) consistent wordmark; [#120](https://github.com/DenaSon/Denapax/issues/120) hero copy; [#122](https://github.com/DenaSon/Denapax/issues/122) approved story copy.

[Issue #106](https://github.com/DenaSon/Denapax/issues/106) is resolved by this handoff refresh; close it after this documentation PR merges. [#70](https://github.com/DenaSon/Denapax/issues/70) and [#107](https://github.com/DenaSon/Denapax/issues/107) remain explicitly Post-MVP and should not be pulled into the current release.

## Explicitly retired / excluded

The Telegram customer bot, Nutgram dependency, Telegram webhook, chat-group alerts, Telegram identity columns and Telegram-first order UX are retired. Do not reactivate them. Historical docs/01–34 and old issue histories explain decisions from that period but are **not the active web specification**. There is no Redis or Horizon launch requirement; database queue is the approved implementation.

## Next start point in a new conversation

1. Read [AGENTS.md](../AGENTS.md), this ledger and [Phase 4 plan](38-web-phase4-release-readiness.md); verify the latest `master` SHA and CI.
2. Keep P0 launch blockers [#11](https://github.com/DenaSon/Denapax/issues/11), [#24](https://github.com/DenaSon/Denapax/issues/24), [#25](https://github.com/DenaSon/Denapax/issues/25) authoritative. The outbox code in #102 is merged, but production worker/scheduler acceptance is still part of #25.
3. Issue [#103](https://github.com/DenaSon/Denapax/issues/103) is complete in [PR #128](https://github.com/DenaSon/Denapax/pull/128). SMS.ir implementation and owner-reported OTP acceptance are complete in [PRs #130/#131](https://github.com/DenaSon/Denapax/pull/130); [#124](https://github.com/DenaSon/Denapax/issues/124) is closed. Keep the repository default fail-closed; deployment activation is controlled by the environment.
4. Continue the P2 storefront/admin backlog in focused PRs, starting with product-detail purchase UX [#108](https://github.com/DenaSon/Denapax/issues/108). Keep Zarinpal #81 deferred until merchant onboarding; independently complete real-device QA, target-MySQL migration, manual receipt-first E2E, backup/restore and operations checks before any public-sales GO.
5. Keep tests focused, require green CI before merge, and do not claim a deployment or provider settlement without proof.

Whenever this ledger is updated, distinguish **implemented / unit-tested / staging-verified / production-verified / owner-approved**; never mark unchecked items completed from code inspection alone.
