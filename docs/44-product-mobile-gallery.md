# Denapax — Mobile-first product image gallery

**Date:** 2026-11-15. **Scope:** public `/products/{slug}` photo experience. Implementation is in the product gallery PR associated with this document; production/iOS/Android device acceptance remains pending.

## Customer experience

- **Main image:** the first uploaded product gallery photo is the cover. The image is contained within a calm neutral square frame, not arbitrarily cropped, with a clear zoom affordance and accessible descriptive alt text.
- **Carousel:** previous/next arrow buttons, image index and a horizontally scrollable strip of selectable thumbnails. A selected thumbnail changes the main image without navigation; thumbnails remain accessible keyboard-focusable anchors. The page works in Persian RTL and at narrow widths.
- **Mobile swipe:** horizontal swipe (45px threshold, with vertical intent rejection) advances/rewinds the main image. Vertical page scrolling remains native. Thumbnail row has horizontal scroll and scroll-snap.
- **Full-screen viewing:** native `<dialog>`, image fitted to viewport, close button, Escape, left/right arrow keys, previous/next controls, zoom +/-/reset, double-click/double-tap zoom, two-finger pinch zoom and one-finger pan when enlarged. Body scroll is restored when the dialog closes and focus returns to the original main image link.
- **Variant-specific photos:** selecting an available variant radio automatically selects that variant's own photo when it exists. A variant with no photo leaves the current main image alone. The server supplies the exact variant-ID → gallery-index mapping; no client-side product IDs are trusted for price or stock.
- **Without JavaScript:** the first product image remains visible, the main image and thumbnails link to their full original URLs, the form remains a normal POST and products without images show the existing Dena-themed placeholder. Native product data, SEO/OpenGraph image and IRR structured-data prices are preserved.

## Image sourcing and admin workflow

Media uses existing Spatie Media Library. The admin product editor can upload multiple images into the product's `gallery` collection and one `variant-image` per product variant. No new database migration or image storage disk is required.

The controller takes the first **8 general gallery photos** followed by **every publicly available variant photo**, deduplicating identical media URLs. This guarantees a sellable variant image does not disappear simply because the main gallery contains eight photos. HTML thumbnails load lazily and the main image is prioritized; full-size files are used in the modal. Do not display inaccessible private payment receipt media here.

**Product manager recommendation:** upload a clean front/packaging cover first, then details/texture/scale photos, and a separate shot per packaging variant only when it materially differs. Accept JPEG/PNG/WebP; the existing editor validation limits a single upload to 4 MB. If source files are large, consider responsive thumbnail conversions as a later performance optimization rather than adding a large slider dependency now.

## Implementation map

- `app/Http/Controllers/PublicProductController.php` — image list and variant image indexes.
- `resources/views/landing/product.blade.php` — SEO-safe server markup, picture links, carousel controls, native dialog and variant hooks.
- `resources/js/product-gallery.js` — small dependency-free progressive enhancement, touch gestures, modal/focus and zoom.
- `resources/css/landing.css` — gallery-specific mobile/desktop styles; Tailwind 4 `@source` includes the product module.
- `vite.config.js` — dedicated gallery entry for the product permalink only.
- `tests/Feature/PublicLandingTest.php` — no-image fallback, one-image progressive fallback, multi-image render, variant photo mapping and bundler sources.

## QA and release gates

CI must pass PHPUnit, Composer audit, Pint and Vite build **on the PR and then on master**. It verifies server markup and bundle compilation but not touch ergonomics.

- [ ] iOS Safari and Android Chrome 320/375/390/430px: horizontal swipe navigates images; vertical scroll is not accidentally intercepted; thumbnails are reachable/scrollable and click correctly.
- [ ] Real pinch zoom, drag after zoom, zoom reset, close/reopen, Back/Forward return, viewport change and rotation; preserve native focus and keyboard accessibility.
- [ ] Test product with **no photo, one photo, many photos**; repeat with variants having their own photos or no photos. Cover and currently selected item must always match expected images.
- [ ] Check 200% browser zoom, native keyboard/assistive tech, reduced-motion preference, long Farsi names, font/loading and dark-background lightbox contrast.
- [ ] Verify performance and image sizes on a slow mobile connection; ensure no accidental download of every full-size image in initial load.
- [ ] Record sanitized device screenshots/results under release QA [#24](https://github.com/DenaSon/Denapax/issues/24). Browser/Production acceptance remains **pending**; do not confuse a merged UI PR with public launch.

No changes to shipping, OTP, payment, checkout or order authorization. Continue [Phase 4C/4D readiness](38-web-phase4-release-readiness.md) and [current web delivery status](39-web-roadmap-status.md).

## Update — 2026-11-18: non-destructive normalized square images

[PR #156](https://github.com/DenaSon/Denapax/pull/156) supersedes the earlier recommendation to defer image conversions. The original photo remains intact; product gallery, thumbnails, catalog cards and admin preview use 960px / 192px square WebP media conversions when generated, with source fallback. A conservative optional white-margin crop is applied only to derivatives; natural backgrounds are not auto-cropped. Image selection is keyed to media ID. Production's existing images require an explicit operator backfill (not automatic on deploy). Follow the [normalization runbook](53-product-gallery-image-normalization.md).
