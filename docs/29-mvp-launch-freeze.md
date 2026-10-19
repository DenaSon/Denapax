# Denapax — MVP scope freeze, functional launch and feedback-led handoff

**Decision date:** 2026-10-19  
**Decision owner:** Project owner  
**Milestone:** MVP **functionally accepted / scope frozen / launched (owner-reported)**. Further product work is **Post-MVP, based on actual feedback**, not unrequested features.

> This is a **product/business launch decision** and an owner attestation of hands-on smoke checks, **not** an independent certification of the complete E07 security, backup/restore or operations gates. Issues [#11](https://github.com/DenaSon/Denapax/issues/11), [#24](https://github.com/DenaSon/Denapax/issues/24), [#25](https://github.com/DenaSon/Denapax/issues/25) stay open as prioritized operational/release evidence debt. Do not silently mark these checklists complete or block recording the owner's functional acceptance.

## 1. Scope frozen at launch

- **Customer channel:** regular Telegram bot for product browsing/search, variants, cart, nationwide weight-based shipping, checkout, card-to-card payment/receipt upload, order status and support. Not a Mini App or public customer website.
- **Operations:** Laravel 13 / PHP 8.4 staff admin with Livewire 4, Mary UI, Fortify/RBAC; customer and staff identities isolated.
- **Fulfillment:** staff payment verification (receipts are never automatic proof), order states, manual tracking, customer notifications with database queue, scheduler and transactional outbox.
- **Platform:** MySQL, HTTPS webhook, Runflare deployment (as reported during owner tests). Redis/Horizon are **not** needed for this scope. Protect credentials, bank data and receipt media.
- **Price contract:** staff **enters and sees toman** for product variants and shipping rates; Telegram and admin financial readouts use toman. DB fields, shipping arithmetic and **immutable historical order snapshots remain integer IRR**. Exact integer conversion is applied, without a blanket repricing/migration. A displayed price of **1,389,000 تومان** corresponds to **13,890,000 ریال** in storage. Legacy non-multiple-of-10 IRR is preserved/represented precisely, never silently rounded.
- **Catalog edit UX:** automatically suggested DN-prefixed variant SKU and live product slug, with manual overrides preserved. Published products require a valid, purchasable variant.

## 2. Functional acceptance evidence vs. remaining proof

**Owner report (2026-10-19):** Multiple **real catalog products** have been created in the admin; each was exercised at least once in the live Telegram bot. The owner previously reported the MVP live and key functional end-to-end flows working. Product image upload/display was retested during catalog setup; this is owner-reported functional confirmation and is **not** a comprehensive media/Telegram matrix.

**Examples of products discussed during catalog entry (not a claimed full production inventory):**
- گردو
- شیره انگور ارگانیک سی‌سخت — per owner, without added sugar or chemical additives
- برگ آلو بخارا
- لواشک آلو شابلون

**Catalog content style:** concise natural Persian descriptions, factual origin/characteristics; no exaggerated slogans. Preserve truthful product-specific claims and validate organic/health-related marketing claims before publishing when necessary.

**Repository verification as of milestone creation:**
- `master` head: [`edf3805`](https://github.com/DenaSon/Denapax/commit/edf3805811478683d1a23d79f1c7a43dfa72ee5d); [CI run 39287004609](https://github.com/DenaSon/Denapax/actions/runs/39287004609) **successful**.
- Currency overhaul: [PR #69](https://github.com/DenaSon/Denapax/pull/69) merged. SKU/slug: [PR #68](https://github.com/DenaSon/Denapax/pull/68) merged. Telegram photo fallback: [PR #67](https://github.com/DenaSon/Denapax/pull/67) merged (code, not proof of every live media path).
- Initial core MVP Phases 1–5: [PRs #30–#34](https://github.com/DenaSon/Denapax/pulls?q=is%3Apr+is%3Aclosed), merged.
- Previously executed **Post-MVP Sprint 1:** Phase 7 [PR #61](https://github.com/DenaSon/Denapax/pull/61) and Phase 8 [PR #62](https://github.com/DenaSon/Denapax/pull/62), both merged and their issues #56–#58 closed as code-complete. This is **historical delivered work**, not a new authorization to start another sprint.
- **Phase 9** private admin Telegram group alert code [PR #65](https://github.com/DenaSon/Denapax/pull/65) is merged / [#64](https://github.com/DenaSon/Denapax/issues/64) code-closed. **Actual private-group activation/delivery is not asserted** until configured and manually checked.

### E07 residual controls (keep open, pursue separately)

| Issue | Missing or not independently evidenced | Operating posture |
| --- | --- | --- |
| [#24](https://github.com/DenaSon/Denapax/issues/24) | Complete negative/security matrix: cross-customer isolation, forbidden staff access, receipt privacy, forged/replayed updates, repeat/concurrency paths and detailed evidence | Do not claim passed based only on one functional trial |
| [#25](https://github.com/DenaSon/Denapax/issues/25) | Coordinated MySQL **and private receipt files** backups, isolated restore, queue/worker supervision, monitoring, incident/rollback operator sign-off | High-priority launch stabilization; collect sanitized evidence |
| [#11](https://github.com/DenaSon/Denapax/issues/11) | Epic acceptance depends on #24 + #25 | Remains open until evidence-based closeout |

The previously observed `schedule:list`, `queue:monitor` and `queue:failed` outputs showed a scheduled task and zero pending/failed jobs at that instant; they **do not establish** persistent worker health, actual delivery, backups or restore readiness. Check `APP_DEBUG=false`, secret configuration, permission gates and queue retry logs on production without exposing credentials.

## 3. Change control after MVP freeze

1. **Keep current MVP stable.** No new features, broad refactors, speculative UI work or extra infrastructure by default. Fix verified P0/P1 production defects and operational/security issues promptly.
2. **Feedback intake:** capture real customer/operator observation, affected screen/flow, reproducible steps or sanitized evidence, impact and desired outcome. Never store PII or payment artifacts in public issues.
3. **Triage:** label P0 (payment/security/broken checkout), P1 (material operational/customer friction), P2 (usable enhancement), P3 (nice-to-have); deduplicate and estimate before owner approval. P0/P1 reliability work need not await a feature sprint.
4. **Approval and delivery:** group only approved changes into small Post-MVP scopes; create/maintain GitHub Issues with acceptance criteria. Use a scoped branch, essential targeted tests + CI, reviewable PR, verified merge to `master`, brief Persian report and targeted real-bot smoke after deployment.
5. **Protect invariants:** exact toman/IRR conversion, historical snapshots, manually approved payments, private receipts, customer isolation, shipping grams, outbox retries, role permissions and idempotent checkout.

**Existing Post-MVP backlog:** [#70 — per-variant price change history/audit trail](https://github.com/DenaSon/Denapax/issues/70). Remains **open and deferred** until prioritized/approved. Assess reuse of Spatie Activitylog before adding schema; log only genuine before/after changes with actor/time, preserve old order snapshots.

**No automatically scheduled next sprint.** Prioritize according to live feedback; review the existing [Sprint 1 historical record](24-post-mvp-sprint-1.md) without restarting it.

## 4. Practical post-launch verification (not marked complete here)

- Confirm representative product/variant photo and published-price behavior on actual Telegram after any deploy; compare admin variant/shipping fields vs. bot and saved IRR.
- Validate one controlled checkout and private receipt review/approval with authorized staff and correct customer notifications; never infer payment from a receipt alone.
- Review production error logs, HTTPS webhook, scheduler, supervised `database:notifications,default` worker, group-alert activation if enabled, and stalled/failed message alerts.
- Back up MySQL and private receipts consistently; perform an **isolated restore** and record the result.
- Run targeted privacy/unauthorized access and duplicate/replay checks; attach evidence to #24/#25 and close #11 only when its original acceptance is fulfilled.

## 5. Next conversation handoff

Read [root AGENTS.md](../AGENTS.md) and this milestone record **first**, then inspect the current `master`, open issues and recent green CI before changing code. The MVP has been accepted by the owner and is frozen. Product enhancements now require observed feedback, owner-approved scope and small PRs. E07 #11/#24/#25 are **not closed by this business decision**; track the residual risk explicitly.

Related: [toman rollout details](28-admin-toman-pricing.md) · [Phase 9 operator runbook](27-phase-9-admin-group-alerts.md) · [Phase 6 operational checks](16-phase-6-operator-runbook.md) · [UX handoff](24-post-mvp-sprint-1.md).
