# Denapax — show weight-based shipping in cart before checkout

**Scope:** shipping visibility in guest/customer carts, desktop summary and mobile sticky dock. No changes to shipping tariffs, checkout fingerprint/quote, order confirmation, payment, database schema or env.

## Behavior

- `EstimateCartShipping` uses current sellable cart lines and `ShippingSetting::find(1)`, and calls the **same** `ShippingCalculator` and `ShippingRate` as `PrepareCheckout`. Item weight is variant shipping weight times quantity; the calculator adds packaging weight and uses the active tiered tariff. IRR amounts are formatted as toman.
- Since the **current** tariff is nationwide and independent of the destination, guests see shipping and a current combined estimate before OTP login or entering an address. The server still recalculates the authoritative quote in checkout and validates the fingerprint at order confirmation.
- Desktop shows merchandise subtotal, shipping, and combined estimate. Mobile shows shipping in its card and the combined total plus shipping in the existing safe-area-aware dock; avoid duplicated totals.
- Changes in quantity trigger a fresh Livewire render and server-side estimate; native cart fallbacks also update after redirect.
- Empty carts show no estimate. Unsellable products, invalid quantities/weights, and missing or invalid shipping settings suppress the combined estimate; never silently treat shipping as zero.

## Coverage and operator QA

- Feature tests cover guest rate thresholds including packaging weight, Livewire quantity changes, missing tariff/unavailable lines, and parity with authenticated checkout.
- On production iOS Safari and Android Chrome (320–430px), verify the dock fits with safe-area/browser chrome, prices do not wrap awkwardly, CTA remains accessible, and plus/minus/remove keeps prices in sync.
- Verify real production `shipping_settings` ID 1 exists and sellable variants have positive `shipping_weight_grams`. Compare cart estimate and checkout total with unchanged tariff.
- If tariffs later depend on province/city, revise the cart estimate rather than showing an inaccurate address-free total.

GitHub CI passing does not certify real-device or production checkout.
