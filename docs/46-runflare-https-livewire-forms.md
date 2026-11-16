# Runflare HTTPS, session and Livewire admin forms — operator checklist

**2026-11-16.** Code review identified an HTTPS ingress configuration gap: `ForceHttpsUrls` forces HTTPS links but does not change `Request::isSecure()` for requests passed to PHP over HTTP by a TLS-terminating proxy. That distinction matters to secure session cookies, signed upload URLs, CSRF and Livewire. This change introduces **opt-in, narrowly scoped proxy trust**. It is not a verified diagnosis of the currently deployed admin failure; that requires one real browser Network trace and/or server logs.

## New opt-in configuration

Use these values in Runflare deployment environment (not GitHub):

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://denapax.ir
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
TRUSTED_PROXIES=<actual-known-proxy-IP-or-CIDR>
```

`TRUSTED_PROXIES` may be a comma-separated list of **known reverse proxy** IPs/CIDRs, or `REMOTE_ADDR` for a deployment in which **all direct connections to PHP are exclusively from the trusted platform ingress**. Do not automatically configure `*`, and do not set `REMOTE_ADDR` if the PHP service is directly accessible to arbitrary internet clients. Prefer platform-confirmed proxy addresses. The application trusts only `X-Forwarded-Proto` and `X-Forwarded-Port`, never forwarded host/IP headers. When unset, current behavior is unchanged. `SESSION_DOMAIN` should be unset (host-only cookie) unless using a planned multi-subdomain session; do not use a different hostname.

Leave `SESSION_DOMAIN` **unset** for the single `denapax.ir` host. Update environment settings and **redeploy/restart**, rebuilding Laravel's config cache as part of the deployment process. Inspect a small allowlist of effective values without printing secrets:

```bash
php artisan tinker --execute='dump(["url" => config("app.url"), "env" => config("app.env"), "trusted_proxies" => config("app.trusted_proxies"), "session_driver" => config("session.driver"), "session_domain" => config("session.domain"), "secure_cookie" => config("session.secure"), "same_site" => config("session.same_site")]);'
php artisan route:list --path=livewire
```

The platform must forward `X-Forwarded-Proto: https` on HTTPS browser requests and must not allow client-supplied forwarded headers to bypass the edge. Do not treat `/up` as a Livewire form test.

## Confirmed Livewire 4 JavaScript 404 — published asset fallback

The admin browser trace on 2026-11-16 shows:

```text
GET /livewire-8ed8c6b4/livewire.js?id=a964a3c 404 Not Found
```

The project uses `livewire/livewire v4.4.8`. Its standard script endpoint is intentionally a **dynamic Laravel route** at `/livewire-{hash}/livewire.js`, with the hash derived from `APP_KEY`. The admin layout already includes `@livewireStyles` and `@livewireScripts`. This 404 therefore happens before any form submission: the browser never loads the Livewire runtime.

The first likely cause is the ingress/web-server treating the `.js` URL as a static-file request rather than forwarding it to Laravel. Other possible causes are a stale route cache or inconsistent `APP_KEY` across app replicas. It is **not fixed by `TRUSTED_PROXIES`**; proxy trust affects forwarded request scheme, not route existence.

To avoid dependence on dynamic `.js` routing, the app now publishes Livewire's official static assets during Composer's `post-autoload-dump`:

```bash
php artisan vendor:publish --tag=livewire:assets --ansi --force
```

Livewire detects `public/vendor/livewire/manifest.json` automatically and changes the script URL to `/vendor/livewire/livewire.min.js?id=...`, served as an ordinary static asset. Generated files are ignored by Git and recreated by Composer install/update. No manually copied JS, Vite bundling, or database migration is needed.

After deploying this change:
1. Confirm these files exist in the active release: `public/vendor/livewire/manifest.json` and `public/vendor/livewire/livewire.min.js`.
2. Run `php artisan route:list --path=livewire` and confirm the hashed `POST .../update` route exists.
3. Clear/rebuild only the deployment's route/config caches as appropriate, then restart all replicas. Keep the same `APP_KEY` across every replica; never rotate it to fix this issue.
4. Reload the admin page with cache disabled. The JS request should now be `/vendor/livewire/livewire.min.js?id=...` and return HTTP 200 with JavaScript content.
5. Submit a harmless admin form and inspect the POST update request. If it returns 404, investigate route registration, route cache and identical `APP_KEY` across replicas. If it returns 419, investigate the session/CSRF cookie and trusted proxy settings separately.

The official Livewire 4 docs describe both the hashed endpoint and publishing assets as the supported alternative for environments that cannot route the dynamic script endpoint: https://livewire.laravel.com/docs/4.x/installation#publishing-livewires-assets-to-public-directory

## Browser diagnosis (authenticated staff)

1. Open a staff admin form at `https://denapax.ir/admin/settings`, DevTools → Network, preserve log and filter `livewire`. Submit a **non-sensitive** temporary change only after confirming that a backup exists. Do not share card numbers, login cookies or request payloads.
2. **No Livewire network request:** check Console errors, blocked Livewire script (`/livewire-<hash>/livewire.js` in Livewire 4), CSP, mixed content, missing JS or a stale Vite build. The script route must load as JavaScript, not 404 or HTML.
3. **419:** likely session/CSRF mismatch; check session cookie domain, `Secure`, DB session access across pods, stable `APP_KEY`, cookie sent on update request, and whether HTTPS is recognized by the upstream proxy. Never disable CSRF as a workaround.
4. **302 to login / 401 / 403:** check staff `web` guard session, cookie, domain, role and persistent middleware on the update route.
5. **404:** check `php artisan route:list --path=livewire`, route cache, `APP_KEY` consistency and proxy rewrite for Livewire 4 hashed routes.
6. **500:** inspect sanitized Laravel/ingress errors for exception class and route. Verify migration and MySQL connectivity. If images only fail, inspect the signed Livewire temporary-upload URL and private media volume.
7. **200 but no persistence:** check JSON response for validation errors; verify the intended fields, database and relevant `wire:model` bindings.

After correcting ingress and deployment config, test saving store settings (using non-sensitive values), shipping tariff, category, product changes and receipt approval against the real target database and browser. Restore the intended settings after tests. CI includes Livewire component persistence smoke tests but cannot verify the production proxy or browser.

**Safety:** Never disable CSRF, permit arbitrary forward headers, expose `.env`, rotate `APP_KEY` to troubleshoot, or run `migrate:fresh` on Production. This change makes no database migrations or changes to payment approval rules.
