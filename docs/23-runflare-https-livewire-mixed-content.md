# Runflare TLS termination and Laravel mixed-content URLs

**2026-10-19 — corrected after live operator diagnostics.**

## Verified evidence

- Browser reported `http://.../logout` and `http://.../livewire-.../livewire.js` on a HTTPS admin page.
- After the earlier [PR #53](https://github.com/DenaSon/Denapax/pull/53) deploy the operator reported `ERR_CONNECTION_CLOSED`.
- Operator runtime checks subsequently showed `nginx -t` passing, PHP files with no syntax errors, `php artisan about` completing, and **`curl http://127.0.0.1/up` returning HTTP 200**. These findings show the *internal* HTTP application works and do **not** diagnose whether Runflare's *public* HTTPS ingress, TLS, DNS, port mapping or routing has a separate problem.
- Telegram message/callback operations were successfully sent in the provided Laravel log. A separate `Log [] is not defined` was caused by an **empty** `TELEGRAM_LOG_CHANNEL`. `APP_DEBUG` was still enabled in production.

## Scoped code correction

The previous implementation forced HTTPS globally from `AppServiceProvider::boot()` and introduced optional `TRUSTED_PROXIES` configuration. This follow-up moves **only URL scheme generation** into `App\Http\Middleware\ForceHttpsUrls`, registered in the global HTTP middleware stack. The provider is restored to its database Schema responsibility. The extra proxy configuration is removed.

Middleware conditions:
1. `APP_ENV=production`
2. `APP_URL` begins with a real `https` URL scheme.

If both hold, `URL::forceScheme('https')` runs **per HTTP request** and applies to Laravel URL generation (`url()`, `route()`, Livewire script URL). The scheme is derived from ENV; **no production domain name is hardcoded in application source**. This does **not** send a redirect, rewrite the incoming proxy request, or turn the HTTP origin into an HTTPS server. An ingress/TLS outage cannot be cured solely by changing this middleware.

## Runflare ENV

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR_PUBLIC_DOMAIN
ASSET_URL=https://YOUR_PUBLIC_DOMAIN
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
TELEGRAM_LOG_CHANNEL=null
```

Replace placeholders using Runflare secrets/settings. `TELEGRAM_LOG_CHANNEL` must be the **literal valid logging channel name** `null`, not an empty string; do not enable verbose bot payload logs. Preserve the existing `APP_KEY`. Do not paste private tokens or bank data in tickets.

## Restore/connectivity sequence

1. If the public HTTPS endpoint is currently unavailable, use Runflare's previous known-good deployment rollback first, while preserving database and secrets. A code change should not be assumed to fix `ERR_CONNECTION_CLOSED`.
2. Verify actual **public** network separately from the working internal HTTP `/up`: confirm domain records, TLS certificate, service ingress/port mapping, target container status and logs, and try `curl -vkI https://YOUR_PUBLIC_DOMAIN/up` from a network outside the container. Do **not** expose HTTP origin just to bypass SSL.
3. Only after the public endpoint is healthy, deploy the new `master` SHA containing this middleware correction, and:
   ```bash
   cd /var/www
   php artisan optimize:clear
   php artisan config:cache
   php artisan view:cache
   ```
4. Browser: open the HTTPS admin page, inspect the **generated** Logout form `action` and Livewire `script src` for HTTPS, verify the JS request is 200 and clicks work, test logout, session and CSRF.
5. CI tests use `store.example.test` as an arbitrary fixture; the live domain comes solely from ENV. Real Runflare browser/TLS behavior must still be validated by the operator.

## Remaining unrelated deployment warnings

- `supervisorctl status` can fail if the supplied INI is missing a `[supervisorctl]` section; it does **not** prove Nginx/PHP-FPM are stopped. Check process list/runtime logs. Do not replace Runflare's supervisor INI without confirming its startup contract.
- Production `APP_DEBUG` must be `false`; Nutgram log channel should be a valid channel and not an empty string.
- Missing `public/storage` symlink affects uploaded public media, **not** TCP/TLS handshake. Prepare persistent storage/volumes and run `php artisan storage:link` only when appropriate.

**No changes to queue, billing, bot handlers or migrations in this HTTPS correction.** Keep Phase 6 issues open until real staging/live gate evidence.
