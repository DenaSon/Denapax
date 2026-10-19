# Denapax 🌰

**Telegram-first store for Dena souvenirs, dried fruits and local products.**

## Current milestone — 2026-10-19

**MVP functionally accepted, scope frozen and launched (owner-reported).** Several real products were entered in the staff admin and each was tried once in the Telegram bot. Earlier owner-run functional E2E shopping tests were reported successful. This is a **product milestone**, not an independently verified security/backup/recovery sign-off.

- **Stable MVP:** catalog, product variants and images; Telegram browsing/search/cart; nationwide weight-based shipping; checkout; card-to-card manual payment/receipts; staff review; fulfillment, tracking and customer notifications.
- **Production-price UX:** variant prices and shipping fees are **entered and displayed in toman** in the admin; Telegram amounts are shown in toman. Stored DB values and immutable order snapshots **remain integer IRR**. No blanket price migration. See [toman rules](docs/28-admin-toman-pricing.md), merged [PR #69](https://github.com/DenaSon/Denapax/pull/69).
- **Catalog admin polish:** generated SKU suggestions and live slug suggestion ([PR #68](https://github.com/DenaSon/Denapax/pull/68)); product image fallback to public HTTPS for Telegram after failed local upload ([PR #67](https://github.com/DenaSon/Denapax/pull/67)).
- **Recent catalog examples (not a complete inventory):** گردو؛ شیره انگور ارگانیک سی‌سخت؛ برگ آلو بخارا؛ لواشک آلو شابلون.
- **Latest code baseline at milestone:** `master` [`edf3805`](https://github.com/DenaSon/Denapax/commit/edf3805811478683d1a23d79f1c7a43dfa72ee5d) with [green master CI run 39287004609](https://github.com/DenaSon/Denapax/actions/runs/39287004609).

**Important residual release evidence:** [E07 Epic #11](https://github.com/DenaSon/Denapax/issues/11), [negative/security checks #24](https://github.com/DenaSon/Denapax/issues/24), [backup/restore/monitoring #25](https://github.com/DenaSon/Denapax/issues/25) remain **open**. Owner-reported live functional acceptance **must not** be presented as proof these controls passed. Prioritize these operational checks alongside stabilization.

**Source of truth:** [MVP launch/freeze decision & handoff](docs/29-mvp-launch-freeze.md) · [Agent development handoff](AGENTS.md).

## Public editorial landing — approved Post-MVP Phase L1

The homepage `/` serves a public RTL editorial brand/catalog experience for denapax.ir. **Only the product listing is dynamic** (published items, active categories, orderable variants, Media Library images and exact toman minimum price). The static sections include a bespoke Dena mountain SVG hero, topographic visual identity and Telegram CTA. Alpine.js enhances mobile navigation, progressive reveal and subtle motion; Tailwind 4 uses a standalone landing bundle. No web checkout or payment changes. The admin stays under `/admin`.

To activate the outbound links, configure `TELEGRAM_BOT_USERNAME` in Runflare (public handle *without* @), refresh config and confirm `APP_URL=https://denapax.ir` and public product media URLs. If unset, no fake Telegram link is generated. Run CI, deploy, then check real mobile/desktop and bot navigation. [Phase L1 runbook](docs/30-public-landing-phase-1.md).

### Landing Phase L2 — visual and interaction refinement

Second-phase work adds topographic path drawing, chapter-aware scroll progress, refined product reveals, accessible mobile keyboard focus behavior, no-JavaScript navigation fallback and reduced-motion/high-contrast safeguards. The dynamic scope remains **catalog only**. No web checkout or payment changes. [Phase L2 handoff](docs/31-public-landing-phase-2.md). Device/production smoke must be verified separately.

### Landing Phase L3 — Product Experience

Product cards now open one responsive native-dialog preview with gallery thumbnails, description, features and **only available variants with exact toman prices**. Alpine changes the visible variant/image, while the actual checkout remains in the Telegram bot (no product deep-link promise). The payload contains only public catalog fields for the current page and uses safe JSON serialization. The page remains usable without JavaScript through existing links. See [L3 handoff](docs/32-public-landing-phase-3.md). Real phone/desktop smoke is distinct from CI.

## Architecture

| Layer | Stack |
| --- | --- |
| Customer storefront | Regular Telegram Bot (Nutgram; Reply Keyboard + Inline Keyboards), **not** Mini App/web storefront |
| Staff admin | Laravel 13 / PHP 8.4, Livewire 4, Mary UI 2, Tailwind CSS 4, Fortify and Spatie Permission |
| Persistence | MySQL; product media via Spatie Media Library; private receipts stored separately |
| Notifications | DB queue (`notifications` / `default`), Laravel scheduler and transactional outbox; **no Redis/Horizon** |
| Payments | Card-to-card instructions and receipt upload; independent staff verification mandatory |
| Hosting | Runflare (owner-tested), HTTPS webhook, protected server-side environment secrets |

## Delivery history

- **Core MVP Phases 1–5:** [#30–#34](https://github.com/DenaSon/Denapax/pulls?q=is%3Apr+is%3Aclosed) merged; formal Phase 6 **E07 evidence remains open** (#11/#24/#25).
- **Telegram UX-1–4:** PRs #43–#46 completed.
- **Earlier Post-MVP Sprint 1:** Phase 7 [#61](https://github.com/DenaSon/Denapax/pull/61) and Phase 8 [#62](https://github.com/DenaSon/Denapax/pull/62) merged; feedback Issues #56–#58 closed as *code-delivered*. This sprint is historical/completed; do not start new work without new approved feedback.
- **Phase 9 private admin-group alerts:** [#65](https://github.com/DenaSon/Denapax/pull/65) merged, [Issue #64](https://github.com/DenaSon/Denapax/issues/64) code-closed; live Telegram group activation and delivery still require explicit configuration/verification. [Runbook](docs/27-phase-9-admin-group-alerts.md).

## Post-MVP change policy

**Feature scope is frozen until actionable owner/customer feedback exists.** Capture issue + reproduction + impact, prioritize safety and operational reliability first, secure owner approval, implement only the approved change in a scoped PR, run essential checks, merge to `master` and smoke-test affected live bot/admin paths. No speculative features or broad refactors.

Open candidate: [#70 — variant price-change audit trail](https://github.com/DenaSon/Denapax/issues/70), **backlog only**, not implemented or committed to a sprint.

## Operator notes

On an authorized, backed-up deployment, use safe migrations and baseline settings seeders. Real payment instructions and tariffs are configured in admin; demo products are **not** seeded to production. Run a supervised database queue worker plus scheduler; protect HTTPS Telegram webhook secrets and private receipt images.

```bash
php artisan migrate --force
php artisan db:seed
php artisan queue:work database --queue=notifications,default --tries=1 --timeout=60
```

Do not run `migrate:fresh` on production. Do not commit `.env`, tokens, bank/card data, customer PII, receipt files or production identifiers.

## Documentation index

- [29 — MVP launch scope freeze and Post-MVP handoff](docs/29-mvp-launch-freeze.md)
- [28 — Exact toman/IRR conversion and verification](docs/28-admin-toman-pricing.md)
- [27 — Phase 9 private admin group alert activation](docs/27-phase-9-admin-group-alerts.md)
- [24 — Historical completed Post-MVP Sprint 1](docs/24-post-mvp-sprint-1.md)
- [15 — Phase 6 evidence status](docs/15-phase-6-readiness-plan.md) · [16 — Operator runbook](docs/16-phase-6-operator-runbook.md)
- [01 — Original MVP scope](docs/01-mvp-scope.md) · [02 — Architecture](docs/02-architecture.md) · [03 — Database](docs/03-database-design.md) · [04 — Workflows](docs/04-workflows.md)
- [07 — Backlog](docs/07-product-backlog.md) · [08 — Traceability](docs/08-mvp-traceability.md) · [09 — Execution agreement](docs/09-execution-agreement.md)
- [Telegram UI/UX guidelines](docs/telegram-ui-ux-guidelines.md) · [21 — Seeders and database queue](docs/21-initial-seeders-handoff.md)
- [23 — Runflare HTTPS/Livewire troubleshooting](docs/23-runflare-https-livewire-mixed-content.md)

**Handoff for a new chat:** read [AGENTS.md](AGENTS.md), [MVP freeze status](docs/29-mvp-launch-freeze.md) and current GitHub issues/CI. Treat the owner's functional launch decision as recorded, but verify unresolved operations/security separately.
