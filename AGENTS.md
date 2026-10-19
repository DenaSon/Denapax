# Denapax — Agent Handoff & Engineering Agreement

**Updated 2026-10-19. Read this file first in any new Denapax ChatGPT/Codex development session.** The authoritative business milestone is [docs/29-mvp-launch-freeze.md](docs/29-mvp-launch-freeze.md); inspect current GitHub `master` and Issues before new code.

**Approved Post-MVP exception (2026-10-19):** The owner explicitly approved the public Denapax landing/catalog at `/`. Phase L1 is implemented in [PR #72](https://github.com/DenaSon/Denapax/pull/72); confirm actual CI/merge/deployment status. The editorial sections are STATIC; only published/available catalog products, product photos and category filters are dynamic. No web checkout. Tailwind CSS 4 + standalone Alpine, original Dena SVG artwork. `TELEGRAM_BOT_USERNAME` must be set privately for working public bot links; no hardcoded guess. See [landing handoff](docs/30-public-landing-phase-1.md). This is an approved single-scope exception to the default feedback-only product freeze; unrelated features remain frozen.

## 1. Current owner decision

**MVP is functionally accepted, owner-reported live, and feature scope is frozen as of 2026-10-19.** The owner created multiple real products, tested each at least once in the bot and previously confirmed the functional E2E purchase experience. Launch/freeze is a **product decision**, **not** documentary clearance for the remaining E07 security and disaster-recovery tests.

- **No additional feature phases are scheduled**. Future work is Post-MVP **only when supported by feedback**, owner-approved scope and prioritized Issues.
- **Do not close** original E07 [#11](https://github.com/DenaSon/Denapax/issues/11) / [#24](https://github.com/DenaSon/Denapax/issues/24) / [#25](https://github.com/DenaSon/Denapax/issues/25) without objective proof. Continue those as **urgent operational/security stabilization**, separate from feature expansion.
- **Existing candidate Post-MVP:** [#70 variant price changes audit trail](https://github.com/DenaSon/Denapax/issues/70), backlog only. Reuse existing Spatie Activitylog if appropriate; not yet approved for implementation.
- Historical Post-MVP Sprint 1 (Phases **7 and 8**) is **already code-delivered/closed** through PRs [#61](https://github.com/DenaSon/Denapax/pull/61) and [#62](https://github.com/DenaSon/Denapax/pull/62). Do not revive its old “intake pending” instructions in [docs/24](docs/24-post-mvp-sprint-1.md).
- Phase **9** private admin Telegram group alerts were **code-delivered** via [PR #65](https://github.com/DenaSon/Denapax/pull/65), Issue #64 closed. Actual Telegram-group configuration/smoke **not independently verified**; see [docs/27](docs/27-phase-9-admin-group-alerts.md).

**Last code snapshot checked during this handoff:** `master` [`edf3805811478683d1a23d79f1c7a43dfa72ee5d`](https://github.com/DenaSon/Denapax/commit/edf3805811478683d1a23d79f1c7a43dfa72ee5d) with green [CI 39287004609](https://github.com/DenaSon/Denapax/actions/runs/39287004609). Verify current head/CI anew after documentation merge.

## 2. Business and architecture

- **Repo:** https://github.com/DenaSon/Denapax — default branch `master`.
- **Business:** Dena souvenirs, nuts, dried fruits and local products. Customer storefront is a **regular Telegram Bot** (Nutgram, message/Reply/Inline keyboards); no Telegram Mini App or public website.
- **Backend:** PHP 8.4, Laravel 13, modular monolith, MySQL.
- **Admin:** Fortify, Livewire 4, Mary UI 2, Tailwind CSS 4/daisyUI 5, Spatie Permission, Media Library and Activitylog.
- **Jobs:** Laravel **database-backed** queue (`notifications` and `default`), supervised worker, scheduled outbox commands. No Redis/Horizon.
- **Hosting:** Runflare used during owner-reported launch; HTTPS ingress, HTTPS webhook, private receipts and secret environment configuration require operator controls.
- **Historical developer path:** `D:\dena\Projects\Denapax` on Windows PowerShell; do not assume agent access to the owner's machine.

## 3. Delivered implementation and owner-checked catalog

| Stage | Code status | What it covers |
| --- | --- | --- |
| Phases 1–5 / PRs #30–#34 | Merged | Admin/catalog, Telegram customers, cart/shipping/checkout, manual payments, fulfillment and notifications |
| UX-1–4 / PRs #43–#46 | Merged | Navigation, Telegram presentation, cart/catalog and checkout polish |
| Post-MVP S1 / PRs #61–#62 | Merged | Phases 7–8; 17 approved UI/UX feedback improvements; Issues #56–#58 closed |
| Phase 9 / PR #65 | Merged code | Private Telegram staff group alerts, runtime activation unverified |
| Photo fix / PR #67 | Merged code | Telegram sendPhoto local-to-public-HTTPS fallback |
| SKU/slug / PR #68 | Merged code | Suggested variant SKU, debounced product slug |
| Money UX / PR #69 | Merged code | Admin input/display in toman, bot/admin amounts in toman, database values in integer IRR |

**Owner-reported catalog smoke (2026-10-19):** multiple real products entered and each checked once in live bot. Examples discussed, **not a complete verified DB inventory**: گردو، شیره انگور ارگانیک سی‌سخت (owner says no added sugar/chemicals)، برگ آلو بخارا، لواشک آلو شابلون. Uploaded product media appeared during owner check. Avoid claiming exhaustive all-variant/all-device testing.

**Product-copy direction:** useful natural Persian facts, origin, qualities and characteristics, not inflated marketing slogans. Do not fabricate health or certification evidence.

## 4. Non-negotiable data and security contracts

- Staff price/shipping input and all visible amounts are **toman**; DB `*_irr` fields, calculations and immutable order snapshot amounts are **integer IRR**. Example `1,389,000 toman = 13,890,000 IRR`; fractional legacy toman must remain exact, never silently rounded. See [docs/28](docs/28-admin-toman-pricing.md).
- Each product has one category; variants have unique SKU, predefined sale unit/quantity and price, shipping weight in grams, availability flag (no numeric inventory). A published product needs at least one valid orderable variant.
- Shipping is the one nationwide gram-based tariff with shared packaging weight; no carrier API or region pricing. Checkout revalidates cart and snapshots variant/amount/address/shipping/card instructions; protect against duplicate checkout.
- Payments are **card-to-card and manually reviewed** after independent bank verification. Receipt upload alone does not confirm payment. Retain reject/retry history.
- Receipt files must remain private; protect role permissions and customer ownership of addresses/orders/payments. No customer PII, receipt media, secrets or real card/bot credentials in GitHub logs/docs.
- Preserve transactional outbox + bounded notification retry; Telegram cannot guarantee exactly-once sends in all failure cases.
- Telegram admin group alerts (if activated) contain **no** buyer PII/card numbers/receipt images and do not interfere with customer notifications.

## 5. What still requires evidence

**#24:** actual negative/security matrix (wrong-user address/order/receipt, unauthorized admin/permissions, forged and replayed webhook/callback, concurrency-sensitive checkout/payment, private media handling).

**#25:** verified supervised queue and scheduler lifecycle, HTTPS and `APP_DEBUG=false`, logs/alerts, consistent backup of **MySQL plus private receipts**, **isolated restore drill**, incident/rollback/operator sign-off.

**#11:** parent E07 Epic closes only after #24/#25 are proven. Owner functional launch and one-time `schedule:list`/`queue:monitor`/`queue:failed` output **do not close** these controls. Prioritize real production defects and high-risk gaps without adding speculative product features.

## 6. Post-MVP feedback workflow

1. Capture observed customer/owner issue with sanitized steps, affected screen, actual/expected, severity and proposed measurable acceptance.
2. Triage: P0 security/payment/broken purchase; P1 real operational/customer friction; P2 enhancement; P3 cosmetic; dedupe and avoid repeating implemented Sprint 1 work.
3. Get owner's explicit priority/scope approval **before feature coding**. Existing price audit Issue #70 remains merely deferred.
4. Deliver one small topic/phase per branch + PR; lean core tests, green CI, merge to `master` after verifying PR status, no forced master pushes. Check actual Telegram/admin flow after deploy.
5. End each completed phase with a **brief Persian summary**: change, tests/CI, PR+merge SHA, risks/unverified live tests, next action. Keep README, AGENTS and milestone documents current when status changes.

## 7. Read order and verification

1. [docs/29 — MVP freeze/launch decision](docs/29-mvp-launch-freeze.md), [README](README.md), then [docs/28 — toman pricing](docs/28-admin-toman-pricing.md).
2. [MVP scope](docs/01-mvp-scope.md), [architecture](docs/02-architecture.md), [database](docs/03-database-design.md), [workflows](docs/04-workflows.md), [Telegram UX guide](docs/telegram-ui-ux-guidelines.md).
3. [E07 readiness/evidence](docs/15-phase-6-readiness-plan.md), [operator runbook](docs/16-phase-6-operator-runbook.md); read [Issue #24](https://github.com/DenaSon/Denapax/issues/24) and [#25](https://github.com/DenaSon/Denapax/issues/25) before claiming security/ops clearance.
4. [History of feedback Sprint 1](docs/24-post-mvp-sprint-1.md), [Phase 9 group runbook](docs/27-phase-9-admin-group-alerts.md), [Seeder/queue runbook](docs/21-initial-seeders-handoff.md).
5. Inspect latest `master` code, open Issues, merged PRs and **latest successful** CI. Docs are not a replacement for runtime verification.

### Baseline commands — only in configured checkout

```bash
composer install
php artisan test
vendor/bin/pint --test
composer validate --no-check-publish
npm install
npm run build
php artisan schedule:list
php artisan queue:failed
```

Actual production queue, MySQL, bot/webhook, private files and bank cannot be assumed present in an agent environment. Never run destructive migrations against live data; protect environment secrets and back up before data operations.

## 8. New chat starting prompt

> Read AGENTS.md and docs/29-mvp-launch-freeze.md, verify GitHub master and CI. The owner accepted and launched the functional MVP on 2026-10-19, entered multiple real catalog items, tested them in Telegram, and approved freeze of new feature scope. The admin and bot use toman with exact integer-IRR persistence (PR #69). Earlier Post-MVP Sprint 1 and Phase 9 code are already delivered. Post-MVP work starts only from new prioritized feedback (candidate #70). Keep E07 #11/#24/#25 open until independent security and backup/restore evidence exists. Only create small reviewed PRs with essential tests and concise Persian summaries.

**Durable memory is in versioned GitHub files/issues; do not rely on unstated conversation memory.**
