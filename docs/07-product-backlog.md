# Denapax — MVP Product Backlog v1.1

**Status:** Proposed execution backlog, ready for owner review · **Scope:** [MVP Scope v1.1](01-mvp-scope.md) · **Last reviewed:** 2026-10-18

## Operating principles
- This is an **MVP execution backlog**, not an expansion of MVP Scope v1.1.
- 7 epics and 20 user stories are tracked as GitHub Issues with explicit acceptance criteria.
- Priority `P0` = required for MVP launch; story estimates are intentionally **not assigned** before implementation refinement.
- One Laravel Modular Monolith with a Telegram Bot customer interface and Livewire/Mary admin. No Mini App / web customer storefront.
- Work in short **vertical slices** with test coverage; use GitHub branches/PRs per story or closely coupled technical slice.
- Existing foundation [PR #1](https://github.com/DenaSon/Denapax/pull/1) is **merged**. It is enabling engineering work, not a complete authentication or shipping feature.

## Epics and stories

| Epic | Focus | Tracked stories | Dependency |
| --- | --- | --- | --- |
| [E01 · Foundation](https://github.com/DenaSon/Denapax/issues/5) | Admin login, Fortify, RBAC | [#2](https://github.com/DenaSon/Denapax/issues/2) | PR #1 |
| [E02 · Catalog](https://github.com/DenaSon/Denapax/issues/6) | Product/variant/media schema and admin CRUD | [#3](https://github.com/DenaSon/Denapax/issues/3), [#12](https://github.com/DenaSon/Denapax/issues/12) | E01 |
| [E03 · Telegram + Customers](https://github.com/DenaSon/Denapax/issues/7) | Nutgram, saved addresses, browse/search | [#4](https://github.com/DenaSon/Denapax/issues/4), [#13](https://github.com/DenaSon/Denapax/issues/13), [#14](https://github.com/DenaSon/Denapax/issues/14), [#26](https://github.com/DenaSon/Denapax/issues/26) | E02 catalog schema |
| [E04 · Cart + Shipping + Checkout](https://github.com/DenaSon/Denapax/issues/8) | Multi-item cart, grams/tier formula, snapshots | [#15](https://github.com/DenaSon/Denapax/issues/15), [#16](https://github.com/DenaSon/Denapax/issues/16), [#17](https://github.com/DenaSon/Denapax/issues/17) | E02 + E03 identity |
| [E05 · Manual Payments](https://github.com/DenaSon/Denapax/issues/9) | Card details, private receipt, review/resubmit | [#18](https://github.com/DenaSon/Denapax/issues/18), [#19](https://github.com/DenaSon/Denapax/issues/19), [#20](https://github.com/DenaSon/Denapax/issues/20), [#27](https://github.com/DenaSon/Denapax/issues/27) | E01 + E04 |
| [E06 · Fulfillment + Notifications](https://github.com/DenaSon/Denapax/issues/10) | Shipping states, tracking, transactional bot alerts | [#21](https://github.com/DenaSon/Denapax/issues/21), [#22](https://github.com/DenaSon/Denapax/issues/22), [#23](https://github.com/DenaSon/Denapax/issues/23), [#28](https://github.com/DenaSon/Denapax/issues/28) | E05 + E03 |
| [E07 · Quality + Launch](https://github.com/DenaSon/Denapax/issues/11) | E2E regression, secure ops, backups and release | [#24](https://github.com/DenaSon/Denapax/issues/24), [#25](https://github.com/DenaSon/Denapax/issues/25) | E01–E06 |

## Delivery plan — six phases (not fixed-duration sprints)

| Phase | Primary focus and stories | Exit gate |
| --- | --- | --- |
| 1 · Admin & Catalog | #2, #3, #12, #27 | Staff sign-in, RBAC, store/card/support settings and product/variant/media CRUD validated |
| 2 · Telegram & Customers | #4, #13, #14, #26 | Bot browsing/search, saved addresses and support work securely |
| 3 · Cart & Checkout | #15, #16, #17 | Multi-item checkout, gram-based postage and immutable snapshot |
| 4 · Card-to-Card Payment | #18, #19, #20 | Card instructions from preconfigured settings plus private receipt upload/review/resubmission verified |
| 5 · Fulfillment & Notifications | #28, #21, #22, #23 | Admin queue, states, tracking and outbox notifications work |
| 6 · Production Readiness | #24, #25 | E2E/security/operations gates met and release test passed |

Existing foundation PR #1 is already merged; it supplies reusable shipping calculation, base RBAC and CI, but it **does not** satisfy all of phases 1 or 3. Engineering quality, security, testing and data integrity apply to every phase.

## Requirements traceability and execution phases

The scope-to-story checklist and **six delivery phases with explicit acceptance gates** are maintained in [MVP Requirements Traceability](08-mvp-traceability.md). This closes the three missing-story gaps discovered during the scope audit: bot Support (#26), configurable store/card/support settings (#27), and admin order queue/search/detail (#28). These are already **in** approved MVP scope, not scope expansion.

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
- [Requirements traceability and 6-phase delivery gates](08-mvp-traceability.md)
