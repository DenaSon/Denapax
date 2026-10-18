# Denapax — MVP v1.1 Requirements Traceability (planning audit)

**Date:** 2026-10-18  
**Source of truth:** [MVP Scope v1.1](01-mvp-scope.md), [Architecture v1.1](02-architecture.md), [Database Design v1.1](03-database-design.md) and [Workflows v1.1](04-workflows.md).  
**Audit status:** Requirements mapped to [Backlog v1.1](07-product-backlog.md); **mapping is not proof of completed implementation**.

## Coverage matrix

| MVP requirement / invariant | Stories | Verification gate |
| --- | --- | --- |
| Staff-only auth with Fortify, no public signup; restricted roles/permissions | [#2](https://github.com/DenaSon/Denapax/issues/2) | Unauthenticated / unauthorized requests denied; role tests |
| Catalog: one category per product, active flags, summary/description and product-level features | [#3](https://github.com/DenaSon/Denapax/issues/3), [#12](https://github.com/DenaSon/Denapax/issues/12) | Schema, validation and admin CRUD tests |
| Variants with predefined sale unit/quantity, independent IRR price, positive shipping grams, availability only (no stock counts) | #3, #12 | Form/DB constraints + selling rules tested |
| Catalog gallery + optional single variant photo via Spatie media | #3, #12 | Public catalog media; private receipts stay separate |
| Telegram-only customer surface (messages/inline buttons), webhook verification, update dedupe and conversation state | [#4](https://github.com/DenaSon/Denapax/issues/4) | Webhook signature and replay tests |
| Browse, paginate, name/description search, product details and variant prices displayed in toman | [#14](https://github.com/DenaSon/Denapax/issues/14) | Bot interaction tests, visibility filters |
| Telegram customer record + multiple addresses with name, phone, province, city, full address, postal code; owner isolation | [#13](https://github.com/DenaSon/Denapax/issues/13) | Own vs other-customer address access |
| Support action and admin-configurable contact information | [#26](https://github.com/DenaSon/Denapax/issues/26), [#27](https://github.com/DenaSon/Denapax/issues/27) | Bot support uses configured contact |
| Multi-item cart with integer quantities, editing/removing items, current price/availability validation | [#15](https://github.com/DenaSon/Denapax/issues/15), [#17](https://github.com/DenaSon/Denapax/issues/17) | Cart updates, race/duplicate tests |
| Nationwide one-parcel shipping with packaging weight and ceil-based gram tiers | [#16](https://github.com/DenaSon/Denapax/issues/16) | 250, 1000, 1001, 2000, 2001 grams + packaging boundary tests |
| Checkout review and immutable order, line, address, card instructions and shipping snapshots; unique order number; IRR amounts | [#17](https://github.com/DenaSon/Denapax/issues/17), [#27](https://github.com/DenaSon/Denapax/issues/27) | Snapshot stability and idempotent checkout tests |
| Card-to-card only; admin-edited bank instructions and support settings | [#18](https://github.com/DenaSon/Denapax/issues/18), #27 | Display amount/card holder details from order snapshot; no gateway |
| Telegram photo or supported document receipt; private storage, attempt history, manual human verification | #18, [#19](https://github.com/DenaSon/Denapax/issues/19), [#20](https://github.com/DenaSon/Denapax/issues/20) | Private file access, approval flow, history tests |
| Payment FSM: awaiting_receipt / under_review / rejected / approved; resubmit after rejection, reviewer reason | #18, #19, #20 | All allowed/forbidden transitions tested |
| Order FSM: awaiting_payment / confirmed / preparing / shipped / delivered / canceled; only unpaid may cancel | [#21](https://github.com/DenaSon/Denapax/issues/21), #19 | Role checks and state transition matrix |
| Admin order list/search/filters, snapshot detail, permission-separated receipt viewing/review | [#28](https://github.com/DenaSon/Denapax/issues/28), #19 | Order admin screen and access-control tests |
| Manual tracking code, one parcel/order, customer own-orders and status lookup | #21, [#23](https://github.com/DenaSon/Denapax/issues/23) | Dispatch/tracking and customer-owner tests |
| Transactional Telegram notifications with outbox, retry, dedupe, send logs | [#22](https://github.com/DenaSon/Denapax/issues/22) | All 8 named events + failure/retry cases |
| Staff activity logs, domain order history, timestamp integrity and security boundaries | #2, #19, #21, #28, [#24](https://github.com/DenaSon/Denapax/issues/24) | Audit verification, authorization/PII reviews |
| Test coverage, queues, private receipts, TLS, backup/restore, safe production launch | #24, [#25](https://github.com/DenaSon/Denapax/issues/25) | E2E happy/negative paths, recovery rehearsal |

## Six implementation phases from the current foundation

| Phase | Main stories | Gate for proceeding |
| --- | --- | --- |
| 1 · Admin/Auth, Settings & Catalog | #2, #3, #12, #27 | Secure admin login; store/card/support settings; catalog/variant/media CRUD; CI green |
| 2 · Telegram Storefront & Customers | #4, #13, #14, #26 | Authenticated Telegram identity, browsing/search, saved addresses and support work |
| 3 · Cart, Shipping & Checkout | #15, #16, #17 | Idempotent order created with shipping/card/recipient/item snapshots |
| 4 · Manual Payments | #18, #19, #20 | Protected receipt workflow with already configured card details, manual approval/rejection/resubmission |
| 5 · Fulfillment & Notifications | #28, #21, #22, #23 | Admin order queue, status transitions, tracking, all transactional notices |
| 6 · Quality & Launch | #24, #25 | CI/E2E/security checks, production-like order, backups/restore and operations pass |

These are **delivery phases**, not calendar commitments. Each can be split into small, reviewable PRs. Cross-cutting security, migrations and tests are applied during every phase; phase 6 verifies release readiness rather than postponing quality.

## Mandatory cross-cutting checks
- Exact **webhook secret** validation; Telegram user ID is distinct from staff `users`.
- No unauthorized receipt download, order/address lookup or Livewire mutation.
- Transactions and idempotency keys for checkout, receipt review and outgoing events.
- Telegram **network retries cannot guarantee exactly-once delivery**; deduplicate *business* events and record send attempts.
- All money persisted as **integer IRR** and displayed in toman; weights persisted as **integer grams**.
- Orders snapshot products, variants, recipient address, shipping config/fee and card instruction details.
- No floating-point money, arbitrary variant weight pricing, inventory counts, dynamic carrier fees, online gateway, Mini App or customer website.
- UI/customer messages should be Persian, mobile usable and right-to-left where applicable.
- Automated tests on PHP 8.4 + Laravel 13. Production DB = MySQL; Redis-backed jobs/worker validated before launch.
- Package migrations (Spatie Media Library / Permission / Activitylog) are authoritative: no duplicate bespoke media/audit/RBAC tables.
- `composer.lock` must use sources reachable by CI; dependency audit should use a trusted working endpoint.
- No scope change without a documented MVP decision.

## Current implementation versus planning
- **Implemented and verified by CI:** base Laravel scaffold, key framework packages, base RBAC role seeder/admin provision command, pure shipping calculator and boundary unit tests, PHPUnit/Pint GitHub Actions.
- **Partially prepared:** package migrations/config for Spatie RBAC/media/activity; MySQL schema/config, Fortify login UI, Nutgram routing and remaining domain implementations are not completed.
- **Not claimed:** production deployment, working external Telegram webhook or database migration on the deployment MySQL, receipt review UI, checkout/order features or end-to-end ordering.

## Acceptance policy
A Story is not Done when code merely compiles. It is Done only after its UI/API flow, server validation/permissions, migrations, tests and CI are verified; release phases also require their explicit phase acceptance gate.
