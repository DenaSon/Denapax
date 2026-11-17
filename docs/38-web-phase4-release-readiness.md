# Denapax — Web Phase 4: release readiness, payment safety and mobile UX

**Status: IN PROGRESS / LAUNCH NO-GO — 2026-11-17.** Phase 4A [PR #82](https://github.com/DenaSon/Denapax/pull/82) and 4B [PR #83](https://github.com/DenaSon/Denapax/pull/83) are merged. Current verified `master` at `adc85cf905acb45212ea5c7991aff34438c441e8` with [GitHub CI](https://github.com/DenaSon/Denapax/actions/runs/44066864675) green: 132 tests / 945 assertions, Composer audit/validation, Pint and Vite. The owner has separately confirmed real SMS.ir OTP delivery and successful customer login flow; the exact deployment tier was not recorded. **Live Zarinpal settlement, target-MySQL migration, isolated restore and real-device browser acceptance remain unverified; Phase 4C/4D and the overall release gate remain pending.**

This is the current **release playbook**, replacing Telegram-specific procedures in historical `docs/15` and `docs/16`. Existing release issues [#11](https://github.com/DenaSon/Denapax/issues/11), [#24](https://github.com/DenaSon/Denapax/issues/24) and [#25](https://github.com/DenaSon/Denapax/issues/25) remain OPEN. Keep them open until evidence and owner go/no-go exist.

## Mission and boundaries

Launch a low-friction Persian RTL **web-only** storefront: catalog → cart → OTP login only when needed → delivery address → accurate shipping quote → one confirmation → primary Zarinpal payment or secondary manual transfer/receipt → status/tracking in the same order page. Never reintroduce Telegram or its bot/webhook/notification dependencies. No Laravel Horizon/Redis prerequisite: `QUEUE_CONNECTION=database` with supervised `notifications,default` queue workers is the agreed design.

**Code implemented in Phases 1–3 plus 4A/4B is not a deployed release.** Do not mark tasks completed based on this plan. If the store cannot meet a P0 safety gate, do not accept public orders or enable Zarinpal.

## Priority P0 — must be resolved before launch

### P0-A: gateway money-safety and reconciliation (code + real verification)

- [ ] Review the Zarinpal v4 integration and **confirm the merchant account's actual IRR/IRT contract, API host, callback and sandbox behavior against current official merchant documentation**. Code currently sends integer IRR and expects the v4 response; do not assume every merchant account matches without a test.
- [ ] Obtain and configure an **activated** merchant identifier securely; enable only with publicly reachable HTTPS callback `/payments/zarinpal/callback`, correct `APP_URL`/proxy scheme and no production debug/secret exposure. Never place credentials in the repository or issues.
- [ ] Perform an authorized low-value end-to-end payment: created order → Zarinpal page → bank payment → server verification → exactly one `approved/confirmed` transition → reference ID → admin/ledger reconciliation. Independently match amount and transaction in the merchant panel.
- [x] **Code mitigation (PR #82):** removed age-based auto-expiry/retry. Unresolved attempt now blocks further online/manual payment; provider inquiry and authorized, audited `cleared_unpaid` clearance are implemented, including a 1-hour holding period for missing Authority. Added late callback conflict handling and verification-token fencing. **Do not treat this as real bank validation.**
- [x] **Code interface (PR #82):** gateway exception queue at `/admin/payments/gateway` with strict `payments.view`/`payments.review`, provider inquiry, recorded actor/note and explicit `requires_review` hold. **Still pending:** operational verification and bank reconciliation of actual unmatched provider settlements; refund/escalation procedures.
- [ ] Ensure a verified payment event is durable under restarts/crashes/retry/reentrant callbacks; test duplicate callbacks, `code=101`, failed gateway request, 5xx/timeout, lost customer session, page reload, repeated button presses, delayed old Authority and simultaneous receipt upload. Do not cause duplicate charge or fulfillment.
- [ ] Put a safe "checking payment / do not pay again" state in the order UI for ambiguous outcomes, with clear support contact/recovery instructions. A missing callback must not strand an already-paid user.

### P0-B: OTP and web customer access

- [x] SMS.ir Verify adapter is configured and owner-confirmed: real test OTP delivered and customer login/continuation flow completed on the configured deployment (2026-11-17). Template `967976`, panel token `#HOURS#`, SDK parameter `HOURS`. Exact deployment tier was not recorded; see [SMS.ir acceptance record](49-smsir-otp-integration.md).
- [ ] Complete negative/security OTP checks on the target environment: wrong-code attempt limit, five-minute expiry, 60-second resend cooldown, IP/mobile throttling, provider timeout/failure behavior and absence of OTP/API-key data in logs.
- [ ] Verify customer session/login redirect after the Zarinpal return when cookies expire or the payment page opens on another device. Successful gateway verification must remain server-side and the user must reach their own order after reauthentication.
- [ ] Confirm staff Fortify web guard, `customer` guard separation, staff RBAC, rate-limits, no public staff registration and no customer cross-access.

### P0-C: migration, backup, restore and run-time operations

- [ ] Identify the **real target** MySQL database; confirm the actual customer row count. The irreversible `2026_11_15_150000_convert_customers_to_web.php` migration intentionally refuses if customers exist. An owner statement about an empty database is not sufficient evidence for target DB. **Never `migrate:fresh` in production.**
- [ ] Take coordinated, verified MySQL + `storage/app/private/receipts` backups; restore both into an **isolated** environment and validate order/receipt mapping, permissions and recovery time. Document backup location/retention/restore operator without posting personal data.
- [ ] Execute the web-cutover and additive gateway-attempt migration on staging MySQL first; validate existing staff, catalog, shipping, settings, private file ACL, indexes and schema. Capture sanitized before/after migration evidence. Have a tested full restore plan (web-cutover down-migration is intentionally unavailable).
- [ ] Check Runflare environment: HTTPS, `APP_DEBUG=false`, correct `APP_URL`/trusted proxies, `APP_KEY`, `config:cache`, writable private storage and security headers. Secure staff/merchant/SMS secrets in environment management, not source.
- [ ] Ensure a continuously supervised Laravel database queue worker for `notifications,default` and minute scheduler (`denapax:notifications:dispatch`). Demonstrate event processing, retries, monitoring of failed jobs and actionable alerting; avoid presenting a one-time zero queue length as proof of supervision.
- [ ] Verify actual card holder/number, support contact, delivery tariff, product prices/weights and only published/available products. Initial demo/seeder shipping rates are **not** business-approved production tariffs.
- [ ] Decide a **real availability/stock policy**: current data has only `is_available`, not numeric stock reservation. If limited inventory is sold, define and validate oversell handling before enabling orders.
- [ ] Document and exercise a release rollback/restore decision and explicit **GO / NO-GO**, owner/operator sign-off and support escalation. Do not close #11/#24/#25 from CI alone.

### P0-D: real-browser UX acceptance

- [ ] Run a full checkout on real Android/iOS devices at narrow/mobile and desktop sizes: variant choice, add/update/remove cart, OTP, address entry with Persian digits, shipping, order confirmation, bank redirect, return and manual fallback. Check RTL layout, readability, image weight/loading, tap targets, browser Back and double taps.
- [x] **Code/UX change in PR #83:** the main checkout combines selected delivery address and server-verified shipping quote with direct order confirmation; minimal primary actions, toman totals and separate review-page bypass while retaining the session fingerprint. **Real-device, customer observation and browser acceptance are not yet complete**.
- [ ] Show payment outcome and next action in plain Persian. Confirm errors/retry paths work when OTP SMS is delayed, price changes, product becomes unavailable, gateway cancels/errors, receipt is rejected, network drops or order is already paid.
- [ ] Check WCAG basics: form labels, validation/error association, keyboard focus and navigation, confirmation announcements, color contrast, accessible buttons and no reliance on color alone.
- [ ] Measure the real mobile purchase journey: steps/taps from product to successful payment, abandonment/confusing moments and time to recovery. Keep improvements focused rather than adding decorative animations or extra forms.

## Release verification matrix

| Journey | Evidence required (not yet recorded) | Status |
| --- | --- | --- |
| Mobile public catalog/search/product/variant/cart | Browser device checklist with screenshots or sanitized notes | Pending |
| Mobile OTP/login/saved address | Owner-confirmed real SMS.ir OTP delivery and successful login; target-MySQL/browser/device and negative-case checks still required | Partially accepted |
| Shipping quote/price stale replay/idempotency | Staging MySQL test + browser confirmation, price snapshot | Pending |
| Zarinpal success and merchant reconciliation | Real authorized payment; matched amount, Authority/ref and panel transaction | Pending |
| Failed/timeout/lost callback/duplicate/retry | Injected error test and operator reconciliation proof | Pending |
| Card transfer / rejected receipt / resubmit / approval | Controlled bank proof and admin review, private media ACL | Pending |
| Customer isolation, staff RBAC, refunds/cancellations | Security/negative regression and resolved findings | Pending |
| Fulfillment, delivery and web inbox | Worker/scheduler logs plus customer UI and tracking | Pending |
| Migration + consistent backup/isolated restore | Dated operator evidence; rollback rehearsal | Pending |
| Production HTTPS, configuration and monitoring | Runtime checks and alerts | Pending |
| Owner acceptance and release decision | Named go/no-go record without secrets | Pending |

## Suggested execution order (one clean PR per code change)

1. **4A — code implemented in PR #82; release gate pending:** conservative no-double-payment state machine, inquiry, operator exception screen, late callback/fencing tests. Verify actual merchant account API/currency/refund/reconciliation in Phase 4C before any public activation. See [technical runbook](40-web-phase4a-gateway-reconciliation.md).
2. **4B — mobile UX code implemented in PR #83; device QA pending:** one-page checkout, preselected address/shipping quote, one-tap cart quantity, concise OTP and product variants. Validate RTL/a11y/real-device/browser Back/Forward and payment recovery before approval. See [Phase 4B handoff](41-web-phase4b-mobile-checkout-ux.md).
3. **4C — provider/staging integration:** SMS.ir OTP delivery is owner-accepted; complete activated Zarinpal credentials, actual merchant-currency/callback/settlement acceptance, real shipping/contact settings and target-MySQL migration rehearsal.
4. **4D — reliability and launch:** worker/schedule, alerting, backups + restore, security review, real web test order, release checklist, owner/operator go/no-go.

A Phase 4 **code PR being merged is not itself a production GO**. Each gate needs dates, environment (no secrets), commit, scenario, result and operator/owner validation.

## After launch (not mandatory unless scope demands)

- Numeric stock and stock reservation/oversell strategy, if inventory constraints matter.
- Variant price change history/audit: existing [#70](https://github.com/DenaSon/Denapax/issues/70).
- Optional outbound transactional SMS for order/payment/shipping (web inbox already exists).
- Conversion analytics, lightweight checkout A/B review and accessibility/performance improvements beyond launch baseline.
- Discounts, richer shipping zones, customer self-service options, additional payment providers, returns/refund automation after explicit product decisions.

See [current delivery ledger](39-web-roadmap-status.md), [Phase 3 payment design](37-web-phase3-payments.md), [AGENTS.md](../AGENTS.md) and release issues #11/#24/#25.
