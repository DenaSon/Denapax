# Denapax — MVP Scope v1.1
**Status:** Approved product scope · **Date:** 2026-10-18 · **Locale:** fa-IR

## Goal
Launch a Telegram-message-and-button storefront for Dena souvenirs/dried fruits, with a separate Laravel web administration panel. Implement a maintainable modular monolith without adding out-of-scope capabilities.

## Channels
- **Customer:** ordinary Telegram Bot; no Mini App or consumer web storefront in MVP.
- **Admin:** Web UI (Laravel + Livewire + Mary UI).
- **Shared backend:** catalog, cart, checkout, manual payment review, shipments and notifications.

## Required customer capabilities
1. Paginated product list with image, title, summary, category and product-level key/value features.
2. Search product names and descriptions.
3. Predefined product variants with **independent price**, sale quantity and unit (`g`, `kg`, `l`, `piece`), plus available/unavailable flag.
4. Product image gallery and optional image belonging to a variant.
5. Multi-item shopping cart with item quantities, update and removal.
6. Checkout collecting recipient name, mobile, full address and postal code; store customer profile and multiple selectable addresses for future orders.
7. Show goods subtotal, nationwide weight-based shipping fee, and total **before** customer confirms order.
8. Issue unique order number; show card-to-card payment instructions.
9. Receive photo/document receipt through Telegram; review happens manually by admin.
10. On rejection, show reason and permit another receipt. Preserve previous attempts.
11. List customer's own orders and show order status, unique order number and postal tracking code when available.
12. Transactional Telegram notifications for order creation, receipt reception/rejection/approval, preparation, dispatch, delivery and cancellation.

## Admin capabilities
- CRUD/activation for categories, products and variants; product summary, description and key/value features; gallery plus optional variant images.
- Edit price, sale quantity/unit, **shipping weight in grams**, and availability.
- Search/filter orders; inspect customer snapshot, line items and receipts; approve/reject receipts with reason.
- Change permissible order states and record postal tracking code.
- Configure nationwide shipping parameters, shared packaging weight, card-payment display details and support contact.
- Restricted admin login using Fortify and Spatie Permission RBAC (seeded staff roles, no public registration); audit of sensitive order/payment actions via Spatie Activitylog.

## Shipping (one national rate)
Every sellable variant has a strictly positive `shipping_weight_grams`. All weights are in **integer grams**, regardless of selling unit.

```text
W = sum(item.shipping_weight_grams × item.quantity) + packaging_weight_grams
extra_tiers = ceil(max(0, W - base_weight_grams) / extra_weight_step_grams)
shipping_fee = base_price_irr + extra_tiers × extra_step_price_irr
order_total = goods_subtotal_irr + shipping_fee_irr
```

Settings are editable by the admin. Suggested initial tier sizes: base 1000g; each extra step 1000g. **Prices are configurable; example prices are not official postal quotes.** A weight of 1001g enters the first extra tier. One order is treated as one parcel in MVP. Save the actual weight, fee and settings-derived result at checkout; do not recalculate existing orders.

## Payment and fulfillment
- Payment method: **card-to-card only**, no payment gateway or automatic bank verification.
- A receipt in Telegram is **not** proof of paid status until a human admin verifies the transfer independently.
- Payment statuses: `awaiting_receipt`, `under_review`, `rejected`, `approved`.
- Order statuses: `awaiting_payment`, `confirmed`, `preparing`, `shipped`, `delivered`, `canceled`.
- No preparation/dispatch before payment approval.
- Staff enter postal tracking code manually; no direct postal API.
- Before approval, admin may cancel unpaid orders. Paid-order cancellation/refund workflow is excluded.

## Data rules
- Each product has exactly one category; product features belong to products, not variants.
- Catalog prices and weights are editable; existing order line items, address, card-payment instruction snapshot, and shipping fee never change retrospectively.
- Store money as integer **IRR**; display toman to users. No separate tax/processing fee in MVP.
- Inventory: availability only; no numeric stock deductions/reservations.
- Authenticate customer via Telegram identity; an order lookup must verify ownership.
- Process Telegram webhook updates idempotently; queue notifications with event deduplication and retry handling.
- Keep receipts in private storage, not public URLs; require admin authorization for viewing.

**Package and migration baseline:** [Stack & Windows bootstrap](05-stack-and-windows-setup.md) and [Database Design](03-database-design.md). RBAC authorization exists from MVP, while a visual role-management interface is out of scope.

## Out of scope
Web storefront, Telegram Mini App, online gateway, automatic bank/payment checks, stock quantities/warehousing, coupons, loyalty points, automatic shipping carrier pricing, destination-zone pricing, postal API/status syncing, marketing broadcast, SMS, returns/refunds automation, multi-vendor, advanced accounting, and elaborate RBAC.

## Acceptance criteria
- Admin creates a product with multiple variants, gallery and per-variant pricing/shipping grams.
- Customer searches, views, selects variant and buys multiple items through Telegram.
- Checkout captures recipient and delivery address, computes shipping correctly at under/exactly/over tier boundaries, and produces an immutable financial snapshot.
- Receipt can be reviewed, rejected with reason, resubmitted and approved; older receipt attempts remain visible.
- Only approved orders can progress through preparation, dispatch and delivery; postal tracking code appears in customer's order.
- All required transactional notifications are queued, sent/retried and deduplicated.
- Customer cannot access another customer's order.
- Duplicate Telegram delivery does not create duplicate orders or receipts.
- Backups, logs and critical-flow automated tests pass.

## Fixed design decisions
- Predefined variants (no arbitrary-weight pricing).
- Variant needs only title, sale quantity and unit (no variant option matrix).
- Product-level gallery, optional dedicated variant image.
- One category per product.
- Persistent customer profile and multiple addresses.
- Subtotal + shipping only (subject to any future legal compliance requirements).
