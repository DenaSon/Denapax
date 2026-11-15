# Denapax — Web-first Dena storefront

**Laravel 13 · PHP 8.4 · Livewire 4 · Tailwind CSS 4 · Mary UI 2 · MySQL**

Denapax is a Persian, RTL web store for Dena souvenirs, dried fruit and local products. The Telegram ordering channel was retired in the web migration. There is **no Telegram bot, webhook, private group alert or Nutgram runtime** in the current application.

## Current delivery status — 2026-11-15

**Web Phase 3: secure payment UX foundation.** Verified customers can search, add to cart, save addresses, confirm a shipping-quoted order, then choose Zarinpal online payment (primary) or private manual receipt upload (fallback). They can view order history, tracking and inbox notifications. **Zarinpal is disabled by default until a verified merchant, callback HTTPS and live payment smoke test are configured; do not announce web-sales launch on CI alone.**

The pre-existing staff admin, catalog, shipping, order snapshots, manual payment review and fulfillment domain actions remain in place. Monetary values are stored in integer IRR, presented in toman. The database has availability flags, **not numeric stock reservations**.

### Web routes

- `/` and `/products/{slug}`: public, crawlable catalog and product details
- `/cart`: guest or authenticated customer cart
- `/account/addresses`: owner-scoped saved delivery addresses
- `/checkout`: address selection and delivery quote; `/checkout/review`: immutable quote preview
- `/orders`: private customer order history; `/orders/{orderNumber}`: payment, status, shipping tracking and evidence
- `/account/notifications`: private notification inbox
- `POST /orders/{orderNumber}/payment/zarinpal`: start verified merchant payment; public callback performs server-side verification
- `POST /orders/{orderNumber}/receipts`: safe manual receipt upload on private disk
- `/customer/login`: phone-based one-time-code authentication, separate from staff `/login`
- `/admin`: protected staff admin (Fortify and RBAC); no public staff registration

### Payment methods

Zarinpal REST v4 is the primary web flow. Configure `ZARINPAL_ENABLED=true`, `ZARINPAL_MERCHANT_ID` (real activated UUID), and `ZARINPAL_SANDBOX` (staging only) after merchant onboarding. The application posts its canonical **IRR** order total to the provider, verifies the returned authority server-to-server and prevents double confirmation or overlapping manual receipt submission. No customer payment should be considered successful on the basis of a browser callback alone.

Manual card-to-card remains a private receipt-upload fallback, reviewed by staff. The receipt disk must remain outside the public storage symlink.

### Customer SMS configuration

The production default is `CUSTOMER_SMS_DRIVER=disabled`, deliberately fail-closed. To enable real sign-in, configure a trusted HTTPS SMS webhook that accepts `{ "mobile": "...", "message": "..." }` with Bearer token:

```env
CUSTOMER_SMS_DRIVER=webhook
CUSTOMER_SMS_URL=https://your-approved-sms-adapter.example/otp
CUSTOMER_SMS_TOKEN=your-secret-token
```

The `log` driver is restricted to local/testing only and should not be used for real customers. Do not commit SMS credentials. Rate limits, SHA-256 hashed rate-limit keys, hashed OTPs, 5-minute expiry, 60-second resend cooldown and a 5-attempt verification cap are enforced.

### Deploying the web cutover

**Warning:** Migration `2026_11_15_150000_convert_customers_to_web` is intentionally irreversible and refuses to run if `customers` contains any rows. The project owner stated there are no existing customers. Independently verify that assertion on the target DB; back up MySQL + private receipt files first. The migration drops Telegram-only tables/columns, creates customer OTP/inbox tables and adapts notification logs. No `migrate:fresh` on production.

After QA and a compatible backup:
```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci
npm run build
php artisan config:cache
php artisan schedule:list
php artisan queue:failed
```

The database notification queue/scheduler continues to persist web customer inbox events. SMTP/SMS transactional order notifications are not yet active. Ensure a supervised `database:notifications,default` worker.

### Engineering practice

Targeted feature tests, Pint, Composer validation/audit and Vite build run in CI. Keep PRs small, merge only when green, and separately verify production migrations/worker/restore. No test run can prove real SMS delivery or production DB state.

See [AGENTS.md](AGENTS.md), [Web Phase 1 handoff](docs/35-web-phase1-cutover.md), and [Web Phase 2 checkout handoff](docs/36-web-phase2-checkout.md), and [Web Phase 3 payments handoff](docs/37-web-phase3-payments.md). Earlier `docs/01`–`docs/34` describe historical Telegram-first development and are not current web scope.
