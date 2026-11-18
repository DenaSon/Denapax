# Mediana customer OTP — deployment and acceptance

**Status:** adapter and short-OTP policy implemented. Real Mediana account/pattern approval, four-digit acceptance and production SMS delivery have **not** been verified.

## API contract

Official documentation: https://apidoc.mediana.ir/

- Fixed endpoint: `POST https://api.mediana.ir/sms/v1/send/otp`.
- Header: `X-API-KEY`; JSON body: `patternCode`, `recipient`, `otpCode`.
- The published example uses a **five-digit** code. Confirm **four-digit** OTP acceptance using your account and approved pattern.
- Accept submission only when HTTP is successful, `meta.code` is `OK`, `data.succeed` is `true`, and `data.requestId` is positive. The sample `PendingApproval` response means queued, **not delivered**.
- Fixed HTTPS host, no redirects, finite timeouts, no automatic retries. Network/provider exceptions are sanitized and the pending challenge is discarded.
- The database stores only an OTP hash, never the plaintext. The existing IP/mobile throttles, 60-second resend cooldown, customer guard, cart merge and customer authorization remain unchanged.
- SMS.ir and HTTPS webhook drivers remain available as optional fallback. No new Composer dependency or migration.

## Environment variables

Store real values only in Runflare/deployment secrets:

```dotenv
CUSTOMER_SMS_DRIVER=mediana
CUSTOMER_OTP_LENGTH=4
CUSTOMER_OTP_TTL=120
CUSTOMER_OTP_MAX_ATTEMPTS=3
MEDIANA_API_KEY=
MEDIANA_OTP_PATTERN_CODE=
MEDIANA_TIMEOUT=8
MEDIANA_CONNECT_TIMEOUT=3
```

- `MEDIANA_API_KEY`: API key from Mediana developer access keys.
- `MEDIANA_OTP_PATTERN_CODE`: **approved** OTP pattern code, not the text of the pattern.
- `CUSTOMER_OTP_LENGTH`: 4 or 6; defaults to 4. Codes with leading zeros are valid.
- `CUSTOMER_OTP_TTL`: expiry in seconds (60–600); defaults to 120.
- `CUSTOMER_OTP_MAX_ATTEMPTS`: bad guesses per challenge (1–5); defaults to 3.
- Timeouts in seconds, must be positive, and connect timeout cannot exceed total timeout.
- The repository default remains `CUSTOMER_SMS_DRIVER=disabled`. Do not enable a provider until it is configured and accepted.

## Production verification on the Iran server

1. Create API key and obtain an approved OTP pattern in Mediana with a plan that allows API and pattern sends.
2. Confirm the pattern supports a **four-digit** code; Mediana's published API example is five digits.
3. Set environment variables in the Runflare deployment; retain SMS.ir secrets for rollback if desired.
4. Rebuild cached Laravel configuration (e.g. `php artisan config:cache`) as part of the deployment process.
5. On a real mobile, test SMS request, actual delivery, OTP login within two minutes and three wrong attempts; confirm resend cooldown and that the guest cart survives login.
6. Confirm access to saved customer addresses, order history and private payment receipts remains properly scoped.

If Mediana does not accept/deliver OTP, select the previously working `smsir` driver and rebuild config. **Never bypass customer OTP**. Provider API acceptance does not itself prove delivery.
