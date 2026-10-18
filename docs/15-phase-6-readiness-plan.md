# Denapax — Current Delivery Status & Phase 6 Readiness Plan

**Checked against GitHub:** 2026-10-18. **Scope:** approved MVP v1.1; no new features assumed.  
**Status:** Five implementation phases merged to `master`; real integration, operational rehearsal and launch approval **PENDING**.

## Verified GitHub ledger

- **Six delivery phases** map to **seven Epics**: E01–E06 delivered over Phases 1–5; E07 covers Phase 6.
- **20 User Stories + 7 Epics = 27 GitHub Issues**, excluding Pull Requests. **18 Stories + 6 Epics closed**; **2 Stories + 1 Epic open**.
- Remaining: [#24 — regression/E2E/security](https://github.com/DenaSon/Denapax/issues/24), [#25 — production/deployment/monitoring/recovery](https://github.com/DenaSon/Denapax/issues/25), [#11 — E07 Epic](https://github.com/DenaSon/Denapax/issues/11).
- **Seven merged PRs:** [#1](https://github.com/DenaSon/Denapax/pull/1), [#29](https://github.com/DenaSon/Denapax/pull/29), [#30](https://github.com/DenaSon/Denapax/pull/30), [#31](https://github.com/DenaSon/Denapax/pull/31), [#32](https://github.com/DenaSon/Denapax/pull/32), [#33](https://github.com/DenaSon/Denapax/pull/33), [#34](https://github.com/DenaSon/Denapax/pull/34).
- Phase 5 code merged as `bf6ef852f6f95e3bc494874c5a8795a00c995d75`. The post-phase documentation commit also passed [CI](https://github.com/DenaSon/Denapax/actions/runs/39153631505). These are essential automated checks, **not live acceptance**.

### Completed implementation

| Phase | Implementation PR | Code/CI | Real integrated trial |
| --- | --- | --- | --- |
| 1 — Admin, Auth & Catalog | [#30](https://github.com/DenaSon/Denapax/pull/30) | Merged / passed | Not verified |
| 2 — Telegram & Customers | [#31](https://github.com/DenaSon/Denapax/pull/31) | Merged / passed | Not verified |
| 3 — Cart, Shipping & Checkout | [#32](https://github.com/DenaSon/Denapax/pull/32) | Merged / passed | Not verified |
| 4 — Manual Payments | [#33](https://github.com/DenaSon/Denapax/pull/33) | Merged / passed | Not verified |
| 5 — Fulfillment & Notifications | [#34](https://github.com/DenaSon/Denapax/pull/34) | Merged / passed | Not verified |
| 6 — Real E2E, Security, Launch | [#24](https://github.com/DenaSon/Denapax/issues/24), [#25](https://github.com/DenaSon/Denapax/issues/25) | Not complete | Not verified |

## Phase 6 — work sequence and proof required

### Gate A — Environment and integration preparation (Issue #25)

- [ ] Identify a **non-production or production-like** domain/HTTPS environment, hosting OS, deployment credentials and responsible operator. No assumption that GitHub CI equals a deployed service.
- [ ] Verify PHP 8.4/Laravel 13, MySQL, Redis, Node/Vite build artifacts, required PHP extensions and working secrets injection. Audit dependencies using a trusted advisory endpoint.
- [ ] Inspect and review all existing first-party and Spatie migrations, set up a **backed-up** MySQL database, then migrate and seed RBAC in authorized target environment. Verify administrator creation (no public registration or default credentials).
- [ ] Configure private receipts directory with correct ACLs/backup and public product-only media symlink; prove receipt images cannot be downloaded anonymously.
- [ ] Configure Telegram token/`TELEGRAM_WEBHOOK_SECRET` privately; set and inspect HTTPS webhook, confirm trusted update processing and retry/deduplication on real inbound updates.
- [ ] Configure Redis worker on `notifications` with supervision and Laravel scheduler; demonstrate notification logs and retry behavior.

### Gate B — Critical end-to-end and abuse-path tests (Issue #24)

- [ ] In actual Telegram, browse/search products and variants and view images; verify catalog activation.
- [ ] Register/use customer, add and switch saved addresses, build/edit a multi-item cart. Confirm exact grams/packaging boundary, quote in IRR and customer display in toman.
- [ ] Complete checkout once and replay confirmation; prove a single order with immutable product/price/address/shipping/card snapshots; re-price a product afterward and verify order unchanged.
- [ ] Upload a **test** private receipt, check staff-only media access, approve only after a **controlled verified test payment**; separately reject a receipt with reason and resubmit.
- [ ] Move only paid order through `confirmed → preparing → shipped → delivered`; save a real-looking **test** tracking code and verify customer-own order/status page. Do not send customers fake dispatch claims.
- [ ] Verify Telegram notices for create/receipt/approve/reject/preparing/shipped/delivered/cancel; simulate failed sends and observe bounded retries, duplicate delivery limitations and operator visibility.
- [ ] Negative/security paths: forbidden access to another customer's orders/addresses/receipts, inactive staff, wrong permissions, forged webhooks, repeated callbacks/concurrent updates, oversized/non-image receipt, hidden variants, exhausted retries.
- [ ] Do targeted browser/mobile Persian RTL smoke tests for product management, settings, payment review, order queue and notification log. Fix severity-one bugs before launch.

### Gate C — Operational sign-off and launch readiness (Issue #25 + Epic #11)

- [ ] Validate schedule/queue process restart and alerting, persistent logs, least-privilege DB/network permissions, `APP_DEBUG=false` and HTTPS behind actual reverse proxy.
- [ ] Back up **MySQL + private receipt media** together. Run at least one controlled **restore** test, document recovery and rollback.
- [ ] Provide release/runbook: migrations, configuration/secrets provisioning, webhook setup, queue/scheduler, health endpoints, rollback conditions, incident on-call, and test evidence links.
- [ ] Resolve or log severity of each finding and document release blockers. Do **not** close #24 or #25 until their acceptance criteria have evidence; do **not** close #11 until both stories and full release gate pass.
- [ ] Make an explicit **go/no-go** decision with the operator/owner before public traffic. Merge fixes only after PR + essential CI.

## Phase 6 kickoff (2026-10-18; verification ongoing)

- Scoped branch: `phase/6-e2e-security-launch` (do not merge until acceptance and essential CI).
- First security/test slice: Telegram webhook exceptions logged by **type only**, private receipt URL/authorization regression assertions, CI audit of production dependencies from lockfile.
- Operator procedure and evidence matrix: [Phase 6 Operator Runbook](16-phase-6-operator-runbook.md). All its integration and recovery evidence rows start **UNVERIFIED**.
- User-requested MVP bot UX improvement: add a **📖 Help** button immediately next to **☎️ Support** on the main Telegram menu; `/help` and `menu:help` explain browse → cart → address → checkout → select order → submit receipt → manual payment review → tracking, with navigation back to shopping, support and home. Include focused fake-bot checks. This change still needs live Telegram smoke verification.
- The work above does **not** satisfy Gates A/B/C or any launch acceptance. #24, #25 and #11 must remain open.

## What is *not* done as of this handoff

No access-backed proof of running MySQL/Redis, BotFather webhook configuration, actual Telegram updates/sent notifications, real manual bank reconciliation, full mobile/browser UI acceptance, backup/restore rehearsal or production release exists in the project record. Mark these **unverified**, never **passed**.

## Initial actions for the next agent

1. Read root [AGENTS.md](../AGENTS.md), approved [MVP Scope](01-mvp-scope.md), [execution policy](09-execution-agreement.md) and current [GitHub Issues #24/#25/#11](https://github.com/DenaSon/Denapax/issues/11).
2. Confirm current `master` and latest CI; review error and security boundaries before choosing the smallest needed fixes.
3. Create a Phase 6 plan/PR branch, work through Gate A then Gate B and Gate C. Prepare runbooks and test harness while infrastructure is unavailable; request only truly necessary sensitive integration access through approved secure means.
4. Keep the issue checklist updated with actual evidence. Submit a short Persian status after each meaningful step. No unnecessary tests, no unrelated features, no premature release declaration.

**References:** [Backlog](07-product-backlog.md) · [Traceability](08-mvp-traceability.md) · [Phase 5 handoff](14-phase-5-handoff.md).
