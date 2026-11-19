# Denapax — final mobile cart polish before launch

**Scope:** cosmetic and content UX for the customer cart. No shipping, checkout, payment, session, database or order mutation. This is not production-device acceptance.

## Implemented

- On mobile (700px and below), item panel appears before summary panel. Desktop remains a two-column layout. Mobile row padding and the summary panel are more compact; duplicate subtotal block and decorative trust footer removed.
- This polish originally displayed only the merchandise subtotal in the sticky dock. It is superseded by the weight-based cart estimate in [docs/60-cart-shipping-estimate.md](60-cart-shipping-estimate.md), which displays the current combined total where calculable.
- Guest checkout link reads «ورود و ادامه خرید»; signed-in customer link reads «ادامه به نشانی و ارسال». Both navigate to the protected checkout route (which sends guests to OTP login, preserving intended destination), not directly to order confirmation.
- Exact variant image has priority. Product gallery image is fallback. Prefer generated `product-thumb`, then `product-square`, then original image when conversions do not exist. If the database has no suitable media, show «بدون تصویر» rather than an ambiguous mountain mark.
- Existing Livewire no-refresh plus/minus/remove, quantity limits and unavailable-product blocks are unchanged; native forms and server authoritative validation remain.

## Owner verification after deployment

1. On an actual iPhone Safari and Android Chrome, view the cart at 320–430px, verify item-before-summary, legible large prices and no layout jump or excess whitespace.
2. Confirm fixed dock does not cover interactive elements at the bottom on devices with safe-area insets and browser chrome; verify it updates after Livewire changes.
3. With a guest cart, follow «ورود و ادامه خرید» through OTP and confirm the cart persists and the destination is checkout. With a signed-in account confirm «ادامه به نشانی و ارسال» opens address selection.
4. Test +, −, remove and unavailable product state in native and Livewire paths. Checkout must remain disabled if a cart item is no longer available.
5. For items with variant images, confirm the selected variant image is shown. For products without images, the explicit placeholder is expected: **upload real photographs in admin before public launch**. A code change cannot create catalog photos.
6. The screenshot that prompted this pass displayed «هلو خشک زعفرونی» without photo. Verify media records and persistent public storage for this live product; do not mark this step completed based only on CI.

## Automated coverage

Focused feature regression covers guest/signed-in CTA text, protected checkout redirect, mobile item/summary ordering and photo priority/fallback. CI tests, Pint and frontend build do not replace visual mobile QA.
