# Denapax — Foundation implementation handoff

**Branch:** `feat/foundation-shipping-rbac`  
**Status:** Draft code; requires package installation commit and local verification before merging.

## Implemented on this branch
- `App\Domain\Shipping\ShippingRate`: validated, immutable nationwide price/weight settings (IRR and grams).
- `App\Domain\Shipping\ShippingCalculator`: inclusive base tier plus ceil of additional tiers, including shared packaging grams.
- `App\Domain\Shipping\ShippingQuote`: immutable quote result; to be captured in the order snapshot when checkout is implemented.
- `tests/Unit/Domain/ShippingCalculatorTest.php`: tier boundaries, packaging, invalid inputs.
- `App\Models\User`: Spatie `HasRoles` for staff (Telegram customers remain a separate model).
- `RolesAndPermissionsSeeder`: idempotently sets up `super-admin` and `operator`, with explicit permissions. **Operator cannot approve receipts or manage settings/staff by default.**
- `denapax:admin` artisan command: creates a unique admin account interactively (secret prompt, no default hardcoded password).
- `DatabaseSeeder`: removes the insecure scaffold demo user and seeds role definitions.

## Important merge dependency
The `master` branch currently may not contain the locally installed Composer dependencies. Before merging this PR, push the user's local Composer changes (Nutgram, Livewire, Mary UI, Spatie and Fortify). Otherwise `Spatie\Permission\Traits\HasRoles` will not autoload.

## Windows / PowerShell — after merging and publishing vendor migrations

1. Install package configuration and migration stubs locally, based on [package setup guide](05-stack-and-windows-setup.md):

```powershell
php artisan mary:install
php artisan fortify:install
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan vendor:publish --provider="Nutgram\Laravel\NutgramServiceProvider" --tag="nutgram"
```

2. **Before migrations:** review generated files. Disable `Features::registration()` in `config/fortify.php` (public staff sign-up must not exist). Do not publish a production admin panel until login view, authorization policies, and HTTPS are set up. Confirm `User` keeps `HasRoles` after publishing.
3. Set up a development database (`DB_CONNECTION=mysql` and correct settings in local **untracked** `.env`; SQLite for isolated unit tests is also fine). Do not commit .env or actual bot token.
4. Review Spatie migrations (do not add a duplicate `media`, `roles` or `activity_log` table); then run `php artisan migrate`.
5. Seed access control and provision a real admin:

```powershell
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan denapax:admin admin@your-domain.example --name="Store Owner"
```

Replace the example email with the real admin email. The command prompts for a private password. The user is deliberately not created automatically.

6. Check calculations and static syntax:

```powershell
php artisan test --filter=ShippingCalculatorTest
php artisan test
php artisan route:list
```

## Not implemented in this PR
- Fortify login view and admin dashboard; server-side authorization must be applied to admin actions before launch.
- Vendor package migration publication and MySQL migrations; do these only after actual package files have been installed and inspected.
- Nutgram webhook, customer model, checkout/order creation and telegram notifications.
- Price/weight settings persisted in database; shipping rate currently accepts validated in-memory inputs.
- Running tests against the user's local MySQL or composer vendor directory.

## Architectural invariants
- Weight is integer grams; fees are integer IRR; **zero numeric inventory logic** in MVP.
- Prices, weights, address and shipping quote are snapshotted at checkout.
- Card-to-card receipts are private and payment success requires manual staff approval.
- Keep bot adapters thin; domain shipping logic does not depend on Telegram or Livewire.
