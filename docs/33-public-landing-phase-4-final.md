# Denapax — Landing Phase L4: SEO, Performance & Final Handoff

**Date:** 2026-10-19 · **Owner approval:** final public-landing phase, following L1 #72, L2 #73, L3 #74. Scope remains a static editorial site with **only the catalog dynamic**. Telegram bot stays the sole order/checkout channel.

## Delivery

- **Crawlable product permalinks:** `GET /products/{slug}` renders an independent, responsive, RTL server-side product page: real category, gallery images, summary, description, public features and the **currently orderable** variants, prices and sizes. No extra content model, CMS, cart or database migration.
- **SEO metadata:** Each product has a unique title, concise description, canonical URL, Open Graph product metadata and image when a real photo exists. JSON-LD Product/Offer schema includes only factual data (no invented reviews/ratings/stock counts), and actual monetary **price currency IRR** matching stored integer rial amounts. Customer-facing HTML still formats exact **toman** via Money::formatToman. Homepage contains WebSite structured data.
- **Dynamic XML Sitemap:** `/sitemap.xml` lists home and the public, published, active-category, orderable product pages. Inactive, unavailable, soft-deleted and zero-price products are excluded by the **shared Product::publiclyOrderable()** scope. It uses a cursor and a short public cache header; no misleading lastmod claim.
- **Indexable navigation:** Product cards keep their premium Alpine detail dialog for ordinary clicks, while `href` points to the actual product page; no-JS, modifier-click, browsers without dialog and crawlers follow the real URL. Products are fully present in server-rendered detail HTML.
- **Robots:** public robots.txt permits public catalog crawling, excludes staff/login paths and advertises `https://denapax.ir/sitemap.xml`. Robot exclusions are not authentication/security controls; admin RBAC remains enforced separately.
- **Performance:** product detail page loads only the standalone Tailwind stylesheet, no Alpine bundle required; hero image keeps intrinsic dimensions / eager image hints, the remaining gallery is lazy; the homepage keeps its preloaded local SVG; fewer Google Font weights; no extra animation or SEO library.
- **Governance:** document actual release verification, no invented performance metrics or premature Google-indexing guarantees. Existing E07 issues #11/#24/#25 remain unresolved until independently proven.

## Files

- Routes: `routes/web.php`
- Controllers: `app/Http/Controllers/PublicProductController.php`, `PublicSitemapController.php`, and existing `PublicLandingController.php`
- Shared eligibility: `app/Models/Product.php` scope `publiclyOrderable`; shared public Telegram link: `app/Support/PublicStorefront.php`
- Views: `resources/views/landing/product.blade.php`, `sitemap.blade.php`, index changes
- Client: `resources/js/landing.js` preserves true permalink fallback
- Robots and short test coverage: `public/robots.txt`, `tests/Feature/PublicLandingTest.php`

## Release verification

1. Run existing CI including PHPUnit, Pint, Composer validation/audit, Vite build. Only **two** focused L4 tests were added for public product metadata/access restrictions and sitemap visibility; no excessive testing.
2. Merge a green PR to `master`; verify `master` CI again. No migrations or new env keys.
3. After Runflare deploy, open `https://denapax.ir/`, a real `/products/{slug}` URL, `/sitemap.xml`, and `/robots.txt`. Check full HTTPS URL generation and `APP_URL`; ensure product photos use public HTTPS and the configured real `TELEGRAM_BOT_USERNAME`.
4. Verify mobile/destop product permalinks and card dialog, browser keyboard/no-JS fallback and correct toman price; confirm products unpublished in admin disappear from index/permalink/sitemap.
5. Submit sitemap to Google Search Console if desired and measure **actual** PageSpeed/Lighthouse on deployed pages. Tests passing do not prove indexing, Core Web Vitals, actual Runflare rollout or business security controls.

## Completion contract

L1–L4 landing **feature code is complete after passing checks/merge**. Further frontend changes are owner-feedback-led Post-MVP, not automatically scheduled. A real live-site acceptance remains an operator/owner verification checkpoint until confirmed.
