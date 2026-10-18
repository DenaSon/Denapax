# Denapax — Agent Handoff & Working Agreement

> **Read this first in every new ChatGPT/Codex/AI development conversation about Denapax.**
> This is a versioned repository handoff, not a substitute for inspecting GitHub's current code, issues or CI before taking actions.
> Snapshot date: **2026-10-19**. Update this file when project status materially changes.

## 1. Project identity and mission

- **Repository:** https://github.com/DenaSon/Denapax · default branch **`master`**.
- **Business:** Dena souvenirs and dried fruit sold through a **regular Telegram Bot** (messages + inline buttons), not a Telegram Mini App or public customer website.
- **Backend:** PHP 8.4, Laravel 13 **modular monolith**, Nutgram Laravel 1.7 / Nutgram 4.
- **Staff admin:** Laravel Fortify login, Livewire 4, Mary UI 2, Tailwind CSS 4 / daisyUI 5; Spatie Permission 8, Media Library 11, Activitylog 5.
- **Intended production services:** MySQL + Redis queue/worker + scheduler, HTTPS Telegram webhook.
- **Developer environment (historical):** PowerShell on Windows, `D:\dena\Projects\Denapax`. Do not assume the assistant can run commands on this computer.

## 2. Current milestone — where to resume

The **six-phase delivery plan is approved**. Phases **1–5 are code-implemented, merged into `master` and passed essential GitHub CI**; Phase **6 has NOT been completed or launched**.

| Phase | What shipped in repository | Merged PR |
| --- | --- | --- |
| 1 — Admin & Catalog | Fortify/RBAC, staff admin, store/card/support settings, categories/products/variants/media | [#30](https://github.com/DenaSon/Denapax/pull/30) |
| 2 — Telegram Storefront & Customers | Bot menus/search, customers, addresses, protected webhook | [#31](https://github.com/DenaSon/Denapax/pull/31) |
| 3 — Cart, Shipping & Checkout | Cart, nationwide gram-based shipping, transactional checkout and snapshots | [#32](https://github.com/DenaSon/Denapax/pull/32) |
| 4 — Manual Payments | Private receipts, staff approval/rejection, multiple attempts | [#33](https://github.com/DenaSon/Denapax/pull/33) |
| 5 — Fulfillment & Notifications | Staff order queue, shipment/tracking, Telegram customer orders, outbox/queue/retry | [#34](https://github.com/DenaSon/Denapax/pull/34) |
| 6 — Real E2E & Launch | **OPEN**: deployment-capable testing, security checks, backups, rollback, release gate | [Epic #11](https://github.com/DenaSon/Denapax/issues/11) |

Other merged PRs: foundation [#1](https://github.com/DenaSon/Denapax/pull/1), process agreement [#29](https://github.com/DenaSon/Denapax/pull/29).
Last Phase 5 merge SHA: `bf6ef852f6f95e3bc494874c5a8795a00c995d75`; a later `master` commit may differ due to documentation updates.

**Issue ledger checked 2026-10-19:** original MVP baseline **7 Epics + 20 User Stories = 27 Issues**; plus **4 separate UX polish tracking Issues #38–#41 = 31 actual Issues (excluding PRs)**; **24 closed, 7 open** (#11, #24, #25, #38, #39, #40, #41). **Eight PRs merged**, including UX documentation [#37](https://github.com/DenaSon/Denapax/pull/37). Re-query GitHub before reporting totals later.

**CI:** PHP 8.4 PHPUnit, Pint, Composer validation, npm/Vite build passed on the merged phase code. **Not evidence of a running/live store.** No confirmed live Telegram Bot API test, MySQL/Redis production-equivalent run, real bank transfer validation, full browser QA or successful E2E order/dispatch; do not imply these have passed.

**Next release milestone remains Phase 6**; additionally, owner-approved Telegram UI/UX improvements are tracked as **four UX sub-phases** [UX-1 #38](https://github.com/DenaSon/Denapax/issues/38), [UX-2 #39](https://github.com/DenaSon/Denapax/issues/39), [UX-3 #40](https://github.com/DenaSon/Denapax/issues/40), [UX-4 #41](https://github.com/DenaSon/Denapax/issues/41). They add no extra core MVP delivery phase. Start with UX-1 and coordinate existing help code on draft Phase 6 PR #36; after each accepted UX phase merge its PR to `master`. These planning issues are **OPEN**, not delivered. **Do not reimplement earlier phases.** See [Phase 6 Runbook](docs/15-phase-6-readiness-plan.md). Track [#24 — E2E/security](https://github.com/DenaSon/Denapax/issues/24) and [#25 — deployment/backup/rollback](https://github.com/DenaSon/Denapax/issues/25), closing [#11](https://github.com/DenaSon/Denapax/issues/11) only when both and their release gates truly pass.

## 3. Source-of-truth reading order

1. **This file**, [Current status and Phase 6 plan](docs/15-phase-6-readiness-plan.md).
2. [Approved MVP Scope v1.1](docs/01-mvp-scope.md); [Architecture](docs/02-architecture.md); [Data dictionary](docs/03-database-design.md); [Workflows](docs/04-workflows.md); **[Telegram UI/UX & Brand Guidelines](docs/telegram-ui-ux-guidelines.md)** (owner-approved greeting and **four** UX polish sub-phases, including native Telegram button styles/icons; issues #38–#41).
3. [Product Backlog: 7 Epics/20 Stories/6 Phases](docs/07-product-backlog.md); [Traceability](docs/08-mvp-traceability.md); [Execution Agreement](docs/09-execution-agreement.md).
4. Phase handoffs [1](docs/10-phase-1-handoff.md), [2](docs/11-phase-2-handoff.md), [3](docs/12-phase-3-handoff.md), [4](docs/13-phase-4-handoff.md), [5](docs/14-phase-5-handoff.md).
5. Actual current `master` files, PRs/Issues, and the latest **successful** CI run. Documentation is not proof of runtime behavior.

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
- Treat external access (live bot token, MySQL, Redis, bank, server) as **not provisioned unless explicitly verified**. If absent, prepare reproducible runbooks and local/mock verification without claiming production tests.
- Keep permissions, isolation, privacy, financial consistency and retries in scope for Phase 6; do not waive security or a release blocker just to close Issues.
- For code changes consult existing Laravel domain Actions, Nutgram adapters, Livewire components, models, migrations and Spatie package migrations first; avoid duplicating models/services.

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
- Set `QUEUE_CONNECTION=redis` and run supervised queue worker for `notifications`; separately run Laravel scheduler (`schedule:run` every minute or managed equivalent). See [Phase 5 handoff](docs/14-phase-5-handoff.md) for timeouts and retry semantics.
- Set Telegram webhook with HTTPS and matching configured secret-token header; do not paste bot token into GitHub Issues or chat output.
- Run end-to-end test order with approval, rejection/resubmission, dispatch/tracking and delivery; perform rollback/restore rehearsal before launch approval.

## 6. New conversation starting instruction

In a **new conversation**, ask the assistant:

> Read `AGENTS.md`, `docs/15-phase-6-readiness-plan.md`, `docs/01-mvp-scope.md`, the open GitHub Issues #11/#24/#25 and current `master` CI. Continue **Denapax Phase 6** from the existing implementation; do not restart phases 1–5. Prioritize actual reproducible E2E testing, security checks, MySQL/Redis/Telegram infrastructure readiness, backup/recovery and release gates. Keep GitHub and docs synchronized; use a branch/PR, essential tests and short Persian delivery reports. Do not claim live tests or launch without evidence.

**Status note:** At time of handoff, the project's ChatGPT Memory is unavailable; **GitHub docs + Issues + code are the durable project memory**. Keep this guide current after real Phase 6 progress.
