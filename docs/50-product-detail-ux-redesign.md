# Denapax — product detail UX redesign

**Date:** 2026-11-18. **Scope:** public product detail page. This is a presentation-layer redesign; no schema, pricing, stock, checkout, payment, or authorization behavior is changed.

## Customer experience

- Desktop uses a deliberate LTR grid wrapper with an RTL gallery on the left and RTL product information on the right. This makes column placement explicit instead of depending on implicit RTL grid placement.
- The product title and short summary lead the information column. Long descriptions and feature lists move below the purchase controls, reducing the time required to reach a buying decision.
- The duplicate "price from" panel is removed. Each variant card clearly shows its own package price, while one live total shows the selected variant multiplied by selected quantity in toman.
- Quantity selection has compact +/- controls around a still-editable native number input. The form remains a CSRF-protected POST to the existing cart endpoint when JavaScript is unavailable.
- Desktop purchase controls are sticky within the product information column. The mobile fixed purchase dock remains in place, now showing the selected variant and quantity alongside the live total.
- The existing Alpine/Livewire event flow, cart reconciliation, error handling, variant-specific gallery image selection, gallery gestures, wire:navigate, SEO metadata and structured-data offers remain intact.

## Implementation map

- resources/views/landing/product.blade.php — compact product-first hierarchy, package cards, quantity stepper, lower product details and selected-variant dock context.
- resources/css/landing.css — responsive two-column desktop layout, sticky desktop purchase panel, compact variant cards and mobile dock refinements.
- resources/js/product-purchase.js — selected variant/quantity labels and bounded quantity adjustment; existing server-authoritative cart events remain unchanged.
- app/Http/Controllers/PublicProductController.php — remove the now-unused minimum-price view value; product and variant query behavior is unchanged.
- tests/Feature/PublicLandingTest.php — regression coverage for the new hierarchy, live total, selected variant context, quantity controls and preserved native/Livewire cart paths.

## QA and release gates

- [x] Feature test asserts the purchase-first hierarchy and absence of the duplicate "price from" block.
- [x] Existing tests continue to cover guest/customer cart state, native POST fallback, sellable variants and variant-photo mapping.
- [x] PR CI passed: 151 tests / 1,232 assertions, Composer audit/validation, Pint and Vite build (run #44300740697).
- [x] The same checks passed on master after merge (run #44301133481; 151 tests / 1,232 assertions).
- [ ] Inspect desktop at 1024px, 1280px and wide desktop; confirm sticky purchase controls stop naturally at the end of the product information column.
- [ ] Inspect mobile at 320/360/390/430px; confirm the dock remains readable, safe-area aware and does not obscure form controls.
- [ ] Verify keyboard navigation, 200% zoom, reduced motion, long Persian titles and a product with many variants.
- [ ] Complete actual iOS Safari and Android Chrome acceptance; CI does not establish device/browser usability.

No production deployment or live-device acceptance is implied by this code change.
