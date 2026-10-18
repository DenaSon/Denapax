# Phase 6 — Operator deployment, E2E evidence & recovery runbook

**Status:** PREPARATION ONLY (2026-10-18). This runbook is not evidence of a deployed or launched service.
**Tracking:** [#24 E2E/security](https://github.com/DenaSon/Denapax/issues/24), [#25 deployment/recovery](https://github.com/DenaSon/Denapax/issues/25), [#11 release gate](https://github.com/DenaSon/Denapax/issues/11).
**Owners:** An authorized operator must record test results and explicitly approve launch. Keep credentials, card details, receipt files and customer PII out of GitHub.

## 0. Release gates

- **A — Integration readiness (#25):** authorized staging domain with TLS, actual PHP 8.4/MySQL/Redis, migrations/RBAC, private storage, bot webhook, notifications worker + scheduler. Capture sanitized command output.
- **B — E2E/security (#24):** actually complete a controlled Telegram order through review, dispatch, delivery and notifications; negative-case tests and RTL admin smoke. Log reproducible results, not just expected behavior.
- **C — Recovery/launch (#25, #11):** restore a coordinated database + receipt-media backup into an isolated environment, rehearse a rollback, confirm monitoring/on-call and obtain explicit go/no-go.

**Any missing evidence, untriaged high-severity defect, broken restore, or absent owner approval = NO-GO.** Keep all three Issues open until acceptance criteria are met.

## 1. Prepare isolated staging

Record only host OS, PHP version, extension names, MySQL and Redis **versions**, deployed Git commit, HTTPS hostname (if disclosure permitted), and timestamp. Never record passwords, bot token, secret header value, `.env`, card data or Telegram request bodies.

1. Create a dedicated database/user with least-privilege application permissions, a private Redis instance/namespace, and a non-production Telegram test bot. Restrict inbound database/Redis ports. Configure a real HTTPS reverse proxy; the HTTP document root must be `public/`, **not** the repository root or `storage/`.
2. Inject application secrets from the host's secret manager or a protected environment file excluded from source control. Production-like configuration: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, `DB_CONNECTION=mysql`, `QUEUE_CONNECTION=redis`, `REDIS_QUEUE_RETRY_AFTER=120`, `TELEGRAM_TOKEN`, `TELEGRAM_WEBHOOK_SECRET`, `TELEGRAM_LOG_CHANNEL=null`; set a dedicated `APP_KEY` and appropriate database/Redis auth. Keep `LOG_LEVEL` restrained and ensure application logs are access-controlled.
3. Ensure PHP **8.4** and required extensions (`pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `zip`, `redis`) exist. Review third-party package migrations before first deploy; in particular Spatie permission, media and activity-log tables. Dependency audit is performed by CI and should be repeated on the exact deployable lockfile.
4. Deploy the **reviewed commit**, install Composer dependencies with `--no-dev --optimize-autoloader`, build Vite assets from the locked release, and keep the prior deploy artifact for rollback. Run `php artisan config:cache`, `php artisan route:cache` only if the app supports it, and `php artisan view:cache`. Run `php artisan about` in the target only after validating that output excludes secret values.

### Database and admin gates (staging only)

First take a recoverable baseline snapshot. Then run in this order:

```bash
php artisan migrate:status
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan migrate:status
php artisan denapax:admin operator@example.invalid
php artisan route:list
php artisan schedule:list
```

The example admin address is **not** a credential. Supply a real authorized staff email in your environment; the command prompts interactively for a unique 12+-character password. Verify no public registration route, no default staff account, and permission-restricted admin pages. **Do not run these migration/provisioning commands against an unknown or unbacked production database.**

### Storage privacy gate

- `receipts` disk points to `storage/app/private/receipts` with `serve=false`, outside `public/`. The application user needs limited read/write; the web server must not expose this directory or directory listings.
- Only `storage/app/public` is linked via `public/storage` for catalog media. If needed use `php artisan storage:link` after confirming the configured link target.
- Upload a **synthetic test image** using the test bot. Anonymous requests to the admin receipt-image endpoint must redirect to login; unauthorised staff must receive **403**; permitted `payments.view` staff should receive the image with `Cache-Control: private, no-store`.
- Confirm direct `/storage/payment-receipts/...`, traversal paths and guessed private paths cannot retrieve any bytes. Do not store or screenshot real banking information in reports.

### Telegram + worker gate

- Configure webhook through a secured operator session using the Bot API `setWebhook` with **exact** HTTPS `/api/telegram/webhook` and the `TELEGRAM_WEBHOOK_SECRET` header value. Do not paste token-bearing URLs or bot API responses into issues, shell transcripts, browser histories or telemetry. Query `getWebhookInfo` safely and check the actual target and delivery errors.
- Verify one authentic inbound test update is accepted, a forged secret is rejected, and replay of the same `update_id` does not repeat business actions.
- Run a **supervised** Redis worker, for example the service's command:
  `php artisan queue:work redis --queue=notifications,default --tries=1 --timeout=60 --sleep=3`.
  Set process restart and logs; the Redis queue retry window **120s must exceed** worker timeout 60s. After deployment, restart workers with `php artisan queue:restart`.
- Run Laravel scheduler every minute under one designated scheduler host: `* * * * * ... php artisan schedule:run` (absolute paths; appropriate service account). Never run multiple unsupervised schedulers accidentally.
- Prove outbox poller `denapax:notifications:dispatch` runs, notifications transition from queued to sent, failed provider delivery retries within bounded attempts, and exhausted failures are visible in the admin notification log. Telegram delivery is **at least once** on ambiguous network failures; duplicates remain possible.

## 2. Actual E2E acceptance record (#24)

Use a dedicated staging bot, two synthetic test customer accounts, one restricted staff operator and one privileged reviewer, with test products only. For bank approval, obtain an authorized **controlled transfer with independently verified settlement** or mark that step BLOCKED; never infer payment solely from an uploaded image. Suppress real-customer dispatch claims.

| ID | Test/evidence requirement | Status |
| --- | --- | --- |
| B01 | Bot browsing/search and product/variant image and availability | UNVERIFIED |
| B02 | Two-customer addresses and cross-customer isolation | UNVERIFIED |
| B03 | Multi-item cart, gram/packaging tier edges, IRR calculation and toman display | UNVERIFIED |
| B04 | Checkout replay yields one order; snapshots survive later price/address changes | UNVERIFIED |
| B05 | Invalid, oversized, fake/non-image receipt rejected; private image ACLs enforced | UNVERIFIED |
| B06 | Receipt reject + reason, resubmit, controlled bank-verification approval | UNVERIFIED |
| B07 | Approved-only transition confirmed → preparing → shipped → delivered, synthetic tracking | UNVERIFIED |
| B08 | All eight customer notifications; failures, retries, exhausted logs and duplicate caveat | UNVERIFIED |
| B09 | Forged/repeated webhook, invalid callbacks, unavailable variant and forbidden staff actions | UNVERIFIED |
| B10 | Persian RTL/mobile browser: products, settings, review, orders, notification logs | UNVERIFIED |

For each row attach: staging identifier (not secret), **commit SHA**, date/time with timezone, responsible tester, sanitized command or UI evidence link, expected vs actual, pass/fail, and defect/issue reference. Automated CI with SQLite and mocked Telegram **does not** count as a PASS for this table.

## 3. Backups, restore and rollback (#25)

### Backup requirements

1. Establish a schedule, retention, encryption-at-rest/in-transit, off-host storage, storage-quota alerts and a designated operator. Cover **both MySQL data and private receipt files**, not only product media. Keep `APP_KEY` and secrets separately in the approved secret vault for recovery.
2. For the initial **consistent recovery rehearsal**, stop new writes: enter maintenance mode and drain/stop the queue worker and scheduler under operator control. Confirm no active webhook requests, payment approvals or receipt uploads remain before snapshot. Where necessary, temporarily disable Telegram webhook delivery and plan for pending updates.
3. Capture a consistent transactional MySQL dump (for example with `mysqldump --single-transaction --routines --triggers --events` using credential-safe client configuration) and an archive of `storage/app/private/receipts` with owner/ACL metadata. Pair both artifacts under one opaque backup identifier and UTC timestamp. Do not print backup contents or command lines containing secrets.
4. Encrypt, checksum and transfer both artifacts together to restricted, off-host storage. Verify file counts and checksums. Restore the webhook and supervised processes only when all archives are durable and the application is healthy. Ongoing hot backups require a proven consistency strategy (quiescing/snapshot coordination or DB+media transaction/manifest); two unrelated periodic backups alone are **not** proven recoverable.

### Mandatory isolated restore rehearsal

1. Provision a new **non-production** database, file store and secret scope. Ensure restored worker/scheduler and webhook are **disabled** and outbound Telegram sends cannot reach customers.
2. Restore the paired SQL dump and private receipt archive. Apply correct file ownership; supply dedicated staging secrets/APP_KEY per the vetted recovery strategy.
3. Run `php artisan migrate:status`, compare order/receipt counts and random synthetic receipt integrity hashes, validate private access and permission checks, and query a test order without sending messages.
4. Record artifact IDs (never signed download links), backup/restore timestamps, hashes, elapsed recovery time, RPO/RTO achieved, tester and pass/fail. Destroy isolated sensitive copies under retention policy.

### Rollback decision

- Preserve the previous application release and assets. If code only fails, point the service at the prior tested release, clear/rebuild caches and `queue:restart` with worker/scheduler coordination.
- **Do not blindly run `migrate:rollback` on production.** Schema backward compatibility must be reviewed per migration; if a destructive/mismatched schema exists, stop all writes, notify the operator and restore a **paired** backup using the documented recovery procedure after assessing data loss.
- Halt launch on unrecoverable receipts, order/receipt inconsistency, unauthorized access, payment-state corruption, notification job loss or unexplained duplicate money actions.

## 4. Monitoring and sign-off

At minimum verify an authenticated HTTPS admin health flow and Laravel `/up` endpoint behind appropriate reverse-proxy rules, a Redis queue/worker process health check, scheduler last-run freshness, DB availability, disk pressure, application errors, notification retry/failure counts and expiring TLS. Keep traces/logs free of tokens, customer PII, card instructions and receipt contents.

**Release decision template (to be completed by an authorized person, not an AI):**

- Target release SHA / staging URL: **UNSET**
- Gate A evidence / result: **MISSING — NO-GO**
- Gate B evidence / result: **MISSING — NO-GO**
- Gate C restore and rollback evidence / result: **MISSING — NO-GO**
- Open severity-1 findings: **NOT REVIEWED**
- Backup ID, RPO/RTO, incident contact and on-call owner: **UNSET**
- Approving operator and timestamp / explicit **GO or NO-GO**: **UNSET — NO-GO**

Once all three gates truly pass, update Issues #24/#25 with sanitized evidence and close them. Only then close Epic #11, merge the completed Phase 6 branch with essential CI green, and record the verified `master` SHA. No public Telegram traffic before explicit approval.
