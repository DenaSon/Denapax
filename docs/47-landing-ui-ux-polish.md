# DenaPax — customer storefront and landing polish

**Date:** 2026-11-16. **Scope:** three small PRs [#92](https://github.com/DenaSon/Denapax/pull/92), [#93](https://github.com/DenaSon/Denapax/pull/93) and [#94](https://github.com/DenaSon/Denapax/pull/94). These code changes are not proof of Runflare deployment or acceptance on real mobile browsers.

## Goals and decisions

1. **Unambiguous navigation:** the landing's editorial rail corresponds to exactly three sections (products, Dena story, order), separate from the three **purchase** stages (cart, address/shipping, payment). The hero is an introduction rather than a numbered chapter. The mobile menu uses correct labels and a real cart link.
2. **Interaction discoverability:** active button, select and anchor controls use pointer cursor on precise-pointer desktop devices; disabled controls remain not-allowed and all existing keyboard-focus styles remain intact.
3. **Premium, nature-led hierarchy:** retain forest green / warm cream / muted stone, Vazirmatn, the centered "از دل دنا" hero and real product photos. Add a factual assurance strip, uniform product cards with clear prices, a photographic Dena story and an explicit order CTA. Don't imply that the first DB result is manually curated.
4. **Shorter purchasing path:** the JS-enabled landing product preview now submits the selected public variant ID and quantity to the existing CSRF-protected `cart.add` POST. The server continues checking product publication, variant availability, price and quantity. The original product permalink and native full product page remain usable without JavaScript.
5. **Cart visibility:** the header shows total cart **units** (not distinct product lines) for both session guests and signed-in customers; reading the count never creates a persistent empty cart. The mobile landing has a direct cart icon.

## Operation and smoke-test checklist

- Deployment must build the latest Vite assets and include the [published Livewire v4 JS](46-runflare-https-livewire-forms.md). No DB migrations or provider changes belong to this polish.
- Verify Persian 320/375/390/430px mobile, tablet and desktop widths; no horizontal scrolling, readable cards and dialogs, correct CTA labels, tab/Shift+Tab/Enter/Escape, and browser back/forward after adding a variant.
- With JS enabled, open landing preview, select **the second** variant, change quantity, add to cart and verify the exact variant and unit count. With JS disabled, click a product card: the normal product page must still allow adding to cart.
- Validate guest cart count, OTP login guest-cart merge, signed-in count across cart/checkout/order pages, and that a fresh logged-in visitor does not gain an empty cart record merely by opening the homepage.
- Confirm category filtering and pagination continue to work with and without Alpine, and previews refresh after filtering.
- Test `prefers-reduced-motion`, 200% browser zoom, contrast, and real touch targets. Restore intended shop data after operator tests.
- The Dena hero and story still use the existing **attributed Wikimedia image** with local SVG fallback. An optimized, properly licensed and self-hosted editorial photograph is an **optional future asset task**, not yet integrated; the footer must retain attribution while this photo is used.
- Staff/finance permissions, customer OTP, manual receipt/private-media handling, and Zarinpal environment switch are unchanged.

## Verifiable checks

GitHub CI covers PHPUnit feature tests, PHP style and Composer audit/validation, Vite compilation. CI cannot replace browser visual comparison or performance checks on Runflare. Do not claim visual sign-off without operator screenshots. Regression tests live in `tests/Feature/PublicLandingTest.php`.
