# Denapax 🌰

Telegram-first store for Dena souvenirs and dried fruits.

## Project status
**Phase:** Product and technical design (MVP v1.1). Laravel application is **not scaffolded yet**.

## MVP channels
- Customer-facing: Telegram Bot (messages, inline buttons, search, cart, order tracking).
- Operations: Laravel + Livewire + Mary UI web admin.
- Backend: Laravel Modular Monolith; MySQL; Redis-backed queues as needed.

## Documentation
- [MVP Scope v1.1](docs/01-mvp-scope.md)
- [Architecture and boundaries](docs/02-architecture.md)
- [Database ERD and Data Dictionary](docs/03-database-design.md)
- [Order, payment, and notification flows](docs/04-workflows.md)

## Key decisions
- Product variants have predefined sale quantities, units and independent prices.
- Shipping weight is stored in **grams** on every variant, independent from its sale unit.
- Shipping is a nationwide formula: base tier + increments above the base tier; settings are admin-configurable.
- Payment is card-to-card with customer receipt upload and **manual admin approval**.
- A customer may re-upload a receipt after rejection; all attempts are retained.
- Order/payment status changes trigger transactional Telegram notifications.
- Order lines, address and shipping fee are snapshotted at checkout.
- Inventory is available/unavailable only in the MVP; no numeric stock tracking.

> **Security:** Do not commit `.env`, bot tokens, cardholder secrets, customer PII, or payment receipt images.

For an authoritative description of decisions and acceptance criteria, see the documentation links above.
