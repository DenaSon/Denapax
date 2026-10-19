# Post-MVP Sprint 1 — Phase 8 UX Handoff

**Scope:** [Phase 8 #58](https://github.com/DenaSon/Denapax/issues/58), [parent #56](https://github.com/DenaSon/Denapax/issues/56), [implementation PR #62](https://github.com/DenaSon/Denapax/pull/62).  
**Frozen feedback:** exactly 10 findings; Phase 7 already merged [#61](https://github.com/DenaSon/Denapax/pull/61).

## Implemented in Phase 8 branch

| PMVP | Delivery |
| --- | --- |
| 001 | Persian search tolerant of Alef/Madda, Arabic/Persian Y/K and spacing/diacritics (bounded SQL REPLACE, without broad fuzzy search). |
| 004 | Prompts emphasize the **specific field**, not a competing address-creation title. |
| 006 | Native inline **❌ لغو** on address/search/receipt-entry flows; typed `/cancel` remains supported. Cancelling the form does not cancel a submitted order. |
| 009 | Native **📋 کپی شماره کارت** from the stored **order payment snapshot**, with selectable `<code>` text fallback. |
| 011 | Nine distinct cart lines per page, preserving whole-cart subtotal and item-specific callback identifiers. |
| 012 | Ten products per catalog/search/category page; page indicator hidden on a single page. |
| 013 | Main gallery photo, else first available variant image, attempted before text via Telegram native `sendPhoto`. Prefer direct `InputFile` upload from local Spatie media storage rather than relying on public HTTPS URL; graceful text fallback and error *type* only in log. Existing gallery callbacks remain. Production attachment existence, storage mounts and phone preview must be validated by operator. |
| 015 | Stable short `DN-#########` public references for all new/legacy orders using a one-to-one bounded permutation of internal order ID. **Original `order_number` never changes**; existing snapshots/exports remain. Admin search matches new public code and original legacy references; callbacks still authorize by authenticated customer + internal ID. |
| 016 | Readable My Orders list with short reference, order status and total in a **message**, plus short per-order detail buttons. |
| 017 | Staged six-field address flow: no write until **✅ تأیید و ذخیره نشانی**, individual-field **✏️ اصلاح اطلاعات**, and safe cancel. Editing is staged; saved address stays unchanged unless explicitly confirmed. |

## Invariants

- No schema/migration changes, order-number mutation, cart/checkout pricing changes, shipping calculations, order state or payment verification changes.
- Product and payment snapshots are preserved; card text is not logged to GitHub or application exceptions.
- Telegram UI remains native bot messaging and inline keyboards, **no Mini App**.
- Current production risk: session/queue, Telegram client rendering, media storage on deployment and HTTPS, and clipboard support must be checked manually after deployment.
- A separately requested **admin group alert** feature is deferred until after the open phases; target one configured private group ID, **not planned or implemented here**.
- The remaining original E07 operational/security/backup/restore gates #11/#24/#25 remain open independently; do not claim they passed.

## Verification and release

Focused CI should pass PHPUnit, Composer validation/audit, Pint and Vite; add minimal regression tests for address review/cancel, unique public codes/admin search, copy button, Persian search, 9/10 pagination. After merge to `master` and actual rollout, operator should exercise the Telegram iOS/Android screens with synthetic data, inspect the primary image and copy-card action, and record results in #58. **CI alone cannot prove deployment or live-device behavior.**

**PR/merge SHA and master CI:** See live [PR #62](https://github.com/DenaSon/Denapax/pull/62) and [Issue #58](https://github.com/DenaSon/Denapax/issues/58) for verified status.
