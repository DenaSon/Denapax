# Denapax — product detail follow-up polish

**Date:** 2026-11-18. **Scope:** remove the decorative browser focus outline from programmatically focused page headings and remove the payment-method explanation from the product detail page only.

## Changes

- Livewire navigation continues to focus `main h1` with `tabindex="-1"` so assistive technology receives the new page context.
- The heading is not an interactive control or sequential keyboard stop. Add a narrow focus-outline reset for only `main h1[tabindex="-1"]` in both public storefront and admin stylesheets. Interactive links, buttons, form fields and their visible keyboard focus styles remain unchanged.
- Remove the shared payment-method note component from the product detail view. Keep the shipping-cost note. Checkout review and post-order confirmation continue to use the shared payment note component.
- Remove the now-unused gateway and store-card-availability dependencies from `PublicProductController`; this page no longer needs to query payment configuration just to render explanatory copy.

## Regression coverage

- Assert the Livewire heading-focus behavior remains, while both CSS entrypoints suppress only the programmatic heading outline.
- Assert product detail still displays the shipping note but not the card-transfer explanatory copy or its component class.
- Existing checkout payment component usage is intentionally untouched.

## Acceptance

- CI must pass before merge.
- Browser QA should confirm the title has no black rectangle after Livewire navigation on the homepage, product page, cart and customer pages; tabbing to real controls must still show the designed focus indicator.
