# Denapax — Admin UI/UX polish and operations handoff

**Implemented in code:** PR [#86](https://github.com/DenaSon/Denapax/pull/86) · 2026-11-15. **Not a production deployment or real-device audit.**

## Why

Denapax's staff/admin already had RBAC, catalog, shipping, orders, private manual receipt review, Zarinpal bank reconciliation and notification logs. The previous admin was operational but plain: a fixed left menu on small screens, counts focused only on catalog, raw English order/payment codes, an unscoped global rejection text area, and a stale Telegram label in the web notification report.

This phase makes daily work understandable on desktop and mobile while retaining the original domain authorization, payment safeguards and business workflows.

## Implemented

### Admin shell and accessibility

- Responsive forest-green header, Vazirmatn with system fallback, light paper surfaces, compact mobile native-details navigation and persistent desktop sidebar.
- One permissions-filtered navigation partial across desktop/mobile, active route states, skip link, visible keyboard focus, minimum 44px touch targets and accessible status regions.
- Admin-only styling in `resources/css/app.css`; customer Landing/Store CSS remains unchanged. No client-side navigation framework or extra JS requirement.

### Action-oriented dashboard

- Paid-and-confirmed orders ready for preparation, preparing orders, shipped orders, awaiting-payment orders, recent orders and failed web-notification records.
- **Only staff with `payments.view`** see counts/links for pending manual receipts and unresolved Zarinpal attempts. Catalog totals are only calculated for staff with `catalog.manage`.
- Clickable action cards open the relevant pages. Order filter shortcuts preserve `orderStatus`/`paymentStatus` through Livewire URL properties.

### Orders and fulfillment

- Human-friendly Persian status labels, accessible status chips, readable toman totals, responsive order cards on phones and a table on larger screens. Search and date filters are unchanged.
- The order detail prioritizes **status → authoritative total → items → address/shipment → payment history → next action**. Confirmations and backend `TransitionOrder` permissions remain authoritative.
- If any Zarinpal payment attempt is unresolved, the detail page warns staff not to request another charge or cancel it; the unsafe cancel button is hidden in addition to existing server-side guards.

### Manual receipt review

- Private evidence viewer remains authorized and opens a private staff route; the UI always emphasizes that an image is **not** bank-settlement proof.
- The reject-receipt reason is now revealed for a **specific selected receipt** instead of permanently occupying every pending receipt card. Switching/canceling resets it. A validation error still requires a reason. Bank confirmation and RBAC in `ReviewPaymentReceipt` stay unchanged.
- Only staff with `payments.review` can approve/reject or open the reason control; `payments.view` staff can inspect without making a financial decision.

### Zarinpal and web notifications

- Gateway status translated to plain Persian without hiding technical Authority/Ref ID/inquiry status needed for reconciliation.
- Keep all bank safety rules: recent failed/reversed inquiry, mandatory evidence, `requires_review` hold, and no automatic retry. No new API changes, payment triggers or refund actions.
- Retired Telegram report language is replaced with accurate **web customer inbox notifications**, not SMS. Retry count displays the actual attempts instead of an inaccurate hardcoded maximum.

## Automated checks and release caveats

Feature tests assert: actionable cards, RBAC omission of payment counters/actions, Persian statuses, selected receipt rejection workflow, old-Authority cancellation warning, and web notification wording. Continue running full PHPUnit, Composer locked audit, Pint and Vite build.

**Still pending for actual release:** real Android/iOS staff browser acceptance (mobile menu, long shipping addresses, one-hand receipt review), authenticated production-like environment, MySQL/migration rehearsal, SMS OTP, real Zarinpal bank inquiry/settlement, backup/restore, monitored workers and owner GO/NO-GO.

See [current web delivery ledger](39-web-roadmap-status.md), [Phase 4 operational gates](38-web-phase4-release-readiness.md) and [Phase 4A gateway reconciliation](40-web-phase4a-gateway-reconciliation.md).
