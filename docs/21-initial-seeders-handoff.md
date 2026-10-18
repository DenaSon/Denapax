# Initial settings and demo catalog handoff

Issue #47; PR #48. Only seeders and documentation. No Horizon/Redis integration or deployment.

## Safe standard initialization

On a backed-up authorized database run:

    php artisan migrate --force
    php artisan db:seed

DatabaseSeeder calls RolesAndPermissionsSeeder and InitialSettingsSeeder. Missing store settings id=1 receive the brand name «دنا» but **empty** payment-card/cardholder fields and null support contact. Missing shipping settings id=1 receive **placeholders, not tariffs**: base tier 1000 g, additional tier 1000 g, base fee 0 IRR, additional fee 0 IRR, package weight 0 g. Existing store/shipping records are never modified. No staff account or password is created by these seeders.

**Operational requirement:** Before accepting real orders, authorized staff MUST set a real card, cardholder and support contact in /admin/settings and a checked delivery-weight rule with genuine shipping prices in /admin/shipping. Zero-price placeholders must never be treated as confirmed shipping policy. Staff account is created separately with the existing denapax:admin command.

## Exactly three opt-in demos

In local, testing or staging only, explicitly execute:

    php artisan db:seed --class=DemoProductsSeeder

One inactive category (denapax-demo-dry-fruits) and three inactive products with one unavailable 500 g variant each:

| Product | Slug | SKU | Example price (IRR) | Shipping weight |
| --- | --- | --- | ---: | ---: |
| انجیر خشک نمونه | denapax-demo-dried-figs | DENAPAX-DEMO-FIG-500 | 1,950,000 | 550 g |
| مغز گردو نمونه | denapax-demo-walnuts | DENAPAX-DEMO-WALNUT-500 | 2,850,000 | 550 g |
| کشمش نمونه | denapax-demo-raisins | DENAPAX-DEMO-RAISIN-500 | 1,250,000 | 550 g |

All prices are **fictional**, not offers or real sourcing claims. No stock counts, photos, promises of origin or real payment instructions are seeded. Products and category remain disabled and **invisible in Telegram** until authorized staff verify their real specifications and deliberately enable them. This demo seeder is NOT called by standard db:seed; it refuses production. Slugs and SKUs are stable; reruns leave admin-edited records intact and do not create duplicates.

## Current queue choice

**No Horizon and no Redis requirement.** Use the existing database-backed queue (QUEUE_CONNECTION=database). To send transactional Telegram notifications in a properly configured deployment, run BOTH scheduler and supervised database queue worker:

    php artisan queue:work database --queue=notifications,default --tries=1 --timeout=60
    php artisan schedule:work

Ensure the worker restart/retry/supervision and scheduler are proven before real release. The choice not to use Redis/Horizon does not remove the queue/background-process requirement.

## Verification and release boundaries

Focused seed tests cover repeated seeding, preserving manually changed settings, exactly three demo records and not publishing them, plus the production guard. Check PR #48 latest GitHub Actions for CI evidence. Actual seeded MySQL/staging, Telegram Bot API, bank, webhook, notification worker, mobile UI, backups and rollback remain **unverified**, required by Phase 6 issues #11/#24/#25. Draft Phase 6 PR #36 must be reconciled with current master before merge.
