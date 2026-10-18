# Phase 1 — Admin & Catalog: implementation handoff

**Branch:** `phase/1-admin-catalog` (merge only after green CI).  
**Issues:** #2, #3, #12, #27 · **Execution policy:** [Agreement](09-execution-agreement.md).

## Implemented
- Fortify staff login screen, throttling, inactive staff block and no public staff registration.
- `/admin` and permission-protected categories, products/edit, and settings routes; interactive staff creation stays via `php artisan denapax:admin`.
- Categories, products (soft-delete), product-level features and independently priced variants with integer IRR and gram shipping weights; Eloquent relationships.
- Spatie public catalog `gallery` and `variant-image` collections; protected write operations via authenticated Livewire.
- Persian RTL administration pages using Mary UI / Tailwind / daisyUI.
- Store name, support contact and card-display settings, with least-privilege settings permission and non-sensitive Activitylog audit.
- Compact critical feature tests for staff login/access, catalog variants and card settings.

## Environment integration — production controls
These migrations create catalog/settings tables and add `is_active` to `users`. **The GitHub PR/CI does not migrate your actual MySQL environment.**

```powershell
# On a development server after phase-1 merge:
composer install
npm install
npm run build
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan denapax:admin your-real-email@example.com --name="Store Owner"
php artisan storage:link
php artisan route:list
```

Do not put the real admin password, card information, bot token or `.env` into Git. The `denapax:admin` command prompts for the password. Use PHP 8.4 and Laravel 13 with installed fileinfo, exif and gd extensions. Configure MySQL in local/production secrets and set `APP_DEBUG=false`, HTTPS and real `APP_URL` for production. `public` catalog images require the storage link; payment receipts will always use a separate private storage disk in later phases.

For local development only, it is safe to run the baseline test suite on ephemeral SQLite using `php artisan test`. Real E2E Telegram-to-payment-to-delivery validation is scheduled for phase 6, not claimed for this phase.

## Known phase boundary
- No Telegram webhook, customer/cart/order, receipt, payment processing or shipping settings persistency in phase 1.
- No public staff registration or visual RBAC administration UI.
- Cart price rechecks and immutable financial snapshots belong to phase 3.
- Live DB migration/production access must be verified separately; CI uses SQLite.
