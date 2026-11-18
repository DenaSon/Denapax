# Denapax — product image normalization and square gallery fit

**Date:** 2026-11-18. **Scope:** public product galleries, variant images, catalog cards and admin catalog previews. This changes neither product pricing nor receipt/payment storage.

## Root cause and design

Product originals have different aspect ratios and sometimes large white/transparent margins embedded in the file. The existing square gallery uses \`object-fit: contain\`, so the original image and the gallery background can appear as mismatched rectangular bands.

- The **original is preserved** in the Spatie Media Library on the existing public disk, including the full-resolution URL used in schema/OG metadata. This is reversible and avoids permanent destructive cropping.
- Product \`gallery\` and variant \`variant-image\` collections generate **product-square** (960 × 960) and **product-thumb** (192 × 192) WebP conversions. The Spatie image \`Fit::FillMax\` operation creates an actual square ivory canvas (\`#f8f7f1\`) without cropping the photograph. This color is shared by the gallery stage and thumbnail background.
- For images with **clearly white or transparent corners**, the optional \`ProductImageNormalizer\` scans a downsampled version and calculates a conservative crop of the outer whitespace. It leaves a ~5.5% content margin. Crop geometry is stored as **per-media Spatie manipulations**, used only for the two conversions, not the original.
- Colored/lifestyle backgrounds, ambiguous corners, very large or EXIF-rotated photos and all-white images are deliberately **not auto-trimmed**. They still receive the square fit. Complex photography may need a manually cropped source in the admin editor.
- The product page serves \`product-square\` for its stage and \`product-thumb\` for its thumbnail rail. Homepage cards and admin saved previews also prefer a generated derivative. **Unconverted legacy records fall back to the original URL**, not a broken image.
- Variant selection still maps a variant to its exact media **ID**, regardless of the conversion URL. Gallery swipe, keyboard access, native navigation, alt text and server prices are unchanged.

## Normalize existing images on the deployed persistent disk

**A merge/deploy alone does not regenerate pre-existing media.** Run these commands **inside the deployed Laravel container whose public disk points at the real persistent media volume**, with the same PHP extensions and filesystem permissions as the application.

\`\`\`bash
cd /var/www
php artisan denapax:products:normalize-images --dry-run
php artisan denapax:products:normalize-images
\`\`\`

- The command targets *only* \`Product/gallery\` and \`ProductVariant/variant-image\`. It cannot touch private manual payment receipts.
- It preserves originals, writes conservative crop metadata where applicable, regenerates the two public derivatives and reports every failure with a nonzero exit code.
- For a small pilot: \`php artisan denapax:products:normalize-images --id=123\` (replace 123 with an actual product media record ID).
- For large catalogs, process batches off-peak and resume by ID:
  \`php artisan denapax:products:normalize-images --limit=50 --after-id=100\`
  (process up to 50 records with media ID greater than 100; continue with the last successfully processed ID).
- The command is safe to rerun. Do not install it in every replica's startup hook; duplicate concurrent regeneration would waste resources and can collide on conversion files.
- If the public volume is not writable or a media original is missing, **fix the volume/permissions or restore the original first**, then retry. Do not use \`chmod 777\` or copy private receipts to the public disk.
- Once regeneration is complete, open a product with several photos, including white-background and natural photos, and verify the gallery, thumbnail rail and variant-photo switching. Browser caches may require a refresh.

## Tests and acceptance

- \`ProductImageNormalizationTest\` exercises conservative whitespace detection, source immutability, actual 960/192 square WebP conversion output, variant mapping, customer-facing URLs, and dry-run/legacy backfill.
- CI must pass PHPUnit, Composer validation/audit, Pint and Vite before merge, and pass again on the merged \`master\`.
- **Not production acceptance:** physical iOS/Android testing, slow-connection performance and the actual deployed backfill still require operator validation.

No schema migration, extra image-storage disk, media-package replacement, private receipt access, or payment change.
