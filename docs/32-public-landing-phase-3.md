# Denapax — Landing Phase L3: Product Experience

**Owner-approved scope:** a polished, mobile-first product-detail preview over the public editorial Landing. This is an extension of the existing **products-only dynamic section**; Hero, story, footer and other copy remain static. No new web cart, checkout, payment or user accounts.

## Implementation

- Tap/click any product card to open a *single* native HTML dialog, rendered outside the card layout. It has a large gallery, thumbnail navigation, details/description, the existing product features, all **orderable** variants with live price in toman and a button to the existing Telegram bot.
- Changing variants updates the visible selected-variant price and selects the corresponding available photo if present. The final variant selection and placing an order happen in Telegram; this page **does not transfer a preselected variant** to the bot.
- Mobile presentation is a swipe-friendly, scrollable bottom sheet; desktop uses a two-column editorial dialog. Native dialog provides accessible focus trapping, Escape dismissal and backdrop. The modal closes by a visible control or backdrop; previous focus is restored and page scroll is unlocked on close.
- Without JavaScript, product cards retain their outbound Telegram or in-page order link.
- Backend SSR assembles only public data from the current page (up to 12 product cards), eager loading active product category, public gallery media, available positive-price variants, public variant photos, and product features. No extra public API endpoint, N+1 query, dynamic site CMS or new database table.
- Preview data in an application/json script uses Blade JSON_HEX escaping; all client text uses Alpine x-text (not raw HTML). Never serialize private receipts, credentials, unavailable variants or internal order data.
- Uses shared Money::formatToman(), preserving integer IRR storage. No new financial calculations.
- Gallery includes up to 12 unique public image URLs per product; no photos fall back to local static art. The top-level cards retain their existing public photos.

## Technical surface

- app/Http/Controllers/PublicLandingController.php: eager load features + minimal public preview projection.
- resources/views/landing/index.blade.php: safe JSON data, triggers, reusable native modal.
- resources/js/landing.js: Alpine preview state, variant/image navigation, scroll lock and focus restoration.
- resources/css/landing.css: responsive two-column dialog and mobile bottom sheet.
- tests/Feature/PublicLandingTest.php: **one** new focused backend/HTML test for displayed data, exclusion of unavailable variants, monetary precision and JSON script escaping.

## Release gates

1. Scoped PR -> green essential tests, Pint, Composer validation/audit and Vite build -> squash merge to master.
2. Verify resulting master CI; no database migrations or new environment keys.
3. Owner/deploy operator independently confirms the live interaction: click a product on phone and desktop, switch image and variant, check true toman price, Escape/backdrop close, focus return, fallback for no photo, link to configured public Telegram username.
4. Do not claim live Runflare status from CI. Remaining E07 production negative/security checks and backup/restore Issues #11/#24/#25 remain open.

**Next separately approved stage:** Phase L4 SEO/performance/final polish; not included in L3.
