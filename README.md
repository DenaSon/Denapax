# Denapax — Web-first Dena storefront

**PHP 8.4 · Laravel 13 · Livewire 4 · Tailwind CSS 4 · Mary UI 2 · MySQL · Persian RTL**

Denapax sells Dena-region souvenirs and dried products through a customer web storefront. **Telegram ordering, Nutgram, bot/webhook and group alerts were retired** in the November 2026 web cutover. The staff/admin Fortify guard, product catalog, shipping, manual payment review, fulfillment and transactional outbox remain. Currency in the database is **integer IRR**; prices visible to customers/staff are formatted in **toman**.

## Current status — 2026-11-17

**Web Phases 1–3, Phase 4A/4B foundations, SMS.ir OTP integration and the SPA-like storefront are implemented and merged.** Current `master`: `16688997d7bcbdb5f84131e3f2a6cf5e1d089181`; [latest master CI](https://github.com/DenaSon/Denapax/actions/runs/44076598005) passed **135 tests / 1,002 assertions**. This is automated code validation on SQLite, not production certification.

- **Identity and OTP:** customer-only guard, hashed/time-limited OTP, rate limits and official SMS.ir Verify adapter. The owner confirmed real OTP delivery and successful customer login on the configured deployment; the exact deployment tier was not recorded. See [SMS.ir handoff](docs/49-smsir-otp-integration.md).
- **Catalog and checkout:** Persian catalog, sellable variant filtering, guest/customer cart, saved addresses, authoritative weight-based shipping quote, immutable order snapshots and idempotent confirmation.
- **Payment:** manual card-to-card transfer + private receipt + staff bank verification is the active path while `ZARINPAL_ENABLED=false`. Zarinpal remains integrated but is deferred because the owner does not yet have a merchant account; keep [#81](https://github.com/DenaSon/Denapax/issues/81) open.
- **SPA-like storefront:** Livewire 4 navigation, reactive cart controls, landing product preview, product gallery, customer address/checkout components and responsive cart/loading feedback ([PRs #95–#100](https://github.com/DenaSon/Denapax/pulls?q=is%3Apr+is%3Amerged+95..100)). OTP expiry/resend countdown UX is in [PR #133](https://github.com/DenaSon/Denapax/pull/133).
- **Still pending:** target-MySQL migration rehearsal, manual receipt-first end-to-end acceptance, iOS/Android browser checks, private database/receipt backup and isolated restore, supervised production workers/monitoring, rollback and owner/operator GO/NO-GO. **Public web-sales launch remains NO-GO.** See release gates [#11](https://github.com/DenaSon/Denapax/issues/11), [#24](https://github.com/DenaSon/Denapax/issues/24), [#25](https://github.com/DenaSon/Denapax/issues/25).

A financial-exception operator screen exists at `/admin/payments/gateway`; `payments.view` can inspect, while only `payments.review` can initiate inquiry and evidence-backed clearance. See [Phase 4A technical runbook](docs/40-web-phase4a-gateway-reconciliation.md).

## Public technical SEO — 2026-11-19

Denapax's public storefront now includes crawlable category landing pages (`/categories/{slug}`), 301 redirects from the old homepage category query links, self-canonical pagination, category URLs in the sitemap, and factual Organization/WebSite/Product/BreadcrumbList/CollectionPage structured data. Product descriptions and image alt text are derived from real catalog fields; the homepage title and copy target both DenaPax brand and local product discovery. Search, cart, accounts and private order pages remain `noindex`. Unused remote hero image preload has been removed in favor of the actual local SVG.

**No fake reviews, shipping times or return policies were added.** Under the current nationwide tariff, shipping is calculated from basket and packaging weight (not destination); the cart shows a current estimate before login, while checkout revalidates the authoritative total. Do not claim fixed shipping fees or return terms in schema until an owner-approved public policy exists. Google Search Console ownership verification and sitemap submission are still pending outside GitHub; live Google indexing, rankings and Core Web Vitals are **not** certified by CI. See [SEO implementation and owner handoff](docs/58-technical-seo-search-console.md).

## Current payment priority while merchant onboarding is pending (2026-11-16)

**Manual card-to-card transfer and private receipt upload are the primary payment UI when `ZARINPAL_ENABLED=false` (default).** Staff verify the actual bank deposit and approve/reject in `/admin/payments`. Zarinpal code remains integrated and switches to the primary online CTA automatically when `ZARINPAL_ENABLED=true` and a valid merchant ID are configured in the deployment environment, after live acceptance; manual receipts remain a fallback. No migration is needed to change this flag. The owner confirmed real SMS.ir OTP delivery and successful customer login; the exact deployment tier was not recorded. This does not prove Zarinpal settlement or full production acceptance. See [manual-first rollout](docs/45-manual-receipt-first-rollout.md) and [release readiness](docs/38-web-phase4-release-readiness.md).

## Customer landing and shopping polish — 2026-11-16

Navigation and accessibility [#92](https://github.com/DenaSon/Denapax/pull/92): three editorial landing sections instead of the confusing four numbered rail entries, correct mobile cart route, pointer cursors for interactive controls. Premium landing polish [#93](https://github.com/DenaSon/Denapax/pull/93): centered Dena photo hero, factual value strip, consistent product-card visuals, clearer Dena story and final order CTA. Faster shopping [#94](https://github.com/DenaSon/Denapax/pull/94): the landing preview submits the chosen variant and quantity to the existing server-validated, CSRF-protected guest/customer cart route, and header badges show total units. All remain subject to actual Runflare deployment and mobile/keyboard acceptance. [Visual QA and scope](docs/47-landing-ui-ux-polish.md).

## SPA-like shopping and Livewire 4 — 2026-11-16

Manual payment receipt approval can now queue a **single optional Mediana notification SMS** to the customer's verified login number after an authorized bank review. This SMS is disabled by default until `MEDIANA_PAYMENT_APPROVAL_SMS_ENABLED=true` is configured; ordinary Informational SMS or a separate approved payment pattern can be used. The existing web inbox and payment state remain independent; see [rollout notes](docs/61-manual-receipt-approval-sms.md).

The public store now uses a **shared Livewire 4 / Alpine runtime** and `wire:navigate` for safe internal GET links, with browser History navigation and cleanup/rebinding for gallery and clipboard scripts ([#95](https://github.com/DenaSon/Denapax/pull/95)). Cart quantity/remove and shared header badges are Livewire events ([#96](https://github.com/DenaSon/Denapax/pull/96)); catalog search remains **SSR/SEO-first** and Alpine-enhanced with debounce/history, while quick-add in the product dialog uses a server-validated Livewire action ([#97](https://github.com/DenaSon/Denapax/pull/97)). Customer address forms and checkout address selection use guarded Livewire components without changing shipping quote/idempotency ([#98](https://github.com/DenaSon/Denapax/pull/98)); shared offline/keyboard-navigation states and staff GET links are [#99](https://github.com/DenaSon/Denapax/pull/99). [#100](https://github.com/DenaSon/Denapax/pull/100) completes the responsive cart redesign and fixed, non-layout-shifting Livewire loading feedback. Original native GET/POST/PUT/PATCH/DELETE endpoints remain functional without JavaScript. **OTP/session-changing POST, payment, private receipt upload and order confirmation deliberately stay native/server-authoritative.** Read [implementation and Runflare QA](docs/48-livewire-spa-upgrade.md). CI passing is not real-device approval.

## Current web user journey

1. Public `/` and `/products/{slug}` — search products and variants; mobile-first image gallery with swipe, selectable thumbnails, full-screen pinch zoom and photo switching by selected variant.
2. `/cart` — guest/persisted cart; mobile OTP login at `/customer/login` when required.
3. `/account/addresses`, `/checkout` — saved delivery address, authoritative delivery/shipping quote and exact total with **one-tap order confirmation on the same page**. Previous `/checkout/review` remains compatible but is not in the normal path.
4. `/orders/{orderNumber}` — order hub: **manual card-to-card and private receipt first** while `ZARINPAL_ENABLED=false`; Zarinpal becomes primary after real merchant activation, with manual fallback, payment/fulfillment status and tracking.
5. `/orders` — customer-owned order history; `/account/notifications` — customer-owned inbox; `/admin` — separate staff-only admin.

Payments are **not** proved by return-page parameters or uploaded receipt images. Only a server-verified Zarinpal transaction or staff-approved bank receipt can confirm an order. Receipt media is stored on the non-public `receipts` disk. The current database uses `is_available` product flags rather than numeric inventory reservations.

## Configuration gates (disabled by default)

### Zarinpal

```env
ZARINPAL_ENABLED=false
ZARINPAL_MERCHANT_ID=
ZARINPAL_SANDBOX=false
```

Use only an activated real merchant identifier and a public HTTPS callback (`/payments/zarinpal/callback`). Confirm provider currency contract (IRR/IRT) and a small authorized transaction against the merchant panel before enabling sales. **Phase 4A code mitigation:** the 45-minute age cutoff no longer authorizes a fresh charge. Unknown/expired attempts require provider inquiry and authorized bank-panel reconciliation; customers should not pay again while unresolved. **Real merchant account/currency/settlement and recovery testing are still mandatory before enabling production payments.**

### Customer OTP

```env
CUSTOMER_SMS_DRIVER=mediana
CUSTOMER_OTP_LENGTH=4
CUSTOMER_OTP_TTL=120
CUSTOMER_OTP_MAX_ATTEMPTS=3
MEDIANA_API_KEY=
MEDIANA_OTP_PATTERN_CODE=
MEDIANA_TIMEOUT=8
MEDIANA_CONNECT_TIMEOUT=3
```

The Mediana OTP adapter uses an approved pattern and `X-API-KEY`, while the repository default driver remains `disabled` until live acceptance. Four-digit OTPs expire in 120 seconds with three attempts (all configurable), keep leading zeros, and are hashed in the DB. The previous SMS.ir adapter remains available via `CUSTOMER_SMS_DRIVER=smsir` with its existing secrets and template; to restore the previous code length set `CUSTOMER_OTP_LENGTH=6`. See [Mediana deployment guide](docs/57-mediana-otp-integration.md) and [SMS.ir history](docs/49-smsir-otp-integration.md).

## Deployment and operational constraints

**Do not deploy blindly:** migration `2026_11_15_150000_convert_customers_to_web.php` deletes Telegram-era schema and refuses to execute if any target `customers` rows exist. Verify the **actual** target MySQL customer count first; create coordinated MySQL + private receipt-media backups and prove an **isolated restore**. Test web and gateway migrations against staging MySQL before any production `migrate --force`. Never `migrate:fresh` against production.

Provision real shipping/card/support configuration, HTTPS and `APP_DEBUG=false`, secured environment secrets and supervised database workers for `notifications,default` plus the `denapax:notifications:dispatch` minute schedule. Confirm private media permissions, monitoring, restore/rollback and no old Telegram webhook; use [Phase 4 acceptance plan](docs/38-web-phase4-release-readiness.md).

## Development quality

Run `php artisan test`, `vendor/bin/pint --test`, `composer validate --no-check-publish`, `composer audit --locked --no-dev` and `npm run build`. GitHub CI runs these checks on PR and `master`. Use one feature branch/PR per coherent phase, essential tests rather than exhaustive duplicates, and only merge after clean CI.

- [AGENTS.md — working agreement and handoff](AGENTS.md)
- [Current web delivery status and remaining work](docs/39-web-roadmap-status.md)
- [Next Phase 4 release plan](docs/38-web-phase4-release-readiness.md)
- [Phase 4A reconciliation/operator runbook](docs/40-web-phase4a-gateway-reconciliation.md)
- [Phase 4B mobile checkout UX](docs/41-web-phase4b-mobile-checkout-ux.md)
- [Storefront UI/UX polish and device acceptance](docs/42-web-storefront-ui-ux-polish.md)
- [Admin operational UI/UX polish](docs/43-admin-ui-ux-polish.md)
- [Mobile product image gallery and operator guidance](docs/44-product-mobile-gallery.md)
- [Phase 1 history](docs/35-web-phase1-cutover.md), [Phase 2 history](docs/36-web-phase2-checkout.md), [Phase 3 design](docs/37-web-phase3-payments.md)

**Historical docs/01–34 and old GitHub issue history refer to a previous Telegram-first implementation; they are not the active web product specification.**
