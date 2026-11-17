# SMS.ir customer OTP integration

**Status:** adapter implemented; real provider acceptance is still pending. Keep `CUSTOMER_SMS_DRIVER=disabled` until the SMS.ir account, approved Verify template and an authorized staging delivery are verified.

## Runtime design

- `CustomerOtpLogin` continues to depend only on `CustomerOtpSender`; it does not know about SMS.ir or its SDK.
- `CustomerOtpSender` selects the `smsir` driver and delegates to `App\Integrations\Sms\SmsIrOtpSender`.
- The adapter uses the official `ipe/smsir-php` SDK's `verifySend()` method.
- The SDK's package auto-discovery is disabled. `AppServiceProvider` binds its service using `config('services.smsir')`, so credentials remain compatible with `config:cache` instead of the SDK provider's direct runtime `env()` access.
- The application supplies finite connect/request timeouts and disables redirects so the API key header is not forwarded to another host.
- A response is accepted only when SMS.ir returns API `status = 1` and a positive `data.messageId`. HTTP success alone is not sufficient.
- Provider exceptions and response details are reduced to a generic delivery failure. OTP values, API keys and SMS payloads must never be logged.
- No automatic retry is performed for OTP sends; a retry could create duplicate messages.
- Existing mobile/IP rate limits, resend cooldown, OTP expiry, verification-attempt limit, hashed storage and replay prevention remain in `CustomerOtpLogin`.

## Required environment variables

Set these in the deployment secret/environment manager. Do not commit real values or send API keys through chat, issues or logs.

| Variable | Required | Description |
| --- | --- | --- |
| `CUSTOMER_SMS_DRIVER` | Before activation | Set to `smsir` only after staging acceptance; default remains `disabled` |
| `SMSIR_API_KEY` | Yes | API key from SMS.ir developer panel |
| `SMSIR_VERIFY_TEMPLATE_ID` | Yes | Numeric ID of the approved Verify template |
| `SMSIR_VERIFY_PARAMETER` | Yes | Exact template parameter name without `#`; defaults to `Code` |
| `SMSIR_TIMEOUT` | No | Request timeout in seconds; default `8` |
| `SMSIR_CONNECT_TIMEOUT` | No | Connection timeout in seconds; default `3` |

The SMS.ir base URL is fixed in application code to `https://api.sms.ir/v1/`; the API key is never sent to a configurable host.

After changing deployment variables, rebuild Laravel's cached configuration using the normal release process (for example, `php artisan config:cache`). Do not activate the driver while the template ID or API key is missing.

## SMS.ir account acceptance checklist

1. Confirm the account API key and an active service line in the SMS.ir panel.
2. Confirm the approved Verify template ID, exact parameter name and final Persian message wording. The default parameter is `Code`, but use the actual panel value if it differs.
3. Confirm the template can deliver to the intended Iranian mobile networks and that the account has sufficient credit.
4. On HTTPS staging, set the variables above and temporarily set `CUSTOMER_SMS_DRIVER=smsir`.
5. Send one OTP to an owner-authorized test mobile. Confirm the SMS.ir response/message ID and delivery report in the SMS.ir panel.
6. Verify successful sign-in, wrong-code attempt handling, expiry, resend cooldown and failure behavior. Confirm no OTP or API key appears in application logs.
7. Keep production disabled until the owner approves the test and the release checklist is updated.

## Dependency note

The project pins the official `ipe/smsir-php` SDK in `composer.lock` (v1.0.1, published 2024-10-19). Its release history is old, so keep it isolated behind this adapter and review the upstream source and compatibility before any upgrade. Its Laravel package provider is intentionally not auto-discovered because it reads `env()` directly and hard-codes the API base URL. The application owns the SDK binding and timeout policy.
