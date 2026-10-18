# UX-4 — Customer Checkout, Receipts, Orders & Notifications Handoff

**Work item:** [UX-4 Issue #41](https://github.com/DenaSon/Denapax/issues/41)  
**Implementation:** [PR #46](https://github.com/DenaSon/Denapax/pull/46) (verify actual merge and current CI before reporting).  
**Scope:** Fourth and final **Telegram UX polish sub-phase** — no payment-gateway, no Mini App, no domain schema rewrite. This phase's implementation merge does **not** constitute launch acceptance.  
**Design source:** [Telegram UI/UX brand guide](telegram-ui-ux-guidelines.md).

## Changes

1. **Checkout navigation and final quote:** choose or enter delivery address with cart/home navigation; a final quote explicitly separates recipient/mobile/postal address, goods subtotal, calculated shipping fee, parcel weight and final payable amount. Existing authoritative `PrepareCheckout` fingerprint and `CreateOrder` remain unchanged. Stale confirmation instructs customer to re-evaluate the quote instead of guessing.
2. **Post-confirmation payment instructions:** an unpaid order shows its real immutable snapshot card details, number, payable amount and `در انتظار پرداخت`; buttons link to **receipt for that exact owned order** and **its details**. Repeated confirmation callback for a **paid/canceled** order does not instruct customer to transfer money or upload again; it gives real status with link to the order.
3. **Receipts are always linked to a chosen order:** main receipt entry lists only the customer's awaiting-payment orders with number, payable amount and review status; selecting one shows exact amount and card details if applicable. Rejected receipts show reason and direct resubmission for the same order; under-review state **disables duplicate-upload shortcut**. Receipt upload acceptance still uses private receipt storage + `SubmitPaymentReceipt` authorization and review (NOT proof of payment). On upload, a clear not-approved statement and order-status shortcut are shown.
4. **Own order detail and shipment:** shared `OrderStateLabels` prevents `confirmed` being shown as `preparing`; own-only details list goods, shipping and total plus line items, applicable receipt action, carrier and tracking if shipped. Bad/stale customer-owned lookup has safe navigation recovery.
5. **Eight outbound transactional notifications:** concise, factual copy for created, receipt submitted/rejected/approved, preparing, shipped, delivered and canceled. Payment approval means `confirmed`/queue for preparation **not** preparation underway. Shipping uses real carrier/tracking or `ثبت نشده` (not an invented default carrier). No extra marketing or fabricated delivery commitment.
6. **Navigation/accessibility:** normal native Telegram buttons and existing UX-2 colors; states communicated by Persian text as well as emoji. No public/private image exposure, payment status spoofing or new persistence of secrets.

## Verification

- Focused [`TelegramCheckoutStatusUxTest.php`](../tests/Feature/TelegramCheckoutStatusUxTest.php) covers existing-order checkout confirmation behavior before and after payment; customer-owned receipt selection and rejection/under-review distinctions; another customer's inability to see order or receipt details; order approval vs preparation vs shipped tracking; all eight notification events.
- Existing `PhaseThreeCheckoutTest`, `ManualPaymentTest`, `PhaseFiveFulfillmentTest`, `PhaseFiveNotificationsTest` and webhook/Telegram tests remain the invariant protection for ID-based ownership, payments, idempotence, immutable snapshots, dispatch state, private media and outbox/queue.
- **CI status:** see latest result for [PR #46](https://github.com/DenaSon/Denapax/pull/46) and `master`. Do not rely on docs for current execution state. Run PHPUnit, Pint, Composer validate and Vite build before merging.

## Phase 6 launch gates still OPEN

**No verified full real Telegram → customer → checkout → bank-confirmed test receipt → rejection/resubmission → approval → preparation → shipment → carrier tracking → delivered journey.** Manual bank validation, BotFather token/webhook, HTTPS, MySQL/Redis worker, backups/restore, rollback, operator go/no-go and Persian/RTL/mobile rendering remain **unverified** until evidenced in [Phase 6 readiness](15-phase-6-readiness-plan.md) and [Issues #11](https://github.com/DenaSon/Denapax/issues/11), [#24](https://github.com/DenaSon/Denapax/issues/24), [#25](https://github.com/DenaSon/Denapax/issues/25). Do not close those from passing fake-bot tests.

Operator Gate B smoke checklist specific to UX-4:
- [ ] Verify original order review card details and amount against real authorized test transfer and saved snapshots.
- [ ] Select between two customer-owned unpaid orders, upload receipt to the selected one only, verify 8MiB/file type/private ACL boundaries.
- [ ] Reject receipt with reason, resubmit same order, approve after independent test bank check, verify no second receipt upload while under review.
- [ ] Confirm distinct notices: `payment.approved` ≠ `order.preparing`, later `order.shipped` with trustworthy carrier and tracking.
- [ ] Replay old checkout confirmation after payment: must show current status **not** new payment instructions.
- [ ] Inspect Persian RTL, light/dark, iOS/Android and link usability on actual Telegram; sanitize screenshots/records.

## Continuation and overlap warning

UX-1 (#38), UX-2 (#39), UX-3 (#40) and UX-4 (#41) are separate UX polish issues. **Do not mistake the UX track for the six core MVP phases**. The draft Phase 6 PR [#36](https://github.com/DenaSon/Denapax/pull/36) still contains stale Telegram bot/help/test code; rebase or otherwise reconcile with latest `master` so it does not overwrite UX-1 through UX-4. Keep Phase 6 gates and issue evidence accurate.
