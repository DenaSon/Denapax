# Denapax 🌰

Telegram-first store for Dena souvenirs and dried fruits.

## Project status

The security preparation originally in draft Phase 6 PR #36 has been reconciled into a scoped update against current `master` (safe webhook error logs, private-receipt regression tests, locked Composer audit, and the operator runbook). Integrated and verified in merged [PR #49](https://github.com/DenaSon/Denapax/pull/49) ([master CI](https://github.com/DenaSon/Denapax/actions/runs/39209851902)); old [PR #36](https://github.com/DenaSon/Denapax/pull/36) was closed as superseded. This is **not** production deployment, live E2E, or Phase 6 approval. Horizon and Redis are deferred: use Laravel database queues.

**Status 2026-10-19:** UX-1..4 code PRs #43–#46 are merged, with UX issues #38–#41 closed. [Setup issue #47](https://github.com/DenaSon/Denapax/issues/47) and [PR #48](https://github.com/DenaSon/Denapax/pull/48) cover idempotent initialization seeders. Phase 6 release issues #11/#24/#25 remain open. Verify latest GitHub status before reporting counts.

**Phase:** All five principal MVP implementation phases are merged to `master` with passing CI. Phase 6 — integrated real-world testing, security/operational hardening and launch readiness — remains OPEN. Telegram UX polish is tracked in **four sub-phases UX-1..UX-4** ([guide](docs/telegram-ui-ux-guidelines.md)); UX-1, UX-2, UX-3 and UX-4 are all merged (last: [PR #46](https://github.com/DenaSon/Denapax/pull/46)). This does not replace Phase 6. No live Telegram, real MySQL/database queue or banking trial has been claimed.

## MVP channels
- Customer-facing: Telegram Bot (messages, inline buttons, search, cart, order tracking).
- Operations: Laravel + Livewire 4 + Mary UI 2 web admin (Fortify login and Spatie Permission RBAC).
- Backend: Laravel 13 on PHP 8.4, Nutgram Telegram adapter, Modular Monolith, MySQL and Laravel database-backed queue (no Redis/Horizon requirement).
- Product images: Spatie Media Library; audit actions: Spatie Activitylog. Current CI runs PHPUnit + Pint; Pest and Larastan are planned but not installed yet.

## Documentation
- **[AGENTS.md — start here in a new AI/chat development session](AGENTS.md)**
- **[Phase 6 readiness plan and accurate current status](docs/15-phase-6-readiness-plan.md)**
- **[Phase 6 operator runbook — staging, webhook, database queue and recovery](docs/16-phase-6-operator-runbook.md)**
- **[Telegram UI/UX & brand guidelines — approved greeting, native button styles/icons and four polish sub-phases](docs/telegram-ui-ux-guidelines.md)**
- [UX-1 handoff — welcome, help, direct Telegram support and navigation (merged PR #43)](docs/17-ux-1-handoff.md)
- [UX-2 handoff — native Telegram button styles and plain emoji fallback](docs/18-ux-2-handoff.md)
- [UX-3 handoff — compact catalog, gallery and multi-page cart](docs/19-ux-3-handoff.md)
- [UX-4 handoff — checkout, receipts, payment states, shipping and notifications](docs/20-ux-4-handoff.md)
- [Initial settings and three opt-in demo products; database queue runbook](docs/21-initial-seeders-handoff.md)
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

**Next action:** Phase 6 should exercise the real Telegram → cart → checkout → receipt → manual approval/rejection → shipment → notifications cycle using MySQL and the database queue worker/scheduler (Redis/Horizon deferred); inspect privacy/security, queues, backups/restore, visual QA and production deployment readiness. See [Phase 5 handoff](docs/14-phase-5-handoff.md).

For authoritative scope, data rules and acceptance criteria, see the documentation links above.
