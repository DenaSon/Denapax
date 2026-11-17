#!/bin/sh
# Denapax Runflare postStart hook.
# Usage in the Laravel service (NOT the Python/file-manager service):
#   sh /var/www/scripts/runflare-post-start.sh
#
# Safe on repeated starts: no migrations, no database truncation, no destructive
# storage-link replacement, and no queue-worker process started from the hook.

set -eu
umask 022

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd -P)
APP_DIR=$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd -P)
cd "$APP_DIR"

log() {
    printf '[denapax-post-start] %s\n' "$*"
}

die() {
    log "ERROR: $*" >&2
    exit 1
}

[ -f artisan ] || die "Laravel artisan not found in $APP_DIR; run this hook in the Laravel service."
command -v php >/dev/null 2>&1 || die "PHP CLI is unavailable; the selected service is not the Laravel container."

# Link only the public disk. Never expose storage/app/private or payment receipts.
mkdir -p storage/app/public/landing
if [ -L public/storage ]; then
    link_target=$(readlink -f public/storage || true)
    public_target=$(readlink -f storage/app/public || true)
    [ "$link_target" = "$public_target" ] || die "public/storage points to an unexpected directory; inspect it manually."
    log "Public storage link already points to the correct directory."
elif [ -e public/storage ]; then
    die "public/storage exists but is not a symlink; refusing to overwrite an existing directory."
else
    log "Creating Laravel public storage symlink."
    php artisan storage:link
fi

# Refresh release-local caches after environment/deployment changes. Avoid
# optimize:clear: its cache:clear step may touch a database-backed cache store.
log "Refreshing Laravel application caches."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
php artisan optimize

# Livewire's published JavaScript is a static asset behind reverse proxies.
if [ ! -f public/vendor/livewire/manifest.json ]; then
    log "Publishing Livewire static assets."
    php artisan vendor:publish --tag=livewire:assets --force
fi

# The original Runflare file-manager URL is not a reliable page-time image source.
# Cache a verified WebP on the public disk so the storefront serves it same-origin.
# Keep the pre-existing file on persistent storage when restarting.
STORY_SOURCE=${DENA_STORY_IMAGE_SOURCE_URL:-https://fm-9kyo-denapx.runflare.cloud/data/denapax-kuhgol-1200x900_261117145040.webp}
STORY_TARGET="$APP_DIR/storage/app/public/landing/denapax-kuhgol-1200x900.webp"

is_webp() {
    [ -f "$1" ] && php -r '$image = @getimagesize($argv[1]); exit($image && $image[2] === IMAGETYPE_WEBP ? 0 : 1);' "$1"
}

if is_webp "$STORY_TARGET"; then
    log "Dena story photo already cached on the public disk."
else
    case "$STORY_SOURCE" in
        https://*) ;;
        *) die "DENA_STORY_IMAGE_SOURCE_URL must use HTTPS." ;;
    esac

    if command -v curl >/dev/null 2>&1; then
        temp_file=$(mktemp "$STORY_TARGET.part.XXXXXX")
        if curl --fail --silent --show-error --location \
            --proto '=https' --proto-redir '=https' \
            --connect-timeout 4 --max-time 15 --max-filesize 8000000 \
            --output "$temp_file" "$STORY_SOURCE" && is_webp "$temp_file"; then
            chmod 0644 "$temp_file"
            mv -f "$temp_file" "$STORY_TARGET"
            log "Dena story photo saved to the public disk."
        else
            log "WARNING: Story photo could not be downloaded or was not WebP. The local SVG fallback will be shown."
        fi
        rm -f "$temp_file"
    else
        log "WARNING: curl is unavailable. Upload the WebP into storage/app/public/landing manually; the SVG fallback remains available."
    fi
fi

log "Laravel postStart setup completed."
