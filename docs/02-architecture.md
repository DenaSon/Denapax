# Denapax — Architecture and Boundaries v1.0

## Architectural style
**Modular Monolith**, one Laravel application and database. Avoid microservices, unnecessary generic repositories, and a second domain model for Telegram. Keep framework adapters thin and business rules reusable.

## Logical modules
| Module | Responsibility |
| --- | --- |
| Catalog | Categories, products, images, product features, predefined variants/prices/availability |
| Customers | Telegram customer identity, saved recipient/address records |
| Cart | Active cart and cart lines |
| Orders | Checkout orchestration, immutable order snapshot, order statuses, audit history |
| Payments | Card instructions snapshot, private uploaded receipt attempts, manual approval/rejection |
| Shipping | Unit-weight calculation, settings, shipment tracking metadata |
| Notifications | Transactional event outbox and Telegram send/retry logs |
| Telegram | Webhook authenticity, update deduplication, button callbacks, conversation state |
| Administration | Authenticated Livewire admin use cases, policy checks and UI |

## Suggested Laravel code organization
```text
app/
  Domain/
    Catalog/
    Customers/
    Cart/
    Orders/
    Payments/
    Shipping/
    Notifications/
  Application/
    Actions/
    DTOs/
  Integrations/
    Telegram/
  Http/
    Controllers/
      TelegramWebhookController.php
  Livewire/
    Admin/
  Models/                 # Eloquent models or module-local persistence models
database/
  migrations/
  factories/
  seeders/
docs/
tests/
  Feature/
  Unit/
```
These are boundaries, **not a requirement** to create every directory immediately.

## Dependency direction
Telegram handlers and Livewire controllers call Application actions. Application actions coordinate transactions and invoke domain services. Domain logic should not depend on Telegram message formats or Livewire state. Persistence may use Eloquent while avoiding business logic inside UI classes.

Examples: `CreateOrder`, `SubmitPaymentReceipt`, `ApproveReceipt`, `RejectReceipt`, `AdvanceOrderStatus`, `ShippingCalculator`.

## Storage and integration
- **MySQL:** relational business records, financial snapshots, unique constraints.
- **Redis queues:** outbound notifications and other nonblocking work.
- **Private filesystem/object storage:** receipts; public/object storage for catalog images only.
- **Telegram Bot API:** webhook, inline keyboards, media IDs, transactional messages.
- Admin sessions use standard Laravel authentication. Validate Telegram webhook secret; derive customer identity from trusted update payload.

## Transactions and idempotency
- Checkout transaction checks current variants/prices/availability and writes order, order items, address snapshot and shipping snapshot once.
- `telegram_updates.update_id` unique; a repeated update must not duplicate an action.
- For inline callbacks also track one-time checkout commands / idempotency keys.
- Only one pending receipt per order; reviewing and resubmitting must be guarded by a transaction/row lock.
- Receipt approval atomically records reviewer/decision, updates order/payment statuses, appends history and emits an outbox event.
- Background sender publishes queued outbox events and records attempts/results. Retrying cannot cause duplicate logical event creation.
- Use an explicit allowed-transition map; no direct state mutations from UI.

## Monetary, weight and search conventions
- Monetary values: integer IRR. UI displays toman by dividing by 10; no floating point for money.
- Shipping weights: integer grams. Sale quantity can be decimal but **cart item count** is positive integer.
- Search MVP: indexed category/visibility filters plus controlled text matching for name/description; measure query performance before introducing a search engine.
- Store all timestamps in UTC; display in local shop timezone.

## Deployment/quality bar
- TLS for admin and webhook; secrets exclusively in environment/secret manager.
- Authorization policies for admin and order-owner access; validate image MIME/size and private receipt download permissions.
- Automated tests: shipping tier boundaries, checkout snapshots, duplicate webhook, receipt reject/retry/approve, status transitions and ownership.
- CI: formatting/lint, tests and migration checks on pull requests.
- Scheduled backups (DB + private media) and retention policy established before launch.

## Decision notes
- The exact framework minor version is pinned during scaffold, then committed in `composer.lock`.
- No consumer web storefront or Mini App is to be created as part of MVP.
- Detailed schema: [Database Design](03-database-design.md). Product scope: [MVP](01-mvp-scope.md).
