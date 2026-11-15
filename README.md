# Denapax — Web-first Dena storefront

**PHP 8.4 · Laravel 13 · Livewire 4 · Tailwind CSS 4 · Mary UI 2 · MySQL · Persian RTL**

Denapax sells Dena-region souvenirs and dried products through a customer web storefront. **Telegram ordering, Nutgram, bot/webhook and group alerts were retired** in the November 2026 web cutover. The staff/admin Fortify guard, product catalog, shipping, manual payment review, fulfillment and transactional outbox remain. Currency in the database is **integer IRR**; prices visible to customers/staff are formatted in **toman**.

## Current status — 2026-11-15

**Web Phases 1–3 and Phase 4A (payment-reconciliation safeguards) / 4B (mobile shopping UX) are implemented and merged to `master`. Phase 4C/4D — real providers, actual-device QA, staging/restore/operations and release sign-off — are still pending. This is NOT an approved public web-sales launch.**

- **P1 — identity and foundation:** independent customer guard, verified phone OTP with fail-closed SMS transport, guest and persistent cart merge, removal of Telegram, web outbox/inbox persistence. [PR #77](https://github.com/DenaSon/Denapax/pull/77).
- **P2 — checkout:** Persian product search, cart, saved addresses, weight-based delivery quote, immutable order totals, idempotent confirmation. [PR #78](https://github.com/DenaSon/Denapax/pull/78).
- **P3 — payments and account:** online-first Zarinpal v4 request/verify flow and attempt ledger, manual private receipt upload with staff approval, customer order history/tracking and web inbox UI. [PR #79](https://github.com/DenaSon/Denapax/pull/79).
- **P4A — money-safety code:** unknown gateway payments now block duplicate charges/receipts until provider inquiry and authorized operator reconciliation; admin exception review and verification fencing are implemented in [PR #82](https://github.com/DenaSon/Denapax/pull/82). **Phase 4B mobile UX code:** one-page delivery/shipping/confirm checkout, single-variant picker, one-tap cart quantities, compact OTP and address return; see [PR #83](https://github.com/DenaSon/Denapax/pull/83). **Additional UI polish ([PR #85](https://github.com/DenaSon/Denapax/pull/85)):** Tailwind source correction for customer Blade pages, consistent RTL forest/stone cards, mobile focus/tap styles, visible buying steps, clearer ambiguous-payment text and progressive copy/pending-action helpers. [Polish handoff](docs/42-web-storefront-ui-ux-polish.md). **Still pending:** real mobile browser acceptance, live Zarinpal/SMS provider checks, real merchant settlement, negative/E2E staging, target MySQL migration, private-media backup/restore, workers/alerts, Runflare deployment and owner go/no-go. [Phase 4 release plan](docs/38-web-phase4-release-readiness.md).

**Verified `master` baseline before this gallery PR:** SHA `6edcfdad0c1691dc3771d4b5c63314ec4ec9883f`, [green GitHub Actions run](https://github.com/DenaSon/Denapax/actions/runs/43726671634), 84 tests / 580 assertions + Composer locked production audit/validation, Pint, Vite build. CI uses SQLite and fake gateway responses, not a real bank or production-like MySQL environment. No target production migration, actual OTP delivery, real Zarinpal payment or isolated restore has been confirmed.

A financial-exception operator screen exists at `/admin/payments/gateway`; `payments.view` can inspect, while only `payments.review` can initiate inquiry and evidence-backed clearance. See [Phase 4A technical runbook](docs/40-web-phase4a-gateway-reconciliation.md).

The authoritative **done / remaining** list is [docs/39-web-roadmap-status.md](docs/39-web-roadmap-status.md). Open launch issues: [#11](https://github.com/DenaSon/Denapax/issues/11) / [#24](https://github.com/DenaSon/Denapax/issues/24) / [#25](https://github.com/DenaSon/Denapax/issues/25).

## Current web user journey

1. Public `/` and `/products/{slug}` — search products and variants; mobile-first image gallery with swipe, selectable thumbnails, full-screen pinch zoom and photo switching by selected variant.
2. `/cart` — guest/persisted cart; mobile OTP login at `/customer/login` when required.
3. `/account/addresses`, `/checkout` — saved delivery address, authoritative delivery/shipping quote and exact total with **one-tap order confirmation on the same page**. Previous `/checkout/review` remains compatible but is not in the normal path.
4. `/orders/{orderNumber}` — order hub: **Zarinpal primary**, manual card-to-card + image receipt fallback, payment/fulfillment status and tracking.
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
CUSTOMER_SMS_DRIVER=disabled
CUSTOMER_SMS_URL=
CUSTOMER_SMS_TOKEN=
```

Production needs a trusted HTTPS Bearer-token SMS adapter (`CUSTOMER_SMS_DRIVER=webhook`) capable of accepting `{ "mobile": "...", "message": "..." }`. Do not put credentials in GitHub. OTP challenges are hashed, expire after five minutes, and have a resend cooldown, attempt cap and IP/mobile throttles. `log` transport is local/testing-only.

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
