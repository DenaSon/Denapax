# Phase 2 — Telegram Storefront & Customers

**Status:** Completed and merged to `master` via [PR #31](https://github.com/DenaSon/Denapax/pull/31), merge commit [`12d28d6`](https://github.com/DenaSon/Denapax/commit/12d28d6d770d44b5cf556e6c0a1cc0e53003eb12), with successful PR and master CI.  
**Stories:** #4, #13, #14, #26 · **Source of truth:** MVP Scope v1.1 and Traceability v1.1.

## Delivered within phase scope

- Customer persistence keyed by **trusted Telegram user ID** (separate from admin staff `users`) with chat ID and contact profile.
- Multiple customer addresses with recipient name/mobile, province/city, full address and 10-digit postal code. Add, edit (re-enter fields), delete, choose default; all operations scoped to the caller. Default mutation serializes on owning customer row.
- Persistent Telegram conversation state and context, 30-minute form TTL, reset on `/cancel` or `/start`.
- Regular **Nutgram 4** bot with private-chat buttons, category browse, active-product paginated list, product details, gallery navigation (all images), optional per-variant image, image-backed listing with category/summary/features, variant/price in toman, title/description search, and Support contact from store settings.
- `POST /api/telegram/webhook` with required `X-Telegram-Bot-Api-Secret-Token` and durable unique update-ID ledger; failed runs can be retried, fresh in-progress duplicates are acknowledged but not reprocessed.
- The button callbacks are treated as untrusted: resource IDs are always filtered by visibility or owning customer. Telegram ID is derived from the received update and **not** callback data.
- Menu slots for cart and own-orders are **non-functional placeholders by design** until phases 3 and 5. No premature order/payment processing is implemented.

## Security, operations, and known delivery semantics

- **Fail closed:** if `TELEGRAM_WEBHOOK_SECRET` is not configured, the webhook answers 503. Incorrect secret is 403. The secret must be random and stored only in server environment.
- Nutgram `safe_mode` is disabled **only because the dedicated controller implements the bot secret header check** before Nutgram is invoked; keeping both with different secret derivations would prevent genuine requests.
- Configure Telegram Bot API `setWebhook` with `url=https://YOUR-DOMAIN/api/telegram/webhook` and `secret_token=TELEGRAM_WEBHOOK_SECRET`. The Nutgram default `nutgram:hook:set` command may use a different secret: do not use it without confirming it supplies this exact value.
- The update ledger prevents duplicate **completed logical handling** and concurrent fresh update processing; it cannot guarantee exactly-once Telegram message delivery after ambiguous network failures.
- Set `TELEGRAM_TOKEN` to actual BotFather token **only in environment** and `TELEGRAM_WEBHOOK_SECRET` to random 1–256 allowed characters. `APP_URL` must point at a public HTTPS host for media URLs; configure `php artisan storage:link` for product gallery.
- A bot cannot message a user who has never started the conversation or who blocked the bot. Credentials, customer addresses and receipt images must never be in git.

## Phase acceptance and testing

- Essential automated tests: reject missing/invalid webhook secret, deduplicate/retry updates, isolate customer addresses, resume address conversation, hide inactive/unavailable products.
- GitHub Actions: PHP 8.4 PHPUnit, Pint, Composer validation and admin Vite Build.
- **Not performed:** live Telegram webhook test, final MySQL/Redis deployment, end-to-end purchase or payment. Those real integrations remain Phase 6 acceptance tasks as agreed.

## Next phase

Phase 3: Cart, nationwide shipping settings and idempotent checkout with snapshots. Use existing ProductVariant grams/IRR and the `ShippingCalculator` committed in PR #1.

## Final Phase 2 report
- **Delivered:** Private Telegram customer identity, safe webhook/update ledger, resumable multi-address flow, product/category browse/search, image galleries, variant price in toman, support contact and safe future-feature placeholders.
- **Validation:** GitHub Actions passed all essential PHPUnit tests (including Nutgram fake /start handler, webhook secret/dedupe/retry, address ownership and catalog visibility), Pint, Composer validation and Vite asset build. [Master CI run](https://github.com/DenaSon/Denapax/actions/runs/39140168965) also succeeded.
- **Merge:** [PR #31](https://github.com/DenaSon/Denapax/pull/31) squash-merged to `master` at `12d28d6d770d44b5cf556e6c0a1cc0e53003eb12`.
- **Not validated live:** Telegram BotFather token + setWebhook, live Telegram chat/media delivery, destination MySQL migration, Redis production behavior, physical end-to-end order/payment/dispatch. These require real access and are explicitly deferred to phase 6.
- **Next:** Phase 3 carts, shipping tariff persistence and transactional idempotent checkout.
