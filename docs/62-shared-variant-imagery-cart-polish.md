# Shared product/variant imagery and cart legibility

## Image selection (no media duplication)
The same `ProductImageResolver` is used by catalog cards, cart lines, product variant gallery selection, and saved variant previews in admin:

1. Variant's own `variant-image`.
2. The parent product's first `gallery` photo.
3. The first media-bearing sibling variant of that **same parent product** (deterministic `sort_order`, then ID).
4. Accessible empty-image placeholder when none exists.

The resolver returns the original Spatie Media record without copying or attaching it to another variant. Updating/removing that media changes what dependent variants inherit on the next render.

### Efficiency
- Public catalog list eager-loads product `media` and `variants.media`.
- Cart lines eager-load `product.variants.media`, `product.media`, and selected `variant.media`.
- Product detail already eager-loads gallery and sellable variant media. On radio selection of a variant with no dedicated image it navigates to the resolved gallery or sibling image instead of silently leaving the previous variant image.
- Admin editor already loads `Product::with('media','variants.media')`. Shared previews are explicitly labeled; dedicated uploads remain optional.
- Media URLs use existing nonqueued square/thumb WebP conversions when available and otherwise the original. No schema or migration changes.

## Cart UX
- Desktop item rows are moderately tighter, with smaller thumbnail padding and less whitespace.
- A pill emphasizes the authoritative `sale_quantity` + `sale_unit`, distinct from any descriptive variant title. Quantity controls, native HTML form fallback and reactive Livewire actions are unchanged.
- Summary delivery note is shorter and still tells customers the final total is revalidated when ordering.

## Acceptance
- Variant-specific photos remain first priority, product gallery is second, sibling-only photos render when gallery is absent, and no-media state remains accessible.
- All image selections remain scoped to the original product; product-page image switching maps missing-variant photos to the fallback image.
- Validate cart desktop/tablet/mobile breakpoints and admin previews on a real device after deploy, including order totals, +/- and mobile sticky dock.
