# Phase 9 — Private Telegram group operational alerts

**Scope:** [Issue #64](https://github.com/DenaSon/Denapax/issues/64). Reuse the existing Nutgram bot, transactional outbox, database-backed `notifications` queue and every-minute scheduler. One **private group** only; no extra Telegram bot, Mini App, Redis or Horizon.

## Alert types and contents

| Trigger | Group message | Action |
| --- | --- | --- |
| `order.created` | New order, **DN-public reference**, total in toman, status awaiting customer payment | Staff-only order details `admin.orders.show` |
| `payment.receipt_submitted` | Receipt awaiting **manual bank reconciliation**, public reference, total | Staff-only `admin.payments?receipt=<id>`, opening **this receipt**, not the generic queue |
| `payment.approved` | Bank payment independently confirmed by an authorized staff reviewer; reference and total | Staff-only order details |

**Private information never included:** receipt photos/files, card number/cardholder, customer name/phone/address, bank details. Dynamic strings use escaped Telegram HTML. The links enforce existing Laravel login, staff-active middleware, `orders.view` / `payments.view` / `payments.review` permissions. Never make receipt images public.

## Runflare setup (operator-controlled secrets)

1. Ensure the same existing bot has permission to **send messages** in the intended private group. Do not share its token or the real chat ID in GitHub.
2. Deploy the merged phase code and run safe, non-destructive migrations (back up live DB first):
   ```bash
   cd /var/www
   php artisan migrate --force
   ```
3. Configure in Runflare private environment/secrets:
   ```dotenv
   TELEGRAM_ADMIN_CHAT_ID=-1001234567890
   APP_URL=https://YOUR_REAL_ADMIN_DOMAIN
   TELEGRAM_TOKEN=YOUR_EXISTING_PRIVATE_BOT_TOKEN
   QUEUE_CONNECTION=database
   ```
   **Both values shown above are placeholders**, not real secrets. `TELEGRAM_ADMIN_CHAT_ID` must be a negative numeric Telegram group/supergroup chat ID; do not use the @username. `APP_URL` must be the actual HTTPS domain for working clickable admin links.
4. Refresh configuration after adjusting environment, while retaining the existing `APP_KEY`:
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   php artisan schedule:list
   php artisan denapax:admin-alerts:dispatch
   ```
   **Run the final dispatch command once BEFORE placing the first test order.** It creates a durable first-activation timestamp in `admin_group_alert_channels`. Existing historical events are **not** replayed/flooded to the new group. Changing to another group ID moves the activation date forward again. Disabling/unsetting the ID suspends group dispatch; it does not affect customer notifications.
5. Submit a **new** synthetic test order, upload a test receipt, approve it as authorized staff after simulated/verified bank conditions (do not pretend a fake receipt proves payment). Ensure scheduled commands and database queue workers run:
   ```bash
   php artisan denapax:notifications:dispatch
   php artisan denapax:admin-alerts:dispatch
   php artisan queue:monitor database:notifications,database:default --max=100
   php artisan queue:failed
   ```
   The existing scheduler now runs **both** dispatch commands every minute. Existing queue worker must process `notifications`. Verify the three group messages, the **exact** receipt deep link, and staff access on mobile/desktop. Non-staff should be denied even if they see or forward a button.

## Retry, safety and limits

- `admin_group_alert_channels`: single persisted destination and start timestamp, so initial enablement skips history. On group ID change queued jobs for the previous group cannot be newly claimed for the new group.
- `admin_group_alert_logs`: unique `outbox_event_id`, independent of `notification_logs` (customer delivery) and `outbox_events.processed_at`. A failure to post in the group cannot block customer notifications, order/payment completion or outbox processing.
- The group worker claims using DB row locks, retries at most **5** attempts (exponential minutes up to 60), and reclaims stale `sending` jobs after 3 minutes. Last error stores **exception class only**, never raw API responses/tokens.
- Telegram delivery cannot guarantee exactly-once across the small interval between **Telegram accepting a message** and recording the receipt in the local database; rare duplicates remain possible on a DB crash at this boundary.
- Only one configured group is supported. No topic IDs, multiple recipient groups or advertising/marketing notifications.
- Inspect logs when troubleshooting (no customer payloads):
   ```sql
   SELECT outbox_event_id, status, attempts, last_error, sent_at
   FROM admin_group_alert_logs ORDER BY id DESC LIMIT 20;
   ```
- The previous product-gallery upload concern remains separate. E07 #11/#24/#25 security, restore and production evidence remains open.

**Status:** [Implementation PR #65](https://github.com/DenaSon/Denapax/pull/65) / [Issue #64](https://github.com/DenaSon/Denapax/issues/64). Check the actual PR merge SHA and latest green CI before deploying. This document is **not** proof of a live group message.
