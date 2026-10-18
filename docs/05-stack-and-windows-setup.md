# Denapax — Stack, Packages & Windows/PowerShell Bootstrap v1.0

**Status:** Approved technical choices; install only after Laravel scaffold exists. **Date:** 2026-10-18.

## Baseline
- **Windows + PowerShell** development; PHP **8.4** (x64) target, Composer 2, Node.js LTS, npm, Git.
- **Laravel 13.x**, one app / modular monolith; **Livewire 4.x**, **maryUI 2.x**, Tailwind CSS 4 and daisyUI 5 (maryUI installer configures UI assets; inspect the generated files).
- MySQL 8+ (prefer a current supported version); Redis for queues + notifications. Docker Desktop for service containers is optional.
- Auth for **staff web users** is distinct from Telegram customer identity; there is no customer email/password signup in the MVP.

## Composer dependency baseline
| When | Composer package | Reason |
| --- | --- | --- |
| Foundation | `nutgram/laravel:^1.7` | Telegram message/callback/media integration. Includes `nutgram/nutgram` 4.x; do **not** require the base library twice. |
| Foundation | `livewire/livewire:^4.0` | Admin interactions |
| Foundation | `robsontenorio/mary:^2.0` | Mary UI Blade components, Tailwind/daisyUI-based admin |
| Foundation | `spatie/laravel-permission:^8.0` | Roles, permissions, policies; guards restricted to admin web users |
| Foundation | `spatie/laravel-medialibrary:^11.0` | Product galleries and optional single variant image; uses package-owned `media` table |
| Foundation | `spatie/laravel-activitylog:^5.0` | Admin audit trail in package-owned `activity_log` table; **PHP 8.4+** |
| Foundation | `laravel/fortify` | Headless admin authentication backend, login rate limiting; **public registration disabled** |
| Dev | `larastan/larastan:^3.0` | PHPStan/Laravel static analysis |
| Dev | `pestphp/pest:^5.0` and `pestphp/pest-plugin-laravel:^5.0` | Feature/unit tests; **PHP 8.4+**, Laravel 13 **>=13.23** for the plugin |
| Dev | `laravel/pint` | Formatting (may already be in Laravel skeleton; do not install twice) |
| Before production | `laravel/horizon` | Redis queue monitoring/dashboard (requires working Redis queues) |

**Resolver note:** Do not run all Composer installs blindly before verifying `php -v` and `composer.json`. The Laravel skeleton may constrain `phpunit/phpunit` to ^12 while Pest 5 needs ^13. If Composer flags that constraint, update the dev test dependencies together with `-W` (including `phpunit/phpunit:^13`) after inspecting `composer why-not`. Do not delete `composer.lock` or use `--ignore-platform-reqs`.

**Mary UI note:** Avoid Laravel starter kits that introduce another competing UI design system. Use plain Laravel + Fortify + Livewire/Mary. Run `php artisan mary:install` *after* installing the package, then inspect package-managed edits to CSS/JS. Check Livewire component support with a smoke test.

## RBAC seed for MVP
`User` is an administrator/staff identity (web guard), not a Telegram customer.

Roles:
- `super-admin`: full admin actions.
- `operator`: manage products and order fulfillment, view receipts; permission to **approve/reject receipts is explicitly assigned**, not inferred from role name.

Suggested permission slugs: `catalog.manage`, `orders.view`, `orders.update`, `payments.review`, `shipping.manage`, `settings.manage`, `staff.manage`. Seed role/permission records and an initial admin via controlled deploy command; never commit default credentials. Enforce server-side policies and gates on every Livewire action and receipt download.

Use **Spatie Permission's published tables** (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`), not custom equivalents.

## Media and audit conventions
- **Public product images:** Spatie Media Library morph `media` records on `Product` (collection `gallery`) and `ProductVariant` (collection `variant-image`, singleFile). Product image sort order uses the package `order_column`.
- **Private payment receipts:** keep authoritative `payment_receipts` records with `storage_path` in a **private** filesystem; downloads must pass admin authorization and should not use a public disk/link. The package is not required for receipts.
- **Activity audit:** package `activity_log` for staff operations; separate domain `order_status_histories` for business transition facts. Avoid a duplicate custom `admin_audit_logs` table. Do not log tokens, full addresses, full card numbers or receipt URLs.
- Migrations published by the packages are **package-owned** and must be reviewed before running.

## PowerShell — clean scaffold into existing Git repository
Start from a location where the `Denapax` directory does **not** yet exist; this does not overwrite the approved repository docs.

```powershell
$ErrorActionPreference = 'Stop'
$workspace = Join-Path $HOME 'Projects'
New-Item -ItemType Directory -Force -Path $workspace | Out-Null
Set-Location $workspace

# Confirm prerequisites first:
php -v
composer --version
node -v
npm -v
git --version

git clone https://github.com/DenaSon/Denapax.git
composer create-project laravel/laravel Denapax-scaffold "^13.0"

# Copy the framework files into the existing Git working tree.
# Keep repo README/docs/.git; do not copy the scaffold's local .env.
Get-ChildItem -LiteralPath .\Denapax-scaffold -Force |
  Where-Object { $_.Name -notin @('.git', '.env', 'README.md') } |
  Copy-Item -Destination .\Denapax -Recurse -Force

Set-Location .\Denapax
Copy-Item .env.example .env
php artisan key:generate
composer validate
php artisan --version
git status --short
```

Do **not** run the database migrations yet; package migration choices and `.env` database settings are checked after package installation. The `Denapax-scaffold` temp directory can be deleted after verification.

### Follow-up package installation (after scaffold/version check)
```powershell
composer require "nutgram/laravel:^1.7" "livewire/livewire:^4.0" "robsontenorio/mary:^2.0" "spatie/laravel-permission:^8.0" "spatie/laravel-medialibrary:^11.0" "spatie/laravel-activitylog:^5.0" laravel/fortify

# Test tools: inspect the Laravel skeleton's PHPUnit constraint first.
composer require --dev "larastan/larastan:^3.0"
composer show phpunit/phpunit
# Install Pest 5 and its plugin with suitable PHPUnit 13 in the same update:
composer require --dev "pestphp/pest:^5.0" "pestphp/pest-plugin-laravel:^5.0" "phpunit/phpunit:^13.0" -W

php artisan mary:install
# Install/publish other package resources after reviewing their configs/migrations.
npm install
npm run build
composer audit
git status --short
```

Composer can still report platform-specific extension/config conflicts: solve those based on the actual diagnostic. In particular check `ext-fileinfo`, `ext-exif`, PHP CLI version and enabled extensions. If `pestphp/pest-plugin-laravel:^5.0` requires a newer 13.x patch, upgrade Laravel within ^13 before retrying.

### First scaffold commit/push (after smoke tests)
```powershell
php artisan test
git diff --check
git add .
git status --short
git commit -m "chore: scaffold Laravel 13 application"
git push origin master
```

`git add .` is safe only after checking that `.env`, `vendor/`, `node_modules/`, log files, receipts and secrets are excluded in `.gitignore`. **Do not commit production card-payment or bot configuration in `.env`.**

## Install/publish later rather than by default
- `laravel/horizon` when Redis connections and worker supervision are configured, before production launch.
- `spatie/laravel-settings` **not needed**: `shipping_settings` and `store_settings` remain explicit data tables for clarity and snapshots.
- `sanctum`, `passport`, `octane`, alternate admin kits, ecommerce/cart packages, complex modular-loader packages: **not in MVP**.

## References (official)
- Laravel versions: https://laravel.com/docs/13.x/releases
- Nutgram Laravel: https://nutgram.dev/docs/configuration/laravel/
- Mary UI: https://mary-ui.com/docs/installation
- Spatie Permission: https://spatie.be/docs/laravel-permission/v8/prerequisites
- Media Library: https://spatie.be/docs/laravel-medialibrary/v11/introduction
- Spatie Activitylog: https://spatie.be/docs/laravel-activitylog/v5/introduction
- Fortify: https://laravel.com/docs/13.x/fortify
- Pest 5: https://pestphp.com/docs/upgrade-guide
