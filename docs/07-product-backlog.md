# Denapax — MVP Product Backlog v1.0

**Status:** Baseline backlog approved for implementation planning · **Scope:** [MVP Scope v1.1](01-mvp-scope.md) · **Last reviewed:** 2026-10-18

## Operating principles
- This is an **MVP execution backlog**, not an expansion of MVP Scope v1.1.
- 7 epics and 17 user stories are tracked as GitHub Issues with explicit acceptance criteria.
- Priority `P0` = required for MVP launch; story estimates are intentionally **not assigned** before implementation refinement.
- One Laravel Modular Monolith with a Telegram Bot customer interface and Livewire/Mary admin. No Mini App / web customer storefront.
- Work in short **vertical slices** with test coverage; use GitHub branches/PRs per story or closely coupled technical slice.
- Existing foundation [PR #1](https://github.com/DenaSon/Denapax/pull/1) is **merged**. It is enabling engineering work, not a complete authentication or shipping feature.

## Epics and stories

| Epic | Focus | Tracked stories | Dependency |
| --- | --- | --- | --- |
| [E01 · Foundation](https://github.com/DenaSon/Denapax/issues/5) | Admin login, Fortify, RBAC | [#2](https://github.com/DenaSon/Denapax/issues/2) | PR #1 |
| [E02 · Catalog](https://github.com/DenaSon/Denapax/issues/6) | Product/variant/media schema and admin CRUD | [#3](https://github.com/DenaSon/Denapax/issues/3), [#12](https://github.com/DenaSon/Denapax/issues/12) | E01 |
| [E03 · Telegram + Customers](https://github.com/DenaSon/Denapax/issues/7) | Nutgram, saved addresses, browse/search | [#4](https://github.com/DenaSon/Denapax/issues/4), [#13](https://github.com/DenaSon/Denapax/issues/13), [#14](https://github.com/DenaSon/Denapax/issues/14) | E02 catalog schema |
| [E04 · Cart + Shipping + Checkout](https://github.com/DenaSon/Denapax/issues/8) | Multi-item cart, grams/tier formula, snapshots | [#15](https://github.com/DenaSon/Denapax/issues/15), [#16](https://github.com/DenaSon/Denapax/issues/16), [#17](https://github.com/DenaSon/Denapax/issues/17) | E02 + E03 identity |
| [E05 · Manual Payments](https://github.com/DenaSon/Denapax/issues/9) | Card details, private receipt, review/resubmit | [#18](https://github.com/DenaSon/Denapax/issues/18), [#19](https://github.com/DenaSon/Denapax/issues/19), [#20](https://github.com/DenaSon/Denapax/issues/20) | E01 + E04 |
| [E06 · Fulfillment + Notifications](https://github.com/DenaSon/Denapax/issues/10) | Shipping states, tracking, transactional bot alerts | [#21](https://github.com/DenaSon/Denapax/issues/21), [#22](https://github.com/DenaSon/Denapax/issues/22), [#23](https://github.com/DenaSon/Denapax/issues/23) | E05 + E03 |
| [E07 · Quality + Launch](https://github.com/DenaSon/Denapax/issues/11) | E2E regression, secure ops, backups and release | [#24](https://github.com/DenaSon/Denapax/issues/24), [#25](https://github.com/DenaSon/Denapax/issues/25) | E01–E06 |

## Suggested implementation order (not fixed-duration sprints)

### Milestone A — Admin and Catalog Foundation
- [#2](https://github.com/DenaSon/Denapax/issues/2): Fortify staff login and restricted web admin.
- [#3](https://github.com/DenaSon/Denapax/issues/3): catalog database and Eloquent relationships.
- [#12](https://github.com/DenaSon/Denapax/issues/12): product/category/variant/feature/media admin CRUD.

**Acceptance gate:** a permitted admin can create/publish products with correct variants and media.

### Milestone B — Telegram-to-Order
- [#4](https://github.com/DenaSon/Denapax/issues/4), [#13](https://github.com/DenaSon/Denapax/issues/13), [#14](https://github.com/DenaSon/Denapax/issues/14): bot plumbing, identity, address book, catalog search.
- [#15](https://github.com/DenaSon/Denapax/issues/15), [#16](https://github.com/DenaSon/Denapax/issues/16), [#17](https://github.com/DenaSon/Denapax/issues/17): cart, persisted nationwide shipping settings, checkout.

**Acceptance gate:** a customer can search/browse, add multiple variants, select an address and register an order once. Shipping uses grams, including shared packaging, and IRR totals are snapshotted.

### Milestone C — Payment-to-Delivery
- [#18](https://github.com/DenaSon/Denapax/issues/18), [#19](https://github.com/DenaSon/Denapax/issues/19), [#20](https://github.com/DenaSon/Denapax/issues/20): card-to-card receipt, private staff review, reject/resubmit.
- [#21](https://github.com/DenaSon/Denapax/issues/21), [#22](https://github.com/DenaSon/Denapax/issues/22), [#23](https://github.com/DenaSon/Denapax/issues/23): fulfillment, manual tracking, outbox notifications, my orders.

**Acceptance gate:** no receipt upload can auto-approve payment; staff approve after bank check, and customer sees status/tracking through bot.

### Milestone D — Public MVP Launch
- [#24](https://github.com/DenaSon/Denapax/issues/24), [#25](https://github.com/DenaSon/Denapax/issues/25): E2E + security and operational runbook.

**Acceptance gate:** representative end-to-end orders and negative cases pass automated tests; encrypted/private receipts, HTTPS, backup-and-restore, queues and webhook verification are operational.

## Definition of Ready (before coding a story)
- User/actor, expected behavior, boundary conditions and observable acceptance criteria are written on the issue.
- Business entity and relevant source of truth are identified (e.g., `orders` for checkout snapshot; `payment_receipts` for manual review).
- Technical dependencies and security obligations are understood. No unresolved scope change is silently included.

## Definition of Done (for each story)
- Acceptance criteria are tested at the appropriate unit/feature/integration level.
- Changes use server-side authorization and validation; sensitive data remains private.
- No duplicate business operations under repeated callbacks/requests; state changes are transactional when necessary.
- Migrations are reviewed, necessary docs updated, Pint + tests pass in CI, and PR is reviewed/merged.
- UI/chat content has understandable Persian labels and error states where relevant.
- No production secrets or customer media are committed.

## Release boundaries
- **Included:** standard Telegram bot, products/variants/search/images, cart, saved addresses, nationwide weight-based postage, manual card receipt, approval/rejection/resubmission, order tracking and notifications.
- **Excluded:** Mini App, customer website, payment gateway, numeric inventory, regional postal rates, carrier API, marketing campaigns, complex RBAC UI, returns/refunds automation.

## Story naming
- Epics: `[EPIC E01]` ... `[EPIC E07]`.
- Stories: `[US E01-S01]` ... with parent epic link and testable acceptance criteria.
- Implementation PRs should reference a story number and use `Closes #N` only when all acceptance criteria are satisfied.

## Architectural references
- [Architecture](02-architecture.md)
- [ERD/Data Dictionary](03-database-design.md)
- [Payment/order workflows](04-workflows.md)
- [Packages & Windows bootstrap](05-stack-and-windows-setup.md)
- [Foundation handoff](06-foundation-handoff.md)
