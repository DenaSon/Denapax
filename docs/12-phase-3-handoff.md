# Phase 3 — Cart, Shipping & Checkout

**Status:** Completed and merged to `master` via [PR #32](https://github.com/DenaSon/Denapax/pull/32); merge commit [`b90bb2a`](https://github.com/DenaSon/Denapax/commit/b90bb2acd49d4d421f5602b26a74e4f41909f836). Essential PR and master CI are green.
**Issues:** #15, #16, #17. Reference: MVP Scope v1.1, Database Design v1.1 and Workflows v1.1.

## Delivered
- Cart, cart_items (one per variant), shipping_settings, orders, order_items, order_status_histories and outbox_events migrations.
- Domain/cart operations lock the owning customer row, enforce integer item quantity (1..1000), re-check variant eligibility and allow remove/change quantity.
- Shipping admin `/admin/shipping` protected by `shipping.manage`. Singleton shipping parameters: base/extra grams and IRR fees, packaging grams. No inferred official postage costs.
- Checkout preview using authoritative database variant prices, active flags, customer-owned saved address, store card details and `ShippingCalculator`.
- Final confirmation validates an exact preview fingerprint and one-use UUID; creates a single order with immutable item, delivery, price, postage and payment-instruction snapshots, initial domain state history, and `order.created` outbox event **in one transaction**, then clears the cart.
- Telegram inline buttons for variants, quantity adjustment, remove, choose address, preview and confirm. Duplicate confirm shows existing owned order details.
- Payment state remains `awaiting_receipt`; receipt upload and human approval are Phase 4. Outbox delivery worker and notifications are Phase 5.

## Environment/verification boundary
- Essential PHPUnit feature tests check two-item shipping tier with packaging, idempotent confirmation, catalog/address/card/tariff snapshot stability, stale-price refusal, ownership and authorized tariff editing. Earlier shipping calculator boundary tests remain.
- **Not claimed:** live Telegram/MySQL/Redis integration, production migrations, receipt verification or full customer journey. These are verified on a deployment-capable environment in Phase 6.
- Server setup after merge: configure MySQL and credentials **outside git**, `php artisan migrate`, seed roles, set bank/display details and shipping tariff in admin before attempting checkout. Do not invent an official postal rate.
- `outbox_events` records `order.created` transactionally; Phase 5 will implement dependable Telegram delivery, bounded retries and notification logs.

## Exit gate
Working Telegram cart/checkout routing + essential CI checks passed + reviewed schema and snapshot behavior. **Passed:** PHPUnit, Pint, Composer validation and Vite build on PR and `master`. [Verified master CI](https://github.com/DenaSon/Denapax/actions/runs/39148387532). Merge SHA: `b90bb2acd49d4d421f5602b26a74e4f41909f836`.

## Phase 3 completion report
- **Delivered:** Telegram multi-item cart, integer quantities, national configurable shipping tariff and protected admin screen; customer-owned address selection; confirmed order and item/card/address/shipping snapshots; idempotent UUID confirmation and transactional cart clearing, history and `order.created` outbox event.
- **Tests:** Essential PHPUnit (shipping boundaries, checkout totals, duplicate confirmation, historical snapshot stability, stale-price refusal, customer ownership and admin tariff authorization), Pint, Composer validate, Vite build passed on `master`. No live Telegram/MySQL/Redis exercise was performed.
- **Merge:** [PR #32](https://github.com/DenaSon/Denapax/pull/32) merged at [`b90bb2a`](https://github.com/DenaSon/Denapax/commit/b90bb2acd49d4d421f5602b26a74e4f41909f836).
- **Remaining:** Manual bank receipt upload/review/resubmission remains Phase 4; outbox delivery/notifications Phase 5; production-like live tests Phase 6. Set bank instructions and shipping rates on the target environment before accepting actual orders.
- **Next:** Phase 4 — manual card-to-card payments with private receipt attempts and staff review.
