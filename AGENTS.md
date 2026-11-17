# Denapax — web-only developer and project handoff

**Updated: 2026-11-17. Start here: [delivery ledger](docs/39-web-roadmap-status.md) and [Web Phase 4 release gates](docs/38-web-phase4-release-readiness.md).**

## Project and immutable decisions

A Persian RTL web store for Dena souvenirs, dried fruit and local products. The owner authorized a full web-only customer refactor. Telegram sales bot, Nutgram, Telegram webhook/chat alerts/identity and bot-specific UI are **retired**. Do not restore them. Staff/admin login remains separate. PHP 8.4, Laravel 13, Livewire 4, Mary UI 2, Blade, Tailwind 4, MySQL; database queues without Redis/Horizon.

**State evidence:** Web Phases 1–3, 4A and 4B are merged. Current `master` is `c1468eed7aacd12ac95a56bf23ba3b93612a9ef5`; [latest master CI](https://github.com/DenaSon/Denapax/actions/runs/44070505820) passed with 132 tests / 945 assertions, Composer audit/validation, Pint and Vite. The owner confirmed real SMS.ir OTP delivery and successful customer login flow on the configured deployment; the exact deployment tier was not recorded. **No live Zarinpal settlement, target-MySQL migration or isolated restore is claimed. Public release gates remain open.**

## What is implemented

- Customer-only `customer` session guard and verified normalized Iranian mobile OTP; staff `web` Fortify guard/RBAC separate. OTP is hashed, time/attempt/IP/mobile limited. The official SMS.ir Verify adapter is implemented in PR #130 and template-aligned in PR #131; the owner confirmed real OTP delivery and successful customer login. The exact deployment tier was not recorded. The repository default remains `CUSTOMER_SMS_DRIVER=disabled`; the configured deployment uses the verified SMS.ir setup.
- Public search/product/variant catalog, session guest and DB customer cart, merge after login; saved customer-owned addresses and **Phase 4B single-page** validated checkout (weight delivery quote, cart/price fingerprint, immutable order snapshots, idempotent checkout key). Product variants use one shared add-to-cart form, cart quantities have one-tap +/- controls, OTP and address forms are mobile-focused. The product permalink now includes a progressive responsive gallery with native image links, thumbnail carousel, touch swipe, native zoom dialog and variant-specific image switching. See [gallery handoff](docs/44-product-mobile-gallery.md); real device QA is still required.
- Zarinpal v4 transport and attempt ledger are implemented but remain disabled pending merchant onboarding; manual card-to-card receipt with protected private storage and authorized admin review is the active payment mode. Order list/detail, shipping tracking and customer **web inbox UI are implemented**. Transactional order SMS **is not**.
- Staff admin catalogs/settings/orders/payment review/fulfillment, order state history and a reliable web inbox/outbox lifecycle: atomic dispatch claims, unique jobs, bounded retries, terminal failure reporting and authorized manual retry (PR #126). Use `QUEUE_CONNECTION=database`, supervised `notifications,default` workers and one-minute `denapax:notifications:dispatch`.
- Shared catalog/cart sellability contract (PR #128): positive availability, price and shipping weight; active product/category; server-enforced quantity increases for guest and customer carts. Decrement/removal remain available for unsellable lines. No numeric stock reservation.
- All financial values stored as integer **IRR**, displayed as exact **toman**. Catalog records have `is_available` but **no numeric stock reservations**. One nationwide weight-based shipping tariff.

## Repository governance and staff authorization — 2026-11-17

- The active `master` ruleset requires pull requests, the `test` check with strict/up-to-date branches, blocks deletion and force-push, and has zero required approvals for the single-maintainer workflow. The bypass list is empty.
- PR #123 stabilizes CI with the committed npm lockfile, Node.js 22 and deterministic `npm ci` installs.
- PR #125 registers `EnsureActiveStaff` as Livewire 4 persistent middleware. A staff account deactivated while an admin page remains open is denied on the next Livewire update; regression coverage passed (116 tests / 812 assertions).
- Issues #101, #104 and #105 are closed. Keep route-level authentication, role/permission checks and component/action authorization in place.

## Web inbox/outbox reliability — 2026-11-17

- PR #126 makes web inbox delivery recoverable without a schema migration: atomic event claims, a 10-minute dispatch lease, a seven-day `ShouldBeUnique` lock, real attempt counts, three bounded queue attempts (30s / 120s backoff), and terminal `failed` state.
- Terminal failures are excluded from automatic scheduler scans and can be retried only by active staff with `orders.update`; retries are activity-logged and preserve lifetime attempts.
- Scope is strictly the customer web inbox. Do not add transactional/order SMS to this outbox scope or revive Telegram; customer OTP delivery is a separate integration (PR #130). CI passed 121 tests / 851 assertions, Composer audit/validation, Pint and Vite build.
- CI does not prove a production worker or scheduler is running. Verify the database queue worker and one-minute scheduler in the target environment; the operational release gate remains open until observed.

## Cart/catalog sellability consistency — 2026-11-17

- PR #128 centralizes variant-level `availableForSale` (available flag, positive price, positive shipping weight) and full `sellable` (also active product and category) predicates.
- Homepage, product permalink, sitemap and `BrowseCatalog` now exclude invalid-weight variants/products and only expose variants that satisfy the same variant-level rule.
- Guest session carts and authenticated database carts enforce the complete sellability rule on every quantity increase, including direct Livewire actions and native PATCH fallback. Decrement and removal are intentionally permitted after a product becomes unavailable.
- Cart line availability feedback and checkout validation use the model-level predicate. Admin product publishing requires an active category and at least one variant with valid availability, price and weight.
- No schema change and no numeric stock reservation; the catalog still uses the existing `is_available` flag.
- CI passed: 125 tests / 918 assertions, Composer audit/validation, Pint and Vite build. [PR #128](https://github.com/DenaSon/Denapax/pull/128), merge `ea72ef6c1bc84f242a9ab13ba6f10c6cdd34b789`, [CI](https://github.com/DenaSon/Denapax/actions/runs/44051594401).

## Critical safety / release invariants

- Browser callback `Authority` / `Status` is untrusted: only provider server-to-server verification of saved authority + exact order amount can confirm payment. Receipt upload is **not** bank proof; authorized human verification is required.
- **Phase 4A money safety implemented in [PR #82](https://github.com/DenaSon/Denapax/pull/82):** the former 45-minute auto-expiry/retry loophole is removed. Any unknown/expired/failed gateway attempt blocks further online/manual payment until evidence-backed operator `cleared_unpaid` decision. Provider inquiry, auditable admin exception queue and fenced verification leases were added. **Real merchant/bank reconciliation and actual settlement acceptance are still unverified**; do not enable public gateway payments on code CI alone. See [Phase 4A runbook](docs/40-web-phase4a-gateway-reconciliation.md).
- Data ownership: customer must not see other customers' addresses/orders/receipt images/inbox. Staff permissions must remain server-enforced. All receipt files remain outside the public symlink and should have no public URLs.
- The web identity migration `2026_11_15_150000_convert_customers_to_web.php` refuses nonempty `customers` and irreversibly drops Telegram schema. Verify target MySQL first. Consistent MySQL + private media backup and isolated restore are mandatory. **Never** `migrate:fresh` in production.
- `ZARINPAL_ENABLED=false` and `CUSTOMER_SMS_DRIVER=disabled` are the safe repository defaults. SMS.ir OTP delivery/login have owner-reported operational acceptance; keep its API key in deployment secrets. Zarinpal remains disabled because the owner does not yet have a merchant account; activate it only after merchant onboarding, IRR/IRT contract confirmation, HTTPS callback and an authorized live settlement test. Never put provider secrets in code, logs, issues or chat.
- Green GitHub CI (SQLite, mock HTTP) does not establish MySQL staging correctness, production runtime, bank settlement, RTL browser usability or recoverability.
- No public launch/GO or closing release issues without formal operator/owner evidence. Open [#11](https://github.com/DenaSon/Denapax/issues/11), [#24](https://github.com/DenaSon/Denapax/issues/24), [#25](https://github.com/DenaSon/Denapax/issues/25). Post-MVP price history [#70](https://github.com/DenaSon/Denapax/issues/70) remains open.

## SPA-like web UX — 2026-11-16

Livewire 4 SPA-like migration [#95](https://github.com/DenaSon/Denapax/pull/95)–[#99](https://github.com/DenaSon/Denapax/pull/99): shared public `resources/js/storefront.js` (Livewire-owned Alpine, navigation-safe gallery/copy), safe `wire:navigate` GET links, reactive `App\\Livewire\\Store\\CartItems` and `CartCount`, server-validated event-driven quick add, debounced public SSR search, secure `AddressPicker`/`AddressEditor`/`AddressList` built on existing `StoreCart`/`SaveCustomerAddress` services, and offline/focus feedback. **Keep native secure POST flows** for OTP, idempotent order confirmation, gateway and private receipts, plus all no-JS form routes. Do not move money or identity trust into Alpine. Runflare release/actual browser acceptance is separate; see [SPA handoff](docs/48-livewire-spa-upgrade.md).

## Storefront UX refresh — 2026-11-16

Three scoped PRs [#92](https://github.com/DenaSon/Denapax/pull/92), [#93](https://github.com/DenaSon/Denapax/pull/93) and [#94](https://github.com/DenaSon/Denapax/pull/94): three editorial **landing** chapter rail entries versus three independent **checkout** stages; consistent pointer/focus affordances; premium restrained photo-first design; no arbitrary featured product; and direct selected-variant + quantity POST from the native product-preview dialog to the existing CSRF-protected `cart.add` endpoint. Cart count works for guests and authenticated customers without creating empty persistent carts. Production device testing and locally optimized photo remain open. See [handoff and QA](docs/47-landing-ui-ux-polish.md).

## Payment-mode decision — 2026-11-17

Until the owner obtains an active Zarinpal merchant ID, **card-to-card + private receipt + authorized bank verification is the primary customer checkout payment UI**. Preserve the integrated gateway behind `ZARINPAL_ENABLED=false`; when enabled with a valid merchant ID, online payment becomes primary and receipt upload remains fallback. This is a presentation/configuration change only; preserve all unresolved-attempt bank safeguards and private receipts. SMS OTP has owner-reported operational acceptance, but the exact deployment tier is not recorded; receipts still require staff bank verification. See [manual-first payment operation](docs/45-manual-receipt-first-rollout.md).

## SMS.ir customer OTP — owner-confirmed operational acceptance

- PR #130 adds the official `ipe/smsir-php` Verify adapter; PR #131 aligns the approved Verify template ID `967976` and SDK parameter `HOURS`.
- The owner confirmed a real test OTP was delivered, the code was accepted and the customer flow completed. The exact deployment tier (staging vs production) was not recorded; this is owner-reported acceptance, not independent production certification.
- Issue [#124](https://github.com/DenaSon/Denapax/issues/124) is closed after that acceptance. Keep repository defaults fail-closed and store the SMS.ir API key only in deployment secrets. See [acceptance guide](docs/49-smsir-otp-integration.md).

## Next code backlog after SMS.ir acceptance

Issue [#103](https://github.com/DenaSon/Denapax/issues/103) is implemented and closed by PR #128. Issue [#124](https://github.com/DenaSon/Denapax/issues/124) is closed after owner-confirmed OTP delivery/login; PR #132 records the acceptance. The next focused UX improvement is [#113](https://github.com/DenaSon/Denapax/issues/113), visible OTP expiry and resend feedback. Keep the server-side cooldown, expiry and attempt limits authoritative.

## Next work: Web Phase 4

1. **4A — code implemented, live Zarinpal acceptance deferred:** conservative no-retry rule, Zarinpal inquiry, operator-visible exceptions, evidence-backed clearance, callback/timeout/late Authority tests are in place. The owner has no merchant account yet; keep #81 open and the gateway disabled until onboarding and live settlement/currency validation.
2. **4B — mobile-first UX and design polish code implemented, device acceptance pending:** product variant picker, one-tap cart quantity, compact OTP, prefilled receiver phone, saved address selection, one-page quote+order confirmation and clear toman/shipping total; preserve server-side fingerprint validation. An additional [PR #85](https://github.com/DenaSon/Denapax/pull/85) fixes Tailwind's previously missing customer Blade scanning and standardizes CTA/form/error states, payment status, number copy and navigation. **Physical iOS/Android, a11y and browser Back/Forward testing still required.** See [Phase 4B handoff](docs/41-web-phase4b-mobile-checkout-ux.md) and [UI polish handoff](docs/42-web-storefront-ui-ux-polish.md).
3. **4C — staging and manual-first acceptance:** SMS.ir OTP delivery/login are owner-accepted. The owner does not yet have a Zarinpal merchant account, so keep #81 open and `ZARINPAL_ENABLED=false`; resume merchant/currency/callback/settlement acceptance after onboarding. Continue with target-MySQL migration rehearsal, approved shipping/contact settings and complete manual receipt-first acceptance.
4. **Admin UI polish (PR #86):** action-oriented RBAC dashboard, mobile navigation, Persian order/gateway states, receipt rejection form scoped to a receipt, accurate web notification report. No finance permissions were added. Actual staff phone/browser QA is still pending; see [admin handoff](docs/43-admin-ui-ux-polish.md).
5. **4D — release/security/ops:** end-to-end and negative browser tests, storage ACL, backup + isolated restore, monitored queue/scheduler, Runflare HTTPS/secrets/debug checks, rollback rehearsal, owner/operator GO/NO-GO. Detailed acceptance matrix in [docs/38](docs/38-web-phase4-release-readiness.md).

**Future / post-launch only:** optional transactional order SMS, variant price history [#70](https://github.com/DenaSon/Denapax/issues/70), deeper inventory management, conversion analytics, optional refunds/discount workflows once scoped.

## Working method

One branch/PR per coherent phase, reusable Laravel domain actions, no over-engineered testing; cover fundamental happy/negative paths. Run PHPUnit, Pint, Composer locked audit/validation, Vite build; wait for green CI before squash merge to `master`, verify resulting master SHA and CI. Report PR, merged SHA, tests, release blockers and next scope concisely in Persian. Never claim to have carried out real deployment, merchant settlement, SMS delivery or restore without live evidence.

**Authoritative references:** [Product gallery/mobile QA](docs/44-product-mobile-gallery.md), [Admin UX polish](docs/43-admin-ui-ux-polish.md), [UI polish](docs/42-web-storefront-ui-ux-polish.md), [Phase 4B UX](docs/41-web-phase4b-mobile-checkout-ux.md), [ledger](docs/39-web-roadmap-status.md), [Phase 4 plan](docs/38-web-phase4-release-readiness.md), [Phase 4A bank reconciliation](docs/40-web-phase4a-gateway-reconciliation.md), [Phase 3 payment design](docs/37-web-phase3-payments.md). Docs/01–34 and Telegram-era old issue text are historical only.
