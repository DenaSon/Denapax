# Denapax — SMS when management approves a manual payment receipt

**Status:** implemented, disabled by default pending operator rollout and live Mediana acceptance.

## Transactional trigger and recipient

- The sole trigger is `ReviewPaymentReceipt::execute(..., 'approved')` after staff permission check, latest-pending-receipt check and bank reconciliation.
- In the same database transaction as `payment.approved` outbox creation, a separate `notification_logs` entry is inserted for `channel=sms` **only when enabled**. Rejecting receipts, already approved receipts, and prior historical approvals create no SMS entry. The existing web inbox event is independent.
- The SMS goes to `orders.customer_id -> customers.mobile` (the verified login mobile), **never** the delivery address or `orders.recipient_mobile`.
- The existing `denapax:notifications:dispatch` scheduler claims these logs and queues `SendPaymentApprovalSms` on the existing `notifications` database queue. The worker atomically records one outbound attempt before calling Mediana.
- No automatic SMS retries: a timeout may mean the provider accepted the message. Status `sent` means Mediana accepted the API request, not delivery to a handset. SMS failures are terminal in the admin logs and do not undo payment approval. Web inbox delivery is unaffected.

## Mediana API

Official documentation: https://apidoc.mediana.ir/

- If `MEDIANA_PAYMENT_APPROVAL_PATTERN_CODE` is blank: POST `https://api.mediana.ir/sms/v1/send/sms`, type `Informational`, one recipient, text: `دناپکس\nپرداخت سفارش <ORDER> تأیید شد. سفارش شما در صف آماده‌سازی قرار گرفت.`
- If a separate **approved** pattern code is configured: POST `https://api.mediana.ir/sms/v1/send/pattern`, type `Informational`, one recipient, `parameters` with the configured placeholder name (default `ORDER`) containing the public order reference.
- **Do not reuse the OTP pattern.** OTP remains independent of payment notifications.
- Provider response must have HTTP 2xx, `meta.code=OK`, `data.succeed=true`, positive `data.requestId`. A 2xx without a clear acceptance is treated as failed/unknown, not proof of delivery.
- Fixed HTTPS endpoints and timeout settings reused from `services.mediana`. Credentials, phone numbers, full response bodies, receipt images and OTP codes are never written to the notification log.

## Runflare rollout

```dotenv
MEDIANA_PAYMENT_APPROVAL_SMS_ENABLED=true
# Recommended if provider limits ordinary informational SMS to approved patterns:
MEDIANA_PAYMENT_APPROVAL_PATTERN_CODE=
MEDIANA_PAYMENT_APPROVAL_PATTERN_PARAMETER=ORDER
```

1. Confirm production `MEDIANA_API_KEY`, `MEDIANA_TIMEOUT`, `MEDIANA_CONNECT_TIMEOUT` already work for OTP. Only **new** approvals after enablement create SMS logs.
2. Prefer an approved **payment notification** pattern, e.g. «دناپکس: پرداخت سفارش #ORDER# تأیید شد. سفارش شما در صف آماده‌سازی قرار گرفت.» (confirm literal placeholder format in Mediana). The existing OTP pattern code is unrelated.
3. Rebuild/redeploy the code and cached config, enable flag, verify `config('services.mediana.payment_approval_sms_enabled') === true`.
4. Ensure schedule runs every minute and database queue workers process `notifications`. Review a **real new** manual receipt approval, and observe the admin notification-log entry with `channel=sms`. Compare provider request with an actual handset delivery.
5. If provider rejects ordinary informational sends, configure the approved pattern. Never restart a completed job for a timed-out request without checking Mediana's send report for duplicates.

## Automated coverage

New feature tests cover disabled flag, approved vs rejected and duplicate reviews, account-mobile isolation, web-event independence, valid Mediana normal/pattern API requests, provider rejection and no duplicate dispatch. Real delivery and carrier inbox receipt require production QA.
