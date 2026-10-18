# Phase 5 — Fulfillment & Notifications

**Scope:** MVP v1.1, stories #21, #22, #23 and #28  
**Development branch:** `phase/5-fulfillment-notifications`  
**Merge gate:** essential PHPUnit, Pint, Composer and Vite checks, then squash merge to `master`.

## Delivered business flows
- Admin orders queue with pagination; filter by number, order/payment states and date; details show immutable line, recipient, amount, shipping and fee snapshots.
- Receipt metadata/images appear only for staff granted `payments.view`; normal `orders.view` never authorizes private receipt access.
- Transactional status machine: `confirmed -> preparing -> shipped -> delivered`; allowed cancel is unpaid `awaiting_payment` with no pending review. Shipment is one per order, manually records carrier and tracking; actor and status history/audit persist.
- Telegram bot `/orders` and “سفارش‌های من” shows only caller-owned order snapshots, payment state and tracking details.
- Durable outbox events from checkout and receipts are consumed by `denapax:notifications:dispatch`. Background `DeliverOutboxNotification` jobs use the queue named `notifications`, one recipient log per event, five bounded attempts, backoff and claim lease to avoid concurrent sends. Failed Telegram calls do not roll back completed commerce state.
- All eight MVP transactional notices supported: `order.created`, `payment.receipt_submitted`, `payment.receipt_rejected`, `payment.approved`, `order.preparing`, `order.shipped`, `order.delivered`, `order.canceled`. Bot provider error *class* only is stored, not exception text with possible tokens.
- The protected admin notifications page displays delivery status, retry counts and sanitized error types. The scheduler registers the outbox dispatch command every minute. Operations must run **both** `php artisan schedule:work` (or cron `schedule:run`) and a worker consuming `notifications`.

## Known exactly-once limitation
Business events and receipt/order transitions are transactionally deduplicated. Telegram message delivery is **at least once** across an ambiguous network timeout: if Telegram accepts a message but the network drops the response, retry may duplicate the visible chat message. This cannot be guaranteed exactly once via the Telegram API. Notification logs expose attempts and status. Exhausted failures stay visible for operators rather than silently claiming delivery.

## Release integration requirements (Phase 6)
- Configure production MySQL and Redis; `QUEUE_CONNECTION=redis`, working Telegram token/HTTPS webhook. Use a managed scheduler and `php artisan queue:work redis --queue=notifications,default --tries=1 --timeout=60`; set `REDIS_QUEUE_RETRY_AFTER=120` and actual worker supervision/restart.
- Ensure `schedule:run` runs every minute and receipt directory/database are backed up. Never put bot tokens, card data or receipt images in git.
- Do a true Telegram-to-order-to-receipt-to-approval-to-dispatch/tracking-to-delivery journey, and test rejection/resubmission and notification failures.
- CI uses temporary SQLite, mocked Telegram and rendered Livewire screens; no real MySQL/Redis/webhook or carrier delivery was verified yet.
- Full GUI/RTL visual review and production-like environment tests remain Phase 6.

## Next
Phase 6 — real integrated validation, security hardening, operational rehearsal and launch readiness.
