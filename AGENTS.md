# Denapax — Agent Handoff & Working Agreement

**Phase 9 (in review):** Internal Telegram group alerts for three operational events — new order, receipt awaiting review, and payment approved. Code/CI status must be verified in [Issue #64](https://github.com/DenaSon/Denapax/issues/64). Configuration and activation/runbook: [docs/27-phase-9-admin-group-alerts.md](docs/27-phase-9-admin-group-alerts.md). A single negative `TELEGRAM_ADMIN_CHAT_ID` enables alerts **after** the initial activation command (historical events intentionally skipped); no real group ID/token in Git. Production smoke not yet verified. Product-gallery issue remains separately awaiting user retest.


**Verified closeout (2026-10-19):** Both Post-MVP Sprint 1 phases are merged and code-complete. Phase 7 [PR #61](https://github.com/DenaSon/Denapax/pull/61) → `7aa0b10`; Phase 8 [PR #62](https://github.com/DenaSon/Denapax/pull/62) → `87f3c92`. [Master CI run 39248240505](https://github.com/DenaSon/Denapax/actions/runs/39248240505) is green. Phase 8: 69 tests / 383 assertions; Pint, Composer and Vite passed. [#56](https://github.com/DenaSon/Denapax/issues/56), [#57](https://github.com/DenaSon/Denapax/issues/57), [#58](https://github.com/DenaSon/Denapax/issues/58) closed as *code-delivered*. **Production Telegram/device smoke and independent E07 security/backup gates #11/#24/#25 remain unverified/open; no claim of live rollout in this documentation update.** Admin private-group alerts are deferred and unimplemented.


> **Read this first in every new ChatGPT/Codex/AI development conversation about Denapax.**
> This is a versioned repository handoff, not a substitute for inspecting GitHub's current code, issues or CI before taking actions.
> Snapshot date: **2026-10-19**. Update this file when project status materially changes.

## 1. Project identity and mission

- **Repository:** https://github.com/DenaSon/Denapax · default branch **`master`**.
- **Business:** Dena souvenirs and dried fruit sold through a **regular Telegram Bot** (messages + inline buttons), not a Telegram Mini App or public customer website.
- **Backend:** PHP 8.4, Laravel 13 **modular monolith**, Nutgram Laravel 1.7 / Nutgram 4.
- **Staff admin:** Laravel Fortify login, Livewire 4, Mary UI 2, Tailwind CSS 4 / daisyUI 5; Spatie Permission 8, Media Library 11, Activitylog 5.
- **Current owner-approved deployment direction:** MySQL + Laravel **database queue** worker (including `notifications`) + scheduler + HTTPS Telegram webhook. **Redis and Horizon deferred; do not install/configure them.**
- **Developer environment (historical):** PowerShell on Windows, `D:\dena\Projects\Denapax`. Do not assume the assistant can run commands on this computer.

## 2. Current milestone — where to resume

The **six-phase MVP plan** delivered Phases **1–5** to `master` with passing core CI. **Owner update (2026-10-19): the MVP is now live; owner-reported functional E2E checks passed.** Phase **6 remains formally OPEN** on #11/#24/#25 until its independent security/negative-path, backup/restore and operational evidence is recorded. Do not conflate functional acceptance with a complete E07 release gate.

| Phase | What shipped in repository | Merged PR |
| --- | --- | --- |
| 1 — Admin & Catalog | Fortify/RBAC, staff admin, store/card/support settings, categories/products/variants/media | [#30](https://github.com/DenaSon/Denapax/pull/30) |
| 2 — Telegram Storefront & Customers | Bot menus/search, customers, addresses, protected webhook | [#31](https://github.com/DenaSon/Denapax/pull/31) |
| 3 — Cart, Shipping & Checkout | Cart, nationwide gram-based shipping, transactional checkout and snapshots | [#32](https://github.com/DenaSon/Denapax/pull/32) |
| 4 — Manual Payments | Private receipts, staff approval/rejection, multiple attempts | [#33](https://github.com/DenaSon/Denapax/pull/33) |
| 5 — Fulfillment & Notifications | Staff order queue, shipment/tracking, Telegram customer orders, outbox/queue/retry | [#34](https://github.com/DenaSon/Denapax/pull/34) |
| 6 — Real E2E & Launch | **Live functional smoke owner-approved; formal security/backup/restore/release evidence still open** | [Epic #11](https://github.com/DenaSon/Denapax/issues/11) |

Other merged PRs: foundation [#1](https://github.com/DenaSon/Denapax/pull/1), process agreement [#29](https://github.com/DenaSon/Denapax/pull/29).
Last Phase 5 merge SHA: `bf6ef852f6f95e3bc494874c5a8795a00c995d75`; a later `master` commit may differ due to documentation updates.

**Issue ledger note (2026-10-19):** UX-1..4 code PRs #43–#46 merged; UX Issues #38–#41 closed. Setup [Issue #47](https://github.com/DenaSon/Denapax/issues/47) / [PR #48](https://github.com/DenaSon/Denapax/pull/48) add baseline and opt-in demo seeders. Phase 6 #11/#24/#25 remains open. Re-query GitHub before quoting issue/PR totals.

**CI (verified 2026-10-19):** current master `c5fa82d5376129b5e2da13689239bce646dfdf1b`, [successful CI run 39229685750](https://github.com/DenaSon/Denapax/actions/runs/39229685750) (64 tests reported in #24). **Owner separately reports the MVP is live and exercised functional E2E behavior is correct.** Specific high-risk negative cases and full ops/recovery checks are **not yet evidenced** in GitHub; do not imply those passed.

**Next milestone:** owner-reported functional MVP launch acceptance is recorded; Phase 6 formally stays OPEN under #11/#24/#25 pending remaining proof. **Post-MVP Sprint 1** feedback is finalized at **17 approved items**. Phase 7 (7 UI/microcopy items) was merged in [PR #61](https://github.com/DenaSon/Denapax/pull/61) at `7aa0b10` with green master CI. Phase 8 (10 UX items) is implemented under [PR #62](https://github.com/DenaSon/Denapax/pull/62); verify its actual merge/CI status: [intake #56](https://github.com/DenaSon/Denapax/issues/56), [Phase 7 #57](https://github.com/DenaSon/Denapax/issues/57) and [Phase 8 #58](https://github.com/DenaSon/Denapax/issues/58); see [Post-MVP Sprint 1 plan](docs/24-post-mvp-sprint-1.md). UX-1..4 were previously merged to `master`; do not duplicate them.  Setup [PR #48](https://github.com/DenaSon/Denapax/pull/48) adds idempotent initial settings and exactly three opt-in inactive demo products; details in [Seeder Handoff](docs/21-initial-seeders-handoff.md). Current owner choice is database queue, **not** Redis/Horizon. Former Phase 6 draft [#36](https://github.com/DenaSon/Denapax/pull/36) was closed as superseded; its still-useful security prep and operator runbook were reconciled into merged [#49](https://github.com/DenaSon/Denapax/pull/49). Phase 6 issues #11/#24/#25 remain OPEN.


**Runflare HTTPS / browser outage diagnostics:** [request middleware secure URL generation and runtime log analysis](docs/23-runflare-https-livewire-mixed-content.md). Public TLS is terminated upstream of PHP; HTTPS URLs for Livewire JS/logout are generated per request by `ForceHttpsUrls` middleware based solely on production `APP_URL=https://...`. No hardcoded real domain, no redirect, no global provider force and no unverified `TRUSTED_PROXIES`. 2026-10-19 logs show Nginx/PHP/Artisan and internal HTTP `/up` healthy (200) even while public browser reported `ERR_CONNECTION_CLOSED`; public ingress/TLS routing needs separate investigation. Also fix empty `TELEGRAM_LOG_CHANNEL` and disable `APP_DEBUG`. Do not reset keys or weaken CSRF. Actual public browser verification still pending.

**Runflare initial MySQL migration issue:** [1071 index key compatibility fix and safe recovery instructions](docs/22-mysql-index-compatibility.md). Schema defaults to 191 indexed-safe chars, Spatie role/permission composite keys are bounded and MySQL uses InnoDB. If first migration failed, operator must verify whether a partially created empty users table exists and back up before any cleanup; do not use migrate:fresh against live data. Live Runflare migration success is unverified.

**Telegram customer Reply Keyboard (2026-10-19):** The owner approved persistent bottom-of-chat `ReplyKeyboardMarkup` with four rows (products/search; cart/orders; receipts/support; help/home). `StorefrontReplyKeyboard` holds button labels and command mapping; `StorefrontBot::menu` sends it on `/start` or home; tapping a menu button during address/search/receipt input resets the active session before navigating. **All inner-page InlineKeyboards/callbacks are preserved.** See [Telegram UI/UX guide](docs/telegram-ui-ux-guidelines.md). Do not equate automated tests with live Telegram-client verification.

## 3. Source-of-truth reading order

1. **This file**, [Current status and Phase 6 plan](docs/15-phase-6-readiness-plan.md).
   Also read the [current Phase 6 operator runbook](docs/16-phase-6-operator-runbook.md) for database-queue staging, real E2E evidence, backup/restore and owner go/no-go; do not follow obsolete Redis-focused draft PR #36 instructions.
2. [Approved MVP Scope v1.1](docs/01-mvp-scope.md); [Architecture](docs/02-architecture.md); [Data dictionary](docs/03-database-design.md); [Workflows](docs/04-workflows.md); **[Telegram UI/UX & Brand Guidelines](docs/telegram-ui-ux-guidelines.md)** (owner-approved greeting and **four** UX polish sub-phases, including native Telegram button styles/icons; issues #38–#41).
3. [Product Backlog: 7 Epics/20 Stories/6 Phases](docs/07-product-backlog.md); [Traceability](docs/08-mvp-traceability.md); [Execution Agreement](docs/09-execution-agreement.md).
4. Phase handoffs [1](docs/10-phase-1-handoff.md), [2](docs/11-phase-2-handoff.md), [3](docs/12-phase-3-handoff.md), [4](docs/13-phase-4-handoff.md), [5](docs/14-phase-5-handoff.md); [UX-1 brand/navigation handoff](docs/17-ux-1-handoff.md) (merged PR #43), [UX-2 styles/icons handoff](docs/18-ux-2-handoff.md) (merged PR #44), [UX-3 catalog/cart handoff](docs/19-ux-3-handoff.md) (merged PR #45), [UX-4 checkout/payment/notification handoff](docs/20-ux-4-handoff.md) (merged PR #46; real Telegram/mobile proof and full E2E still pending). [Initial seeders and database queue runbook](docs/21-initial-seeders-handoff.md).
5. [Phase 7 UI/microcopy implementation handoff](docs/25-phase-7-ui-microcopy-handoff.md) when reviewing the Post-MVP sprint; note that it is not a merge/CI claim.
6. Actual current `master` files, PRs/Issues, and the latest **successful** CI run. Documentation is not proof of runtime behavior.

## 4. Non-negotiable MVP rules

- Product has exactly one category; predefined variants have independent **integer IRR price**, sale quantity/unit, positive shipping weight in **integer grams**, and boolean availability; **no numeric inventory**.
- Single nationwide shipping rule: item grams + one shared packaging weight, base fee + ceil-based additional weight steps; no carrier API or regional rate.
- Telegram customer identity is separate from staff users; no cross-customer access to addresses, orders, receipts. Staff actions need server-side permissions.
- Checkout revalidates the authoritative cart and creates immutable product/variant, quantity, price, recipient address, shipping/tariff and card-instruction snapshots; guard against repeated checkout.
- Payment is **card-to-card**; image submission never proves payment. Only a permitted staff reviewer can approve/reject after independent bank verification, with reason/history.
- Private receipts may never be served as public assets. Telegram tokens, `.env`, actual customer data, private files and credentials never enter Git or logs.
- Fulfillment state machine and manual tracking; transactional outbox notifications with bounded retry, acknowledging Telegram cannot guarantee exactly-once message delivery.
- **Out of scope:** Mini App, web customer shop, online payment gateway, automated bank verification, inventory counts, dynamic regional/carrier rates, refunds automation, coupons, marketing, complex RBAC GUI.

## 5. Engineering workflow required by owner

- Work on a scoped branch and reviewable PR; **merge each completed phase into `master` only after green essential CI** and verifying the actual merge SHA. Never force push `master`.
- Focus tests on meaningful business paths and high-risk failures; avoid excessive repetitive tests.
- Finish each phase with a short **Persian** summary: delivered, tests/CI, merged PR+SHA, untested/risks, next step.
- Treat external access (live bot token, MySQL, actual database queue worker, bank, server) as **not provisioned unless explicitly verified**. Never claim production tests from mocked CI.
- Keep permissions, isolation, privacy, financial consistency and retries in scope for Phase 6; do not waive security or a release blocker just to close Issues.
- For code changes consult existing Laravel domain Actions, Nutgram adapters, Livewire components, models, migrations and Spatie package migrations first; avoid duplicating models/services.
- **Draft PR #36 retired:** Old Telegram UI and Redis assumptions were intentionally not merged; [PR #49](https://github.com/DenaSon/Denapax/pull/49) brought all still-relevant security preparation onto master and [PR #36](https://github.com/DenaSon/Denapax/pull/36) was closed as superseded. Keep current UX-1..4 and database-queue decisions authoritative.

### Baseline verification (in a configured local/CI checkout)

```powershell
composer install
php artisan test
vendor/bin/pint --test
composer validate --no-check-publish
npm install
npm run build
php artisan route:list
php artisan migrate:status
```

The above is a **verification reference**, not a claim it ran on the owner's Windows machine. Inspect `.env` without exposing secrets before any migration, worker or webhook action. Never execute destructive migrations against an unbacked production database.

### Release-specific operating processes (only in configured test/prod environment)

- Verify real MySQL migrations/published Spatie tables, restricted staff creation via `php artisan denapax:admin`, storage privacy and backups.
- Set `QUEUE_CONNECTION=database`; run a supervised worker (`php artisan queue:work database --queue=notifications,default --tries=1 --timeout=60`) and scheduler (`schedule:run` every minute or managed equivalent). **No Redis/Horizon installation is required.** See [seeder/queue runbook](docs/21-initial-seeders-handoff.md).
- Set Telegram webhook with HTTPS and matching configured secret-token header; do not paste bot token into GitHub Issues or chat output.
- Run end-to-end test order with approval, rejection/resubmission, dispatch/tracking and delivery; perform rollback/restore rehearsal before launch approval.

## 6. New conversation starting instruction

In a **new conversation**, ask the assistant:

> Read `AGENTS.md`, [Post-MVP Sprint 1 plan](docs/24-post-mvp-sprint-1.md) and GitHub issues #56/#57/#58 plus the remaining Phase 6 gates #11/#24/#25. MVP is live with owner-reported functional E2E pass. The owner has **closed the feedback list at 17 items**. Review approved issue checklists #57 (7 UI/copy items) and #58 (10 UX items) and parent #56. Phase 7 was completed and merged. Check Phase 8 [PR #62](https://github.com/DenaSon/Denapax/pull/62), [Issue #58](https://github.com/DenaSon/Denapax/issues/58), and [handoff](docs/26-phase-8-ux-handoff.md); verify CI, merge and rollout separately. E07 operator/privacy/DR checks remain independently tracked. Parent #56 stays open until both phases are delivered. Separately preserve evidence-based E07 security, backup/restore and operational release checks.

**Status note:** At time of handoff, the project's ChatGPT Memory is unavailable; **GitHub docs + Issues + code are the durable project memory**. Keep this guide current after real Phase 6 progress.
