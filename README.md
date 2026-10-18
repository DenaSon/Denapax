# Denapax 🌰

Telegram-first store for Dena souvenirs and dried fruits.

## Project status
**GitHub status checked 2026-10-19 (UX-3 kickoff):** **31 actual Issues** = 27 original MVP and four UX #38–#41; **26 closed, 5 open** (#11, #24, #25, #40, #41); **11 merged PRs** through [UX-2 #44](https://github.com/DenaSon/Denapax/pull/44). UX-3 implementation [PR #45](https://github.com/DenaSon/Denapax/pull/45) is pending verification/merge at this snapshot; re-check GitHub for latest status.

**Phase:** All five principal MVP implementation phases are merged to `master` with passing CI. Phase 6 — integrated real-world testing, security/operational hardening and launch readiness — remains OPEN. Telegram UX polish is tracked in **four sub-phases UX-1..UX-4** ([guide](docs/telegram-ui-ux-guidelines.md)); UX-1 and UX-2 were merged, UX-3 is underway, UX-4 is upcoming. This does not replace Phase 6. No live Telegram, real MySQL/Redis or banking trial has been claimed.

## MVP channels
- Customer-facing: Telegram Bot (messages, inline buttons, search, cart, order tracking).
- Operations: Laravel + Livewire 4 + Mary UI 2 web admin (Fortify login and Spatie Permission RBAC).
- Backend: Laravel 13 on PHP 8.4, Nutgram Telegram adapter, Modular Monolith, MySQL and Redis queues.
- Product images: Spatie Media Library; audit actions: Spatie Activitylog. Current CI runs PHPUnit + Pint; Pest and Larastan are planned but not installed yet.

## Documentation
- **[AGENTS.md — start here in a new AI/chat development session](AGENTS.md)**
- **[Phase 6 readiness plan and accurate current status](docs/15-phase-6-readiness-plan.md)**
- **[Telegram UI/UX & brand guidelines — approved greeting, native button styles/icons and four polish sub-phases](docs/telegram-ui-ux-guidelines.md)**
- [UX-1 handoff — welcome, help, direct Telegram support and navigation (merged PR #43)](docs/17-ux-1-handoff.md)
- [UX-2 handoff — native Telegram button styles and plain emoji fallback](docs/18-ux-2-handoff.md)
- [UX-3 handoff — compact catalog, gallery and multi-page cart](docs/19-ux-3-handoff.md)
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

**Next action:** Phase 6 should exercise the real Telegram → cart → checkout → receipt → manual approval/rejection → shipment → notifications cycle using the target MySQL/Redis environment; inspect privacy/security, queues, backups/restore, visual QA and production deployment readiness. See [Phase 5 handoff](docs/14-phase-5-handoff.md).

For authoritative scope, data rules and acceptance criteria, see the documentation links above.
