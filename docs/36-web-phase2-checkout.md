# Web Phase 2 — Storefront, addresses and checkout

**Scope:** Close the browsing-to-order-registration loop inside Denapax's Persian RTL website without activating payments prematurely.

## Implemented

- Public product search with Persian/Arabic letter normalization and category filters, including safely escaped query handling and server-rendered results.
- Persistent/guest cart navigation, availability-aware checkout button, and customer OTP return-to-checkout.
- Owner-scoped saved address list/create/edit/default/delete, Iranian mobile normalization, Persian/Arabic postal-code digit conversion and existing transactional address domain actions.
- Guarded `/checkout` → `POST /checkout/preview` → `GET /checkout/review` → `POST /checkout/confirm` → authenticated `/orders/{orderNumber}`.
- Preview TTL 30 minutes, per-session customer and checkout key binding, recheck of the authoritative server-side quote, immutable order and item snapshots and idempotent replay through existing `CreateOrder`.
- Shipping shown in toman using stored integer IRR, calculated from variant weight plus packaging under the existing nationwide shipping tariff.
- Existing protected admin order and fulfillment views continue to handle new web-created orders.

## Not in this phase

Online bank gateways, manual web receipt upload, payment confirmation UX, customer order history/tracking and customer-facing notifications inbox remain **Phase 3**. Order confirmation explicitly tells the customer to refrain from transferring money until the secure payment experience is live.

## Release gates

No production activation before configuring HTTPS customer OTP delivery, real settings for shipping and payment instructions, and completing phase 3. Before rollout: re-run CI, verify the Phase 1 database cutover and a consistent MySQL/private-receipt backup, and exercise E2E in staging against MySQL. The automated tests do not prove SMS delivery or production restore success.

## Essential regression tests

- Guest checkout requires mobile authentication; verified customer can add a cart item and proceed.
- Persian phone/postal input is normalized, saved addresses are owner scoped, and unauthorized edits and order reads return 404.
- Preview shows exact fee/weight/toman values; confirmed order snapshots totals, empties cart and creates one outbox event.
- Duplicate confirm does not create multiple orders; stale prices and changed cart data are rejected while preserving the cart.
- Public search respects Persian normalized terms and does not expose unrelated products.
