# Runflare postStart hook and the Dena landing-story image

**2026-11-17. Scope:** only the Laravel storefront service. This is a repeatable post-start hook, not an unattended migration or a release-acceptance claim.

## Where to configure it

In **the Laravel service's** Runflare **Service settings → General settings → postStart**, enter a shell command (not an HTTPS URL):

```sh
sh /var/www/scripts/runflare-post-start.sh
```

The script lives in the repository at `scripts/runflare-post-start.sh`, resolves its Laravel root from its own location, and runs after code and Composer dependencies are present. The path above assumes the deployed Laravel application is at `/var/www`, as in the existing Denapax deployment. For a different root, use its actual absolute script path.

**Important:** If the service's *main command* displays `python3 -m http.server 8000`, that is a Python/static file service rather than the Laravel PHP service. Do **not** configure this PHP hook there. Keep the main command and args of the Laravel service unchanged; postStart is a separate setting.

## What the hook does on every start

1. Validates the Laravel root and PHP CLI.
2. Creates **only** `storage/app/public/landing` when missing.
3. Creates `public/storage` using `php artisan storage:link` if absent. It validates an existing symlink and **refuses** to replace an unexpected file/directory or symlink. The link points only to `storage/app/public`, **never** `storage/app/private` or receipt files.
4. Runs `config:clear`, `route:clear`, `view:clear`, `event:clear`, then `php artisan optimize`.
5. Publishes Livewire static assets if `public/vendor/livewire/manifest.json` is missing.
6. Checks for a valid WebP at `storage/app/public/landing/denapax-kuhgol-1200x900.webp`. If one exists, it is **not overwritten**. Otherwise, it attempts an HTTPS download from the original file-manager URL using cURL, a size/time limit and `getimagesize()` validation, and atomically installs the file with public read permissions. If this download fails or the source is unavailable, **it logs a warning but lets the site start**; the CSS falls back to the existing Dena SVG illustration.

No new environment variable is required. The optional `DENA_STORY_IMAGE_SOURCE_URL` can override the original HTTPS source URL. It is a **process environment** value for the hook, not a Laravel config value.

**Not done automatically:** `migrate --force`, seeding, `storage:unlink`, changing any receipt permissions, recursively changing storage ownership, launching queue workers, or rebuilding Vite assets. Migrations and worker/scheduler operations must remain coordinated release tasks. Vite assets must be built as part of the image/build pipeline.

## Why the photo was missing

The earlier landing CSS loaded the story photograph directly from the external `fm-9kyo-denapx.runflare.cloud` file-manager service. The new CSS points to a **same-origin public storage URL**:

```
/storage/landing/denapax-kuhgol-1200x900.webp
```

This uses the existing public-storage symlink and persistent storage. The original image's file-manager URL was not reachable from the development environment, so the initial source transfer is **not verified** until this hook runs on the deployment. The illustration fallback is intentional so the panel is never empty. Do not treat a working fallback as evidence the photo has been downloaded.

## Runflare CLI checks after redeploy

Open a terminal **inside the Laravel container**, not the Python file-manager container:

```sh
cd /var/www
sh scripts/runflare-post-start.sh
ls -ld public/storage
ls -lh storage/app/public/landing/denapax-kuhgol-1200x900.webp
php artisan about
```

If `ls` reports the WebP is missing, inspect postStart logs for a download warning and test access from the Laravel server:

```sh
curl -I 'https://fm-9kyo-denapx.runflare.cloud/data/denapax-kuhgol-1200x900_261117145040.webp'
```

A 403, 404, DNS failure or HTML response cannot be corrected with `storage:link` or `optimize`. In that case restore/publish the original source or upload the actual WebP (not an HTML error page) once to this exact path in the Laravel service's persistent **public** storage:

```
/var/www/storage/app/public/landing/denapax-kuhgol-1200x900.webp
```

If the download succeeds and `public/storage` points correctly, verify from outside the container:

```
https://denapax.ir/storage/landing/denapax-kuhgol-1200x900.webp
```

Replace `denapax.ir` with the actual storefront host if necessary. It should return HTTP 200 and an image. Reload the page with refreshed assets after the new CSS build is deployed. The photo uses `background-size: cover` without changing the existing card proportions. If the browser returns 404 for the same-origin URL despite the file existing, inspect the Runflare web server's static symlink handling or configured document root; do not open private storage.

## Persistence caution

This hook is **not a backup**. The public image, uploaded products, and **private receipts** require appropriate persistent volumes. A public WebP cached under `storage/app/public` is expected to survive redeployments only when that path is persistent. Never mount or symlink the private receipts directory into a public path.
