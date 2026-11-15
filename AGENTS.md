# Denapax — Web-only project handoff

**Updated: 2026-11-15. Authoritative current web plan: [docs/35-web-phase1-cutover.md](docs/35-web-phase1-cutover.md).**

## Owner decision

The business no longer uses Telegram as a customer ordering channel. The owner has asserted that the customers table is empty and has authorized a structural refactor. **Do not reintroduce Telegram, Nutgram, Telegram-sourced identity, Telegram notification workers or a Telegram CTA.**

Current engineering goal: deliver a complete secure Persian RTL web shopping experience. **Phase 1 foundation is not a commercial launch.** Subsequent phases must implement complete checkout/address selection, private receipt upload/payment, customer account/orders/status, and end-to-end release testing.

## Architecture

- PHP 8.4, Laravel 13, MySQL, Livewire 4, Blade, Mary UI 2, Tailwind 4.
- Customer identity: separate `customer` session guard, normalized verified Iranian mobile, OTP challenge hash/TTL/rate limits; staff `web` guard remains Fortify with restricted RBAC.
- Session cart for guests, persisted `carts`/`cart_items` for customers; merge after phone verification. Continue reusing `ManageCart`, `SaveCustomerAddress`, `PrepareCheckout`, `CreateOrder`, `SubmitPaymentReceipt`, `ReviewPaymentReceipt`, `TransitionOrder`.
- All financial storage is integer IRR; customer and admin displays use toman with exact non-rounded conversion.
- Payment receipts stay on private `receipts` disk and require owner-scoped or staff-authorized access; uploads are not proof of payment. Manual bank verification is mandatory.
- Transactional outbox remains; web inbox persistence replaces Telegram notification jobs. Actual customer-facing order inbox and transactional SMS channel still need work.
- No numeric stock management; only `is_available`. Shipping is one weight-based nationwide tariff.

## Migration and release safety

The post-Telegram cutover migration checks that `customers` is empty and aborts if not. It drops Telegram-only schema and is not reversible without a backup. Always independently check the real database and back up MySQL and private receipt media before deploying. Never `migrate:fresh` in production. Test migration against an isolated copy, preserve protected staff accounts and existing catalog/orders if any.

SMS is disabled by default and requires a separately configured trusted HTTPS provider adapter and secure credentials. Never expose real OTPs, bank/card credentials, customer identifiers, payment files or tokens in source/issues.

## Delivery process

Implement one phase per branch, essential tests rather than exhaustive tests, Pint, Composer audit/validate, frontend build and GitHub CI. Merge to `master` only after passing CI and checking the head SHA. End each phase with a concise Persian report of changes, PR/merge reference, test results, deployment prerequisites and the next phase. Never interpret green code CI as proof of production readiness.

## Next phases

1. **Web Phase 2:** polished product selection/search, cart UX, saved delivery addresses, revalidated checkout, immutable order creation and customer-facing order confirmation.
2. **Web Phase 3:** secure browser receipt upload, manual review/status/resubmission, order history and shipping tracking, web inbox UI and optional SMS transactional notifications.
3. **Web Phase 4:** full negative/security tests, MySQL/private receipt backup & isolated restore, HTTPS/worker monitoring, Runflare production smoke and explicit go/no-go.

Older documents through `docs/34` are historical; do not use their Telegram-only assumptions to shape new implementation. Prior open E07 operational/security Issues #11, #24, #25 remain relevant and must not be closed without evidence.
