# Denapax — Post-MVP Sprint 1: Feedback-Led UI/UX

**Initiated:** 2026-10-19 · **Owner:** DenaPax · **Status:** INTAKE / NO IMPLEMENTATION YET

## Sprint scope freeze — 2026-10-19

**Owner accepted the recommendations and closed feedback collection at exactly 17 items.** This supersedes the historical “awaiting feedback” intake section below. All 17 items are tracked in [parent #56](https://github.com/DenaSon/Denapax/issues/56), with execution checklists attached to the two linked phase issues. The original E07 release/security/recovery Issues #11/#24/#25 stay open independently.

| Delivery phase | Approved IDs | Count |
| --- | --- | ---: |
| [Phase 7 — UI & microcopy #57](https://github.com/DenaSon/Denapax/issues/57) | PMVP-002, 003, 005, 007, 008, 010, 014 | 7 |
| [Phase 8 — UX & final polish #58](https://github.com/DenaSon/Denapax/issues/58) | PMVP-001, 004, 006, 009, 011, 012, 013, 015, 016, 017 | 10 |

### Final added feature — PMVP-017

**Review and explicitly confirm delivery address before persisting it.** Existing six-step address input saves after postal code without confirmation. Show a safe preview (recipient, phone, province, city, street address, postal code) and functional inline actions to **confirm/save**, **correct** a field and **cancel**. No persistence until confirmation. Apply to new-address and edit-address flows while protecting existing records and session ownership. This is separate from PMVP-004 (prompts) and PMVP-006 (cancel).

### Implementation sequence and agreed defaults

1. Phase 7 (7 items): concise cart and order messages, bold/selectable important info, Persian quantity/add-more labels, non-threatening accurate payment-review copy, concise product descriptions.
2. Phase 8 (10 items): normalized Persian search; usable address prompts, native cancel, address review; copy card number from payment snapshot; pagination cart **9 items/page** and catalog **10 products/page**; primary photo automatically above product details/full-screen native view; shared short **DN-** human reference for orders and admin search, preserving legacy orders; readable order history.

Preserve native Telegram Bot UX, customer and staff permissions, private receipts, manual payment approval, idempotent checkout, financial snapshots and exact shipping arithmetic. Use essential targeted tests and real Telegram/mobile smoke. Each completed phase gets its own PR with green CI and verified merge to `master`; update status and deliver a concise Persian report. **Phase 7 application changes are tracked in [PR #61](https://github.com/DenaSon/Denapax/pull/61); verify its actual merge/CI status on GitHub. Phase 8 has not started.** Keep #56 open as parent until both #57 and #58 pass and close.

---

## Baseline and release boundary

- The owner confirms the **MVP is live** and the functional end-to-end workflows that were exercised are working (2026-10-19). This is an **owner report** of functional acceptance, not independently attached test evidence for every E07 gate.
- Implementation phases 1–5 are merged, and the current master CI was verified green at `c5fa82d5376129b5e2da13689239bce646dfdf1b` ([run 39229685750](https://github.com/DenaSon/Denapax/actions/runs/39229685750)).
- The original **Phase 6 E07 operational/release checklist is not formally closed**: [#24](https://github.com/DenaSon/Denapax/issues/24) regression/security evidence, [#25](https://github.com/DenaSon/Denapax/issues/25) backup/restore/monitoring/rollback evidence, [#11](https://github.com/DenaSon/Denapax/issues/11) Epic final gate. Do not turn owner-reported functional testing into a fabricated restore/security sign-off.
- Previous [UX-1..4](telegram-ui-ux-guidelines.md) (#38–#41, PRs #43–#46) are finished. Sprint 1 must address **new real-world feedback**, not recreate prior polish.
- **Architecture remains unchanged:** regular Telegram Bot and Laravel Livewire/Mary UI admin; database queue + scheduler; no Mini App, web store, Redis or Horizon requirement.

## GitHub tracking

- [Sprint intake/approval parent — #56](https://github.com/DenaSon/Denapax/issues/56)
- [Phase 7 — UI/RTL/Persian copy — #57](https://github.com/DenaSon/Denapax/issues/57)
- [Phase 8 — UX/navigation/error recovery — #58](https://github.com/DenaSon/Denapax/issues/58)

## Feedback intake (Step 0: observation only)

Until the owner explicitly states **«لیست کامل شد»**, do not write UI/UX implementation code or preemptively declare findings solved. Capture each observation as a numbered `PMVP-###` item. A useful entry contains:

| Field | Meaning |
| --- | --- |
| ID | `PMVP-001`, `PMVP-002`… |
| Source | Owner/customer (never customer-identifying details) |
| Surface | Telegram / admin; exact screen/interaction |
| Current / expected | What is wrong, preferred behavior or exact desired Persian wording |
| Priority | P0 unsafe/blocked; P1 high-friction; P2 polish; P3 nice-to-have |
| Evidence | Sanitized screenshot or reproducible steps |
| Phase | 7 visual/copy or 8 interaction/flow |
| Acceptance | One testable outcome and lean regression check |

De-duplicate overlapping observations and preserve existing implemented behavior. Only approved findings become individual execution issues; link them to #56 and the relevant phase.

## Planned delivery

### Phase 7 — UI and content refinement

Focus only on approved visual/copy issues: RTL and typography, spacing, labeling, Persian microcopy, readable error/empty states, native Telegram button/style consistency, and affected admin responsive screens. Respect Telegram Bot UI limitations; no invented custom Telegram rendering.

**Exit:** approved findings implemented, affected screens manually checked, essential automated tests + CI green, scoped PR merged to `master`, short Persian phase closeout with merge SHA.

### Phase 8 — UX flows and final polish

Focus only on approved interaction issues: navigation/back behavior, discoverability, flow friction through catalog/cart/checkout, payment receipt clarity/recovery, orders/notifications feedback, and admin operations.

**Exit:** approved flows passed on real Telegram/mobile where relevant, no critical business regressions, essential automated tests + CI green, scoped PR merged to `master`, short Persian phase closeout with merge SHA.

## Invariants and exclusions

Do **not** casually change checkout idempotence, order/address/amount snapshots, gram-based shipping tariff, receipt privacy, manual independent payment approval, RBAC/customer ownership checks, or outbox retry behavior. Preserve active Reply Keyboard mapping and inner-page Inline Keyboard callback correctness. Never upload customer data, actual receipts, card details, .env files or bot tokens to GitHub. Do not introduce speculative scope, excessive tests, or additional infrastructure.

## Acceptance / open questions

- **Feedback list:** Finalized and owner-approved at 17 items on 2026-10-19; this overrides historical intake instructions above.
- **Prioritized backlog:** Approved and mapped to #57 / #58.
- **Phase 7 implementation:** [PR #61](https://github.com/DenaSon/Denapax/pull/61) — verify live merge/CI status and [issue #57](https://github.com/DenaSon/Denapax/issues/57).
- **Phase 8 implementation:** Not started.
- **Original Phase 6 operational closure:** Pending linked release evidence, tracked independently from this UX sprint.

**Working agreement:** Use a scoped branch per approved phase, essential CI, PR to master, merge only after phase exit gate, concise Persian delivery summary. See [AGENTS](../AGENTS.md) and [execution agreement](09-execution-agreement.md).
