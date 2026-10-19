# Denapax 🌰

Telegram-first store for Dena souvenirs and dried fruits.

## Project status

The security preparation originally in draft Phase 6 PR #36 has been reconciled into a scoped update against current `master` (safe webhook error logs, private-receipt regression tests, locked Composer audit, and the operator runbook). Integrated and verified in merged [PR #49](https://github.com/DenaSon/Denapax/pull/49) ([master CI](https://github.com/DenaSon/Denapax/actions/runs/39209851902)); old [PR #36](https://github.com/DenaSon/Denapax/pull/36) was closed as superseded. This is **not** production deployment, live E2E, or Phase 6 approval. Horizon and Redis are deferred: use Laravel database queues.

**Status 2026-10-19 (owner update):** MVP is **live**, and exercised functional E2E purchase flows have been reported working by the owner. Last verified `master` SHA: `c5fa82d5376129b5e2da13689239bce646dfdf1b` ([green CI](https://github.com/DenaSon/Denapax/actions/runs/39229685750)). This does **not** establish that the Phase 6 security, negative-path, backup/restore or operational evidence has passed; release issues [#11](https://github.com/DenaSon/Denapax/issues/11)/[#24](https://github.com/DenaSon/Denapax/issues/24)/[#25](https://github.com/DenaSon/Denapax/issues/25) remain open. UX-1..4 PRs #43–#46 and issues #38–#41 are complete. [Post-MVP Sprint 1](docs/24-post-mvp-sprint-1.md) feedback is now **closed/frozen at 17 owner-approved items**: 7 UI/copy items in #57, 10 UX items in #58; parent #56 tracks delivery. No application implementation has started.

**Phase:** Phases 1–5 completed/merged. **Phase 6 functional acceptance has been reported from live testing by the owner**, while its *formal* E07 release sign-off remains open pending documented evidence. UX-1 through UX-4 were merged previously (last: [PR #46](https://github.com/DenaSon/Denapax/pull/46)); Post-MVP Sprint 1 is separate and contains **Phase 7: UI/copy** and **Phase 8: UX flows**, both now planned against the **owner-approved 17-item feedback backlog** (7 Phase 7; 10 Phase 8).

## MVP channels
- Customer-facing: Telegram Bot (messages, inline buttons, search, cart, order tracking).
- Operations: Laravel + Livewire 4 + Mary UI 2 web admin (Fortify login and Spatie Permission RBAC).
- Backend: Laravel 13 on PHP 8.4, Nutgram Telegram adapter, Modular Monolith, MySQL and Laravel database-backed queue (no Redis/Horizon requirement).
- Product images: Spatie Media Library; audit actions: Spatie Activitylog. Current CI runs PHPUnit + Pint; Pest and Larastan are planned but not installed yet.

## Documentation
- **[AGENTS.md — start here in a new AI/chat development session](AGENTS.md)**
- **[Persistent Telegram Reply Keyboard (bottom customer menu) and inline sub-flows](docs/telegram-ui-ux-guidelines.md#main-reply-keyboard-persistent-two-columns)**
- **[Post-MVP Sprint 1 — feedback intake, Phase 7 UI and Phase 8 UX](docs/24-post-mvp-sprint-1.md)**
- **[Phase 6 readiness plan and remaining operational evidence](docs/15-phase-6-readiness-plan.md)**
- **[Phase 6 operator runbook — staging, webhook, database queue and recovery](docs/16-phase-6-operator-runbook.md)**
- **[Telegram UI/UX & brand guidelines — approved greeting, native button styles/icons and four polish sub-phases](docs/telegram-ui-ux-guidelines.md)**
- [UX-1 handoff — welcome, help, direct Telegram support and navigation (merged PR #43)](docs/17-ux-1-handoff.md)
- [UX-2 handoff — native Telegram button styles and plain emoji fallback](docs/18-ux-2-handoff.md)
- [UX-3 handoff — compact catalog, gallery and multi-page cart](docs/19-ux-3-handoff.md)
- [UX-4 handoff — checkout, receipts, payment states, shipping and notifications](docs/20-ux-4-handoff.md)
- [Initial settings and three opt-in demo products; database queue runbook](docs/21-initial-seeders-handoff.md)
- [Runflare HTTPS mixed-content fix — Livewire JS / admin logout](docs/23-runflare-https-livewire-mixed-content.md)
- [MVP Scope v1.1](docs/01-mvp-scope.md)
- [Architecture and boundaries](docs/02-architecture.md)
- [Database ERD and Data Dictionary](docs/03-database-design.md)
- [Order, payment, and notification flows](docs/04-workflows.md)
- [Packages, version policy and PowerShell bootstrap](docs/05-stack-and-windows-setup.md)
- [Foundation implementation & handoff](docs/06-foundation-handoff.md)
- [MVP epics, user stories and six delivery phases](docs/07-product-backlog.md)
- [MVP requirements-to-stories traceability matrix](docs/08-mvp-traceability.md)
- [Phase 2 Telegram and Customers handoff](docs/11-phase-2-handoff.md)
- [Phase 3 Cart, Shipping and Checkout handoff](docs/12-phase-3-handoff.md)
- [Phase 4 private manual payments handoff](docs/13-phase-4-handoff.md)
- [Phase 5 fulfillment, tracking and notifications handoff](docs/14-phase-5-handoff.md)
- [Project execution agreement (phase merges, lean tests and short reports)](docs/09-execution-agreement.md)


**Runflare MySQL first migration (SQLSTATE 1071):** the UTF8MB4 indexed-column limit fix and safe partial-migration recovery are documented in [MySQL index compatibility](docs/22-mysql-index-compatibility.md). Never run destructive migration reset on a database with data.

## Initial data and non-Redis queue

Safe initialization on an authorized, backed-up database:

```bash
php artisan migrate --force
php artisan db:seed
```

Creates/keeps RBAC plus missing store/shipping defaults only. **Real card/holder/support and delivery fees must be configured in the admin before accepting orders**; default shipping fees are zero placeholders, not approved tariffs.

For **local, testing or staging only**, opt into three **unpublished** catalog fixtures:

```bash
php artisan db:seed --class=DemoProductsSeeder
```

Three sample products: انجیر خشک نمونه، مغز گردو نمونه، کشمش نمونه. Their prices are fictional and variants unavailable; no demo data is created by the normal db:seed, and production execution is blocked. See [seeders handoff](docs/21-initial-seeders-handoff.md).

Horizon and Redis are **not part of current scope**. The default queue is database; background Telegram notifications still require a supervised `php artisan queue:work database --queue=notifications,default --tries=1 --timeout=60` process **plus** the Laravel scheduler.

## Key decisions
- Product variants have predefined sale quantities, units and independent prices.
- Shipping weight is stored in **grams** on every variant, independent from its sale unit.
- Shipping is a nationwide formula: base tier + increments above the base tier; settings are admin-configurable.
- Payment is card-to-card with customer receipt upload and **manual admin approval**.
- A customer may re-upload a receipt after rejection; all attempts are retained.
- Order/payment status changes trigger transactional Telegram notifications.
- Order lines, address and shipping fee are snapshotted at checkout.
- Inventory is available/unavailable only in the MVP; no numeric stock tracking.

> **Security:** Do not commit `.env`, bot tokens, cardholder secrets, customer PII, or payment receipt images.

**Next action:** Implement owner-approved [Post-MVP Sprint 1](docs/24-post-mvp-sprint-1.md) in order — **Phase 7 UI/copy (#57, seven findings)** followed by **Phase 8 UX (#58, ten findings)** — using lean tests, real Telegram checks, and a green CI PR/merge to master at each phase exit. Separately, record outstanding Phase 6 negative/security, backup/restore and operational evidence in #24/#25/#11. The owner has confirmed functional live E2E acceptance; do not claim unverified gates as complete. See [Phase 5 handoff](docs/14-phase-5-handoff.md).

For authoritative scope, data rules and acceptance criteria, see the documentation links above.
