# Denapax — Project Execution Agreement v1.0

**Date:** 2026-10-18  
**Status:** Owner-requested implementation protocol; applies to future work on MVP v1.1.  
**Primary sources:** [Approved MVP Scope v1.1](01-mvp-scope.md), [Architecture](02-architecture.md), [Database Design](03-database-design.md), [Workflows](04-workflows.md), [Product Backlog](07-product-backlog.md), [Traceability](08-mvp-traceability.md).

## Mission and non-negotiable MVP decisions
Build and release the **Dena/Denapax Telegram-first souvenirs and dried-fruit store** as a **Laravel 13 / PHP 8.4 modular monolith**, with an ordinary Telegram Bot for customers (**Nutgram 4**) and a Livewire 4 + Mary UI 2 admin web panel. Do not introduce a Telegram Mini App or customer website.

- Product belongs to exactly one category; product-level key/value features and ordered gallery; optional image dedicated to one variant.
- Predefined variants with sale quantity and unit (`g`, `kg`, `l`, `piece`), their own **integer IRR** prices, mandatory positive shipping weight in **integer grams**, and availability flag. **No numeric stock**.
- Telegram customer IDs are not staff `users`. A customer may save multiple delivery addresses. Authorization checks guard other customers' orders, addresses, receipts and interactions.
- Multi-item cart. Checkout revalidates authoritative prices/availability, computes one nationwide parcel shipping fee using product grams plus shared packaging and base/additional weight tiers. Capture immutable order/item/address/shipping/card-instruction snapshots. Display money to customers in toman.
- **Card-to-card payment only.** Customer sends private photo/document receipt; authorized staff independently verify transfer, approve/reject with reason, and rejected receipts can be resubmitted with complete attempt history. Upload alone **never** means paid.
- Staff update permitted order states and manually enter parcel tracking. Bot provides own-order status and transactional notifications via durable outbox/queue/retry/recording.
- Fortify staff authentication with public registration disabled; Spatie Permission for simple RBAC; Spatie Media Library for public catalog media; Spatie Activitylog for audit; **payment receipts never public**.
- Product/customer/admin workflows, DB schema and exclusions are defined by MVP v1.1. **No unapproved expansion**: no online gateway, numeric inventory, postal API/regional rates, coupons, loyalty, paid refund automation or elaborate RBAC UI.

## Six delivery phases
1. **Admin/Auth, Store Settings & Catalog** — Fortify/RBAC, admin, configurable support/card details, products/variants/features/gallery.
2. **Telegram Storefront & Customers** — verified webhook, safe update handling, customer/address persistence, browse/search and support.
3. **Cart, Shipping & Checkout** — gram-based pricing, multi-item cart, transactional idempotent checkout and immutable snapshots.
4. **Manual Payments** — private receipts, staff verification, approve/reject/resubmit and audit/history.
5. **Fulfillment & Notifications** — admin order queue, states, parcel tracking, my orders, queued transactional notifications.
6. **Real End-to-End Validation & Launch** — real integrated flows, production-like deployment, backups and security/operational acceptance.

Detailed stories and phase acceptance gates live in [Product Backlog](07-product-backlog.md) and [Traceability](08-mvp-traceability.md).

## Branch / PR / master policy (explicit owner request)
- Keep **`master` stable**. Work on a dedicated `phase/N-...` branch or scoped feature branches feeding the phase branch. Track the corresponding GitHub issues.
- Implement in small, maintainable slices with an appropriate, easily reviewable PR. Do **not** merge incomplete phase functionality to `master` as if done.
- **At the end of every phase**, verify the phase acceptance gate and essential CI checks, then merge the phase PR into `master`; confirm the merged commit and status. No force pushes to `master`.
- Prefer clear Laravel domain/application actions and thin Nutgram/Livewire adapters; schema design must match existing docs/package migrations.
- Never commit `.env`, Telegram bot tokens, private receipts, real bank credentials beyond approved display configuration, customer PII or production secrets.
- If a phase is blocked by missing access, real services, unresolved high-impact bugs or failing CI, **do not describe it as complete or merge it as complete**; explain the blocker and the exact required next step.
- No requirement for the owner to sync local sources for document/issue/repository-only operations. Local sync may be needed only for their own execution or environment tests.

## Lean test strategy (explicit owner request)
**Do not write excessive, repetitive or low-value tests.** During phases 1–5, add only meaningful checks for the phase's **core business behavior and severe failure modes**. Suggested minimum targets when relevant:

- Authentication and staff permissions; prevent admin public registration and unauthorized payment review.
- Catalog validation (price, grams, availability), key persistence/model relationships.
- Telegram webhook authenticity, repeated update/checkout handling and customer ownership boundaries.
- Shipping tier boundaries and checkout totals/snapshots; avoid duplicate order creation.
- Manual receipt approve/reject/resubmit and forbidden payment/order state transitions; private receipt access.
- Fulfillment constraints and transactional notification outbox/deduplication.
- Keep composer validation, Pint and essential PHPUnit tests in GitHub Actions; choose Pest only if/when intentionally installed.

**Real integrated tests begin after the main implementation phases (1–5), in phase 6.** Run a realistic Telegram-to-order-to-receipt-to-shipment test with configured test infrastructure and verified access. Include a rejection/resubmission scenario and basic privacy/access checks. Do not claim these tests have run without actual execution/results.

A phase is complete with its **essential automated checks + phase acceptance** even if the full real end-to-end exercise is deferred to phase 6. However, functional smoke testing required to prove that phase's own deliverable is not deferred.

## End-of-phase short report (explicit owner request)
At each phase close, send a compact Persian status report:

**Phase N — [name]**
1. **Delivered:** main implemented behaviors and relevant PR/commit links.
2. **Validation:** essential tests/CI and pass/fail status; explicitly state what was **not** tested.
3. **Merge:** `master` merge result and commit SHA/link; if not merged, reason/blocker.
4. **Risks / remaining work:** only the most material known issues.
5. **Next phase:** what starts next and required outside access, if any.

Keep the report concise. Never claim a merge, test result or production validation that has not been verified.

## Working source of truth
- The **GitHub repository documents, issues, PRs and CI results** are the persistent, reviewable record for decisions and delivery.
- Preserve this agreement alongside the approved MVP documents. If a future delivery decision conflicts with this file, record the change explicitly before implementation.
