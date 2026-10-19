# Runflare HTTPS / Livewire mixed-content recovery (2026-10-19)

## Observed on the live domain
When the browser opened `https://denapax.ir/admin`, the generated form action was `http://denapax.ir/logout` and Livewire 4's JS route was `http://denapax.ir/livewire-.../livewire.js`. The browser blocked these mixed-content resources, even though Vite assets could load via `ASSET_URL`.

## Application fix

- Laravel 13's request-facing `url()` and `route()` calls use the detected request scheme; with Runflare terminating public TLS before internal HTTP, that scheme may incorrectly be `http`.
- In **production only**, when `APP_URL` explicitly uses the `https` scheme, `AppServiceProvider` forces Laravel's URL generator to use HTTPS. This immediately fixes both the logout form and Livewire's script URL without replacing Livewire's framework routes or editing vendor files.
- An **optional** `TRUSTED_PROXIES` configuration is supported: set it to the provider's **confirmed ingress proxy IP/CIDR list**, comma-separated. Laravel then trusts only `X-Forwarded-Proto` and `X-Forwarded-Port` from those addresses. **Do not set it to `*` unless every origin request is guaranteed to come only through a trusted ingress that sanitizes forwarded headers.** In particular, the code does **not** accept unverified `X-Forwarded-Host` or `X-Forwarded-For`. This setting is optional for the specific mixed-content fix.
- In staging/local/testing or if `APP_URL` is not HTTPS, do **not** force secure URL generation.

## Runflare environment (no secrets here)

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://denapax.ir
ASSET_URL=https://denapax.ir
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_DRIVER=database
```

Optional, **only with verified ingress addresses**:

```dotenv
TRUSTED_PROXIES=10.0.12.5,10.0.12.6
```

Those example IPs are placeholders, **not Runflare's actual proxy addresses**. Do not copy them blindly.

## Operator verification

1. Deploy the commit containing the fix and confirm `APP_URL` remains HTTPS. **Do not** regenerate `APP_KEY` and do not relax CSRF.
2. Restart the Runflare service; then in `/var/www` run:
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   ```
3. Open `https://denapax.ir/admin` in a fresh browser session. Inspect its HTML/Network: the logout form action and Livewire JS `src` must both begin with `https://denapax.ir/`. Test clicking menu links, Livewire interactions and logging out.
4. A HTTPS Livewire JS response should be HTTP 200, not an insecure-content browser block or 404.
5. Check no mixed-content messages remain in browser console. Check current Laravel log if HTTP errors occur. For proxy diagnostics use confirmed Runflare ingress behavior; if forwarded protocol is absent, the canonical URL fix still handles generated URLs but won't automatically repair any other request-security perception.

**Evidence boundary:** Automated URL/render tests passing CI are not proof of real-browser execution on Runflare. No real payment/Telegram E2E or go/no-go approval is implied. Phase 6 issues #11/#24/#25 stay open.
