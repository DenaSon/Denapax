# Denapax — Livewire 4 SPA-like migration and handoff

**Date:** 2026-11-16. Scope: [Phase A #95](https://github.com/DenaSon/Denapax/pull/95), [Phase B #96](https://github.com/DenaSon/Denapax/pull/96), [Phase C #97](https://github.com/DenaSon/Denapax/pull/97), [Phase D #98](https://github.com/DenaSon/Denapax/pull/98) and [Phase E #99](https://github.com/DenaSon/Denapax/pull/99). The merge/CI records cover implementation, **not real-browser acceptance or Runflare production deployment**.

## Architecture and ownership

| Concern | Owner / mechanism | Invariant |
| --- | --- | --- |
| Cross-page GET links | `wire:navigate` on safe public and staff anchors; Livewire 4 owns client routing | Public/product/cart/account/admin links use browser History API and content swap; authenticated guards and policy checks still execute for each HTTP request |
| Frontend runtime | One `resources/js/storefront.js` Vite entrypoint for landing, product, cart and customer pages, plus existing isolated staff entrypoint | Livewire owns the only Alpine instance. Register Alpine data once at `alpine:init`, not `Alpine.start()` again |
| Repeated navigation | `livewire:navigating` cleanup, `livewire:navigated` remount and focus management | Product gallery keyboard listeners are aborted when leaving; copy controls are rebound only once per new DOM node; no script duplications |
| Cart state | `App\Application\Actions\StoreCart`, `ManageCart` | Livewire `CartItems` actions update existing guest session/customer DB cart, emit `cart-updated`; `CartCount` refreshes from authoritative server quantity |
| Catalog | Server-rendered public catalog and `PublicLandingController`, progressively enhanced by Alpine fetch | Search debounced, filtered and paginated without reload, SEO HTML still indexable, URL/history correct, native GET fallback and preview data updated |
| Product quick add | `App\Livewire\Store\AddToCart` invoked by Alpine-dispatched Livewire event | Only public selected variant ID/quantity passed. `StoreCart::add()` validates availability and ownership server-side. Original CSRF native POST fallback |
| Customer addresses | `AddressEditor`, `AddressList`, `AddressPicker`, shared `CustomerAddressInput`, existing `SaveCustomerAddress` | Customer-owned records, `#[Locked]` IDs/return, original HTTP forms and routes retained |
| Checkout/payment | Existing `WebCheckoutController`, `PrepareCheckout`, `CreateOrder`, receipt and gateway actions | **Never** put shipping totals, fingerprint, idempotency, receipt approval, gateway reconciliation or OTP validity in Alpine/browser state |

## Deliberate full-document transitions

A smooth storefront must not fake financial success. **Native POST/redirect** remains for OTP request and verification (session/guard changes), checkout confirmation (idempotency), Zarinpal handoff/callback and recheck, and **private payment receipt upload and approval**. Those operations require confirmed server-side state and must not be converted into optimistic client updates. Receipt media remains on the private `receipts` disk. Static source links and non-JavaScript form fallbacks remain valid.

## Deployment on Runflare

1. Deploy the latest merged `master` through the existing release pipeline, including `composer install` and `npm ci && npm run build`. Confirm Vite manifest contains `resources/js/storefront.js` and `resources/js/app.js`.
2. Ensure published Livewire v4 assets exist in the **deployed public directory** (the `post-autoload-dump` Composer hook publishes `livewire:assets`). If missing, run `php artisan vendor:publish --tag=livewire:assets --force` in the build process and redeploy the asset directory. Confirm `/vendor/livewire/livewire.min.js` returns HTTP 200. A missing asset makes the browser fall back to regular form navigation, causing the original symptom.
3. Keep `APP_URL=https://denapax.ir`, secure session cookie, stable `APP_KEY`, and platform-confirmed `TRUSTED_PROXIES`. Do not use wildcard trusted proxies without ingress isolation.
4. No migrations or new environment secrets are required by these UX phases. Do not change payment flags, customer session domain or production data for UX deployment.
5. Deploy/refresh Laravel caches using the platform's normal release procedure, not `migrate:fresh` or key regeneration.

## Real-browser acceptance checklist (not proven by PHPUnit)

- Desktop Chrome/Firefox and Android/iOS browsers: Network confirms no **Document** reload for `wire:navigate` links between eligible pages, while Back/Forward, target `#products`, and a direct URL visit still work.
- Guest session: open product modal, choose **second variant**, add two units, see success and header badge without navigation; open cart, click `+`, `−`, remove, verify values and header count without Document navigation.
- Search typing: debounce, no complete refresh, focus/caret stay in field; category filters/pagination, URL and Back/Forward work; disable JS and verify native GET filter/search and POST cart forms still work.
- Sign in by real SMS OTP, verify guest-cart merge and customer-only cart persistence; test invalid/expired session and empty cart.
- Address creation/edit, make default, delete and checkout address change: navigation remains client-side, but total/shipping quote is recalculated by `WebCheckoutController` and checkout key cannot be replayed after cart/price changes.
- Review card-to-card/manual receipt private storage, authorized admin review, gateway disabled env and pending gateway attempt block; **never** infer payment approval from UI.
- Keyboard/assistive tech: Escape/modal focus restore, Tab order, navigation heading announcement, error summaries, zoom 200%, reduced motion, touch controls, offline announcement, and refreshing mid-checkout.
- Test 320/375/390/430px widths and real low-speed connectivity; watch Console for duplicate Alpine initialization, missing Livewire script, 419/403/500 and navigation error.
- Test reverse-proxy HTTPS, Vite cache busting, browser refresh, consistent session cookies, and production queue/scheduler separately; passing `/up` is not sufficient.

## Remaining optional improvements

- True browser-driven Playwright/real-device screenshot assertions are not yet part of GitHub CI; run separately before visual release sign-off.
- Live receipt uploads and payment/OTP actions are intentionally kept native for financial/session safety. A later phase can consider a secure `WithFileUploads` adapter with specific privacy and reconciliation audits.
- The external attributed Dena photograph can eventually be optimized and self-hosted; retain source/license attribution while using it.
- Product data curation, content strategy and inventory tracking are separate from this SPA-like refactor.

**Project invariants:** no Telegram bot return, all monetary amounts persisted in integer IRR and displayed in toman, independent customer and staff guards, real bank verification before receipt approval, unknown gateway attempts block charge retries.


## Product-detail purchase reactivity — 2026-11-17 (PR #137)

The product permalink now reuses the Livewire cart bridge. Alpine reflects the selected variant's server-seeded cart quantity and price; add, increment, decrement and remove actions stay on the product page and refresh the header count. A native CSRF-protected POST remains available when JavaScript/Livewire is unavailable. Transport errors clear the busy state; ambiguous results request authoritative cart state before retry to avoid duplicate additions. The mobile purchase dock respects safe-area insets. Issue #108 is closed; real iOS/Android and keyboard/focus acceptance remains part of #24.

## Catalog discovery and mobile purchase follow-ups — 2026-11-17 (PRs #138–#139)

- Product-preview primary clicks open the dialog without Livewire navigation intercepting the real product URL; modifier clicks and no-JavaScript navigation retain the link.
- Catalog search is submit-driven rather than input-debounced and preserves the native GET fallback, query and category.
- The cart has a fixed mobile checkout dock with server-rendered count/subtotal and an unavailable-line block; its CTA distinguishes guest SMS login from authenticated address/delivery continuation (PR #142). The checkout address picker shows recipient mobile/postal code and separate default/selected markers.
- Gallery desktop width is capped and lightbox zoom is bounded by source pixel resolution. These changes do not replace real-device acceptance.
