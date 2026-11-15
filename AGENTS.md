# Denapax — Web-only project handoff

**Updated: 2026-11-15. Authoritative current web handoff: [docs/37-web-phase3-payments.md](docs/37-web-phase3-payments.md).**

## Owner decision

The business no longer uses Telegram as a customer ordering channel. The owner has asserted that the customers table is empty and has authorized a structural refactor. **Do not reintroduce Telegram, Nutgram, Telegram-sourced identity, Telegram notification workers or a Telegram CTA.**

Current engineering goal: deliver a complete secure Persian RTL web shopping experience. **Phases 1–3 are not proof of a commercial launch.** The web cart, address, checkout, private manual receipt upload, Zarinpal gateway integration, account orders/tracking and inbox UI are implemented. Public live sales still require merchant/SMS configuration, verified transaction reconciliations, and production release testing.

## Architecture

- PHP 8.4, Laravel 13, MySQL, Livewire 4, Blade, Mary UI 2, Tailwind 4.
- Customer identity: separate `customer` session guard, normalized verified Iranian mobile, OTP challenge hash/TTL/rate limits; staff `web` guard remains Fortify with restricted RBAC.
- Session cart for guests, persisted `carts`/`cart_items` for customers; merge after phone verification. Web address manager and staged checkout use `SaveCustomerAddress`, `PrepareCheckout`, `CreateOrder`, and session-bound 30-minute preview key/fingerprint. Reuse `SubmitPaymentReceipt`, `ReviewPaymentReceipt`, `TransitionOrder` for manual receipts and fulfillment; the new Zarinpal v4 integration uses explicit attempt ledger and server-to-server verification.
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

1. **Web Phase 2 — complete:** public Persian search, cart continuity, owned delivery addresses, server-side quote with shipping, idempotent checkout and private order confirmation.
2. **Web Phase 3 — implemented:** Zarinpal primary payment integration, private receipt upload, manual review/status, order history and shipment tracking, web inbox display. Zarinpal credentials and SMS remain production prerequisites.
3. **Web Phase 4 — next:** gateway/SMS staging and real merchant smoke, negative/reconciliation tests, private receipt backup and isolated restore, HTTPS/worker monitoring, Runflare go/no-go. Optional transactional SMS notifications.

Older documents through `docs/34` are historical; do not use their Telegram-only assumptions to shape new implementation. Prior open E07 operational/security Issues #11, #24, #25 remain relevant and must not be closed without evidence.
