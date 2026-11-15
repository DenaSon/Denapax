# Web Phase 1 — Customer foundation and Telegram retirement

**Decision / implementation: 2026-11-15 · Target: `master` via reviewed PR**

## Scope and implementation

- Independent `customer` session guard and separate Eloquent authenticatable `Customer`; existing staff `web` guard and Fortify login untouched.
- Iranian mobile normalization, OTP request and verification with hashed one-time codes, TTL (5 minutes), 5 failed tries, resend cooldown (60 seconds) and per-mobile/IP throttling. Challenge deleted on successful verification.
- SMS delivery is an injected `CustomerOtpSender`. Default is disabled; production requires an HTTPS bearer-authenticated webhook transport. Local/testing may explicitly use log transport. Do not enable without approved SMS credentials/adapter.
- Guest session cart, authenticated persistent cart, controlled merge after OTP login, product-detail add-to-cart forms, web cart read/update/remove, safe availability/positive-price checks.
- Public landing/product pages replace all Telegram CTAs with direct web cart/product links.
- Telegram webhook, bot routing, sessions, bot classes, chat alerts, SDK configuration and dependency removed from active application; existing `outbox_events` dispatch to transactional **web inbox** instead of Telegram.
- Preserved existing admin, products, shipping, checkout actions, receipt review and fulfillment services. No customer web checkout/payment frontend has been claimed in this phase.

## Database upgrade contract

Historical migrations remain immutable for upgrade compatibility. `2026_11_15_150000_convert_customers_to_web.php` is the **real production cutover**. Check `customers` is empty (explicit owner assertion) and abort rather than silently delete customer data. Remove Telegram columns/tables and retired notification fields; add `mobile_verified_at` and unique mobile index; create `customer_login_codes` and `customer_notifications`. This migration cannot be rolled back by reconstructing Telegram IDs. Restore a consistent MySQL/media backup if needed.

Do not run `migrate:fresh` in production. Run against staging before live deployment. `users`/RBAC and catalog records are not deleted.

## Behavior / security invariants

- No unverified phone number creates an authenticated customer.
- Web and staff sessions are separate guards.
- Guest cart persists during browsing and merges into verified customer on login; product availability is checked server-side.
- Existing order snapshot logic, ownership checks, bank verification and private receipt disk remain unchanged.
- Events are written exactly once into `customer_notifications` using the outbox event key; no Telegram API or token required.
- No secret OTP, phone data or receipts in public issues. Sender config is fail-closed on production.

## Deployment gates

1. Confirm actual customer count `0`, SQL and private receipt backups, isolated migration rehearsal.
2. Configure HTTPS SMS adapter/credentials, real OTP send/verify smoke and failed-code/rate-limit behavior. **Default disabled** until configured.
3. Run CI (`php artisan test`, `vendor/bin/pint --test`, `composer validate --no-check-publish`, `composer audit --locked --no-dev`, `npm run build`).
4. Verify public product and cart browser workflows, admin login/permissions, configured worker+schedule and no reachable old Telegram webhook.
5. Do **not** declare the web store commercially ready: Phase 2 checkout and Phase 3 payment/account remain pending.

## Outstanding

- Phase 2: full responsive search/variant/cart UX, addresses, shipping quote, web checkout and order confirmation.
- Phase 3: web receipt upload/retry, customer order list/status, private media ACL tests, inbox display and transactional SMS.
- Phase 4: complete release QA, deployment, restore/rollback evidence and owner sign-off.
