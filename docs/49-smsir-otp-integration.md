# SMS.ir customer OTP integration

**Status:** adapter implemented and owner-reported real OTP delivery/customer login acceptance completed on 2026-11-17. The exact deployment tier (staging vs production) was not recorded; this is not independent production certification.

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
| `SMSIR_VERIFY_PARAMETER` | Yes | Exact template parameter name without `#`; defaults to `HOURS` |
| `SMSIR_TIMEOUT` | No | Request timeout in seconds; default `8` |
| `SMSIR_CONNECT_TIMEOUT` | No | Connection timeout in seconds; default `3` |

The SMS.ir base URL is fixed in application code to `https://api.sms.ir/v1/`; the API key is never sent to a configurable host.

After changing deployment variables, rebuild Laravel's cached configuration using the normal release process (for example, `php artisan config:cache`). Do not activate the driver while the template ID or API key is missing. The repository default remains `disabled`; the deployment environment controls activation.

## SMS.ir account acceptance checklist

1. Confirm the account API key and an active service line in the SMS.ir panel.
2. Confirm the approved Verify template ID, exact parameter name and final Persian message wording. The current owner-provided Verify template is `967976`, with the panel token `#HOURS#`. The SDK parameter name must be sent as `HOURS` without the `#` delimiters. The application default is now `HOURS`.
3. Confirm the template can deliver to the intended Iranian mobile networks and that the account has sufficient credit.
4. On the configured HTTPS deployment, set the variables above and set `CUSTOMER_SMS_DRIVER=smsir` for the environment where OTP delivery is intended.
5. **Owner-confirmed complete (2026-11-17):** a real test OTP was sent and received using template `967976` with panel token `#HOURS#` / SDK parameter `HOURS`.
6. **Owner-confirmed complete (2026-11-17):** the received code was used successfully and the customer flow was tested through completion.
7. The exact deployment tier (staging vs production), provider message ID and SMS.ir delivery-report reference were not recorded in this handoff. Do not infer production certification from this owner report; retain the broader Phase 4 release gates in #11, #24 and #25.

## Dependency note

The project pins the official `ipe/smsir-php` SDK in `composer.lock` (v1.0.1, published 2024-10-19). Its release history is old, so keep it isolated behind this adapter and review the upstream source and compatibility before any upgrade. Its Laravel package provider is intentionally not auto-discovered because it reads `env()` directly and hard-codes the API base URL. The application owns the SDK binding and timeout policy.


## Owner acceptance record — 2026-11-17

The owner confirmed that, after PRs [#130](https://github.com/DenaSon/Denapax/pull/130) and [#131](https://github.com/DenaSon/Denapax/pull/131) were merged, SMS.ir delivered a real test OTP and the customer login/continuation flow was tested through completion. The exact environment tier was not specified. This closes implementation/provider OTP acceptance issue [#124](https://github.com/DenaSon/Denapax/issues/124), but does not close the independent Zarinpal, MySQL, mobile-browser, security, backup/restore or release-approval gates.
