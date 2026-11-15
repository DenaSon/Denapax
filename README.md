# Denapax — Web-first Dena storefront

**Laravel 13 · PHP 8.4 · Livewire 4 · Tailwind CSS 4 · Mary UI 2 · MySQL**

Denapax is a Persian, RTL web store for Dena souvenirs, dried fruit and local products. The Telegram ordering channel was retired in the web migration. There is **no Telegram bot, webhook, private group alert or Nutgram runtime** in the current application.

## Current delivery status — 2026-11-15

**Web Phase 1: customer identity and cart foundation.** Customer sign-in is via mobile OTP with a separate `customer` session guard, and both guest/session carts and persistent customer carts work. Products and variants can be browsed and added from the public site. Checkout, web receipt upload, customer order history and customer notification pages are **not live yet**; do not announce a web-sales launch.

The pre-existing staff admin, catalog, shipping, order snapshots, manual payment review and fulfillment domain actions remain in place. Monetary values are stored in integer IRR, presented in toman. The database has availability flags, **not numeric stock reservations**.

### Web routes

- `/` and `/products/{slug}`: public, crawlable catalog and product details
- `/cart`: guest or authenticated customer cart
- `/customer/login`: phone-based one-time-code authentication, separate from staff `/login`
- `/admin`: protected staff admin (Fortify and RBAC); no public staff registration

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

See [AGENTS.md](AGENTS.md) and [Web Phase 1 handoff](docs/35-web-phase1-cutover.md). Earlier `docs/01`–`docs/34` describe historical Telegram-first development and are not current web scope.
