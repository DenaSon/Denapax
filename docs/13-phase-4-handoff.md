# Phase 4 — Manual Card-to-Card Payments

**Scope:** #18, #19, #20 (MVP v1.1)  
**Development branch:** `phase/4-manual-payments`  
**Status:** Completed and merged to `master` via [PR #33](https://github.com/DenaSon/Denapax/pull/33), commit [`5166281`](https://github.com/DenaSon/Denapax/commit/51662816cb189f192fbd0467b2e4a627806fa2ba).  
**Validation:** Core PHPUnit tests, Pint, Composer manifest, and Vite build passed on the PR.

## Design implemented

- `payment_receipts` tracks attempt number, order owner, private image path, optional Telegram `file_id`, status, reason, reviewer and timestamps. Historical attempts remain unchanged.
- Customer uses **/receipt** or the inline button after checkout to select their own eligible order. The bot prompts for one photo or image document (JPEG, PNG or WebP, at most 8 MiB). A private upload session expires after 30 minutes.
- Telegram file is downloaded via Nutgram Laravel's `downloadFileToDisk` mixin to dedicated **non-public `receipts` disk** under `storage/app/private/receipts`. Server validates reported metadata, actual byte length, detected MIME and image structure. Invalid uploads are deleted.
- DB submission locks order, rechecks customer ownership and payment eligibility, prevents a second pending attempt, increments attempt number, updates `payment_status` to `under_review` and writes `payment.receipt_submitted` outbox event in one transaction.
- Livewire/Mary admin payment review screen at `/admin/payments` is gated by `payments.view`; private image endpoint at `/admin/payments/receipts/{receipt}/image` requires authenticated active staff and `payments.view`. It never uses public URLs, temporary links or `storage:link`.
- Only staff with `payments.review` can approve/reject. Rejection requires reason; confirmation must follow actual bank transfer verification outside Denapax. Decisions lock the order, revalidate latest pending receipt and write domain payment/order statuses, reviewer/timestamps, Spatie activity log and receipt-scoped outbox event atomically.
- An approval moves `awaiting_payment` to `confirmed`; rejection leaves order awaiting payment and allows resubmission. Stale or duplicate review cannot perform another transition.
- Rejected reason is persisted and shown when the customer reopens the selected order. **Automated Telegram decision notifications** will be delivered by the Phase 5 outbox worker; creating outbox records in Phase 4 does *not* imply messages were delivered.

## Real environment and handoff

- **Not performed:** actual Telegram Bot API media download, physical payment/bank verification, MySQL production migration, admin browser visual QA, Redis worker and end-to-end live testing. They belong to Phase 6 unless limited environment-specific checks become possible.
- Before integration, set real `TELEGRAM_TOKEN`, verified webhook secret and DB settings **outside Git**. Verify filesystem write permissions for `storage/app/private/receipts`; do not expose that directory via web server or Laravel public storage symlink.
- The receipt storage disk uses local private files; production backup policy must cover receipts and MySQL together.
- For payment reviewer provisioning, grant `payments.review` to authorized staff (seeded super-admin by default); regular operators may only have `payments.view`.
- **Receipt is never equivalent to bank confirmation**. Bank verification is a manual external action.

## Next
Phase 5 — admin order queue, fulfillment, tracking and transactional notification dispatch with retries.

## Final Phase 4 report
- **Delivered:** verified private-image ingestion pathway, receipt attempt history, Telegram resubmission UX, authorized manual review and immutable payment decisions with outbox records.
- **CI verified:** [PR validation](https://github.com/DenaSon/Denapax/actions/runs/39149814334) passed. Local MySQL, Telegram Bot API and bank/deployment are *not* verified.
- **Merged:** [PR #33](https://github.com/DenaSon/Denapax/pull/33) into `master` at `51662816cb189f192fbd0467b2e4a627806fa2ba`.
- **Pending:** Phase 5 outbox worker/Telegram notices, order fulfillment and tracking; Phase 6 real-world end-to-end integration and backup/restore.
