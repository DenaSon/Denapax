# Denapax — Web storefront UI/UX polish

**Date: 2026-11-15.** Implementation PR: [#84](https://github.com/DenaSon/Denapax/pull/84). This is a **code/design-system improvement**, not a verified real-device UX acceptance or a launch approval.

## Scope and design principles

Prioritize a calm, premium, nature-inspired Persian RTL visual language without requiring animation or JavaScript for basic shopping. Base palette: deep Dena forest green, warm stone/off-white, subtle sage borders and restrained clay accent. Body/input typography uses Vazirmatn with Tahoma/system fallbacks. Main information always stays in user-visible text, not solely in color or iconography.

### Critical styling fix

`resources/css/landing.css` previously used Tailwind 4 `source(none)` but scanned only `resources/views/landing/**/*.blade.php` and the landing JS. As a consequence, unique utility classes in the checkout, cart, OTP and order pages could be **omitted from the compiled CSS**. The polish explicitly includes `resources/views/store/**/*.blade.php`, `resources/views/layouts/store.blade.php` and the new progressive enhancement script as Tailwind sources. This is a correctness/reliability fix, not simply decoration.

### Implemented improvements

1. **Unified customer shell:** reusable scoped `store-*` CSS tokens/classes for forest-green CTA, warm paper cards, typography, focus indicators and form inputs. A simple Dena mountain mark, an unobtrusive account menu, footer and three-step purchase orientation (cart → address/shipping → payment). The customer shell uses the same font as the public product pages.
2. **Small-screen rhythm and usability:** compact 320px-friendly navigation, minimum ~44–50px tap targets, clearer line heights and borders, consistent primary/secondary button weight, readable mobile inputs (16px) and safe-area-aware content bottom space.
3. **Checkout / cart / login / address:** consistent card and field styling, prominent exact toman checkout total, inline quote without extra pages (the previous Phase 4B domain workflow is preserved), less visual competition between primary CTA and editing links, clearer error/status summaries. One-tap cart quantities are retained.
4. **Payment/order hub:** visually distinct successful, receipt-review, uncertain and awaiting-payment states; amount is surfaced before actions. A primary secure gateway CTA and secondary collapsible card-to-card form remain. A gateway request, recheck or private receipt upload uses an optional `data-submit-once` affordance with an immediate pending label, while server-side idempotency and reconciliation remain authoritative.
5. **Number copy helpers:** order reference and manual card number have browser Clipboard API buttons that appear **only** when the API exists. Native text selection still works without JS, and no payment state is inferred from copy success. The server only renders stored customer-owned order data.
6. **Account/order list:** compact status chips and a genuinely accurate `نتیجه پرداخت در حال بررسی` label for unresolved gateway attempts. The account list eagerly loads attempts to avoid N+1 queries; the order page continues blocking ambiguous duplicate payments.
7. **Product selection:** stronger native radio selected indicator with CSS-only visual feedback and preserved keyboard/assistive-technology semantics; existing variant availability and server validation remain untouched.

## Progressive enhancement safeguards

`resources/js/store.js` is small and optional: it tracks `data-submit-once` form submissions to discourage immediate double taps, restores buttons on browser `pageshow` (including Back/Forward cache), and reveals Clipboard copy buttons only when browser support exists. The actual forms are standard POST/PATCH with CSRF and function without this JS. Backend checkout fingerprint, idempotent order key, Zarinpal verification and money-safe reconciliation are unchanged.

## Checks included

- Feature tests verify customer checkout progression, server-rendered quote and single CTA, purchase stage semantics, storefront Tailwind scanning sources, and safe pending-payment text in order list/detail.
- The existing core order, OTP, address-isolation, manual receipt, gateway callback and reconciliation tests remain part of CI.
- CI additionally runs Composer locked audit, Pint and Vite build. Generated CSS, server-side HTML and mocked API checks **do not** prove actual pixel-perfect display on iOS Safari or Android Chrome.

## Still required before calling UX "done"

- [ ] Review real Android/iOS screens at 320px, 375px, 390px and 430px (portrait and landscape), plus desktop; verify navigation wrap, long Iranian names/addresses, price overflow and card-number layout.
- [ ] Test keyboard-only purchase, radio choice, account `details`, zoom to 200%, VoiceOver/TalkBack labels, reduced motion and contrast.
- [ ] Run native checkout with the SMS OTP provider, refresh/back/forward, price change, duplicate confirm, expired session and Zarinpal bank return.
- [ ] Test payment pending/canceled/verified, manual receipt format/permission and copy-action permission denial on real devices.
- [ ] Record sanitized screenshots/viewport/device results and owner approval in [release QA #24](https://github.com/DenaSon/Denapax/issues/24). **Do not claim visually audited devices without that evidence.**

## Next steps

Phase 4C: real OTP SMS service, confirmed Zarinpal merchant account/currency contract and safe live bank settlement, staging MySQL cutover with consistent backup/restore. Phase 4D: security/operations and Go/No-Go. Refer to [overall roadmap](39-web-roadmap-status.md), [Phase 4 readiness](38-web-phase4-release-readiness.md), and [Phase 4A financial safety](40-web-phase4a-gateway-reconciliation.md).
