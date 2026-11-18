# Denapax — desktop gallery sizing and admin upload previews

**Date:** 2026-11-18. **Scope:** presentation-only follow-up to the public product gallery and administrator product editor. No schema, payment, pricing, saved-media authorization or upload validation change.

## Desktop product gallery

- At desktop widths (1024px and above), the main square product gallery and thumbnail rail use `width: 80%` with a maximum of 448px. This reduces the former 560px maximum by **20%**, including in narrower desktop columns.
- The gallery and thumbnail rail stay centered within the existing desktop column. Square crop, arrows, thumbnail selection, product-variant image switching and mobile layout are unchanged.
- On mobile/tablet below 1024px, no gallery width override applies.

## Admin editor pending upload previews

- Previously, the pending variant and gallery thumbnails rendered `TemporaryUploadedFile::temporaryUrl()`, producing signed `/livewire-{hash}/preview-file/...` image URLs.
- The reported preview failure may involve reverse proxy, signature validation, or temporary-storage routing; the URL alone does not establish which HTTP error occurs. **The editor no longer requires this signed preview endpoint**: it displays the user's selected image using short-lived browser `URL.createObjectURL(File)` URLs, with `URL.revokeObjectURL()` on remove or component disposal.
- Variant upload remains `wire:model="variantUploads.{index}"`. Pending thumbnails render instantly from the browser file while Livewire still uploads/validates/stores the selected file. Errors clear the local preview.
- Gallery uses the existing sequential `$wire.uploadMultiple('galleryUploads', [file], callback)` upload strategy and now retains previews across repeated file selections. Its callback records the returned temporary server filename so removing a pending thumbnail removes the matching Livewire upload as well.
- Gallery's server-rendered pending-file fallback displays the filename (not a signed preview URL) when a local object URL is unavailable after component hydration. Previously saved Spatie product/variant media still load from their permanent public media URLs.
- The browser blob previews are not published, persisted or sent as data URLs, and they are limited to JPEG/PNG/WebP types. Existing server-side MIME/size checks remain authoritative.

## Regression and release gates

- PHP feature assertions cover desktop-only 80%/448px CSS, local blob URL creation/revocation, sequential gallery upload controls, and removal of Livewire signed preview URL rendering from the editor.
- Existing Livewire feature tests continue to exercise assigning pending gallery and variant uploads.
- Actual browser QA is still required: multiple gallery files, repeated selections, removal before save, per-variant preview, upload failure, then save and reload. Review network response codes if upload submission (not preview) still fails.
- A multi-replica deployment must ensure the configured Livewire temporary upload disk is accessible to the worker handling save; browser previews do **not** resolve missing temporary files on the server.

No migration or new public preview route is introduced.
