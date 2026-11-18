# Denapax — public technical SEO handoff

**Code scope:** public SEO foundations. No change to customer authentication, cart, pricing, payments, storage or shipping calculation. **Live Search Console registration is not completed in code.** The user will verify the Google property and submit the sitemap.

## Implemented

- Homepage <title>, meta description and Open Graph describe the real offering: Dena souvenirs, dried fruits, regional goods and Sisyakht. Existing visual headline «از دل دنا» is unchanged.
- Brand identity data: WebSite and OnlineStore/Organization JSON-LD on homepage; square, self-hosted mountain-mark SVG based on the existing header mark. No unsupported social profiles or ratings are claimed.
- Public categories: \`/categories/{slug}\` with server-rendered product cards, internal links, indexable canonical/meta/Open Graph, CollectionPage + BreadcrumbList JSON-LD, pagination and 404 for inactive/empty/exhausted categories.
- Legacy \`/?category=slug\` links 301 redirect to the category permalink (retaining a valid page number). New homepage navigation and product breadcrumbs link directly to public categories.
- Product pages: product-specific titles and description meta based on stored summary/description; IRR prices in Offer JSON-LD (displayed site pricing remains in toman), seller reference and visible-category breadcrumbs. Image labels reflect stored product/variant data instead of unrelated keywords.
- XML sitemap enumerates home, only active categories with orderable products and only publicly orderable product permalinks. Existing \`robots.txt\` points to the sitemap.
- \`/search\`, \`/cart\`, customer entry/account and other shared store pages still have \`noindex,nofollow\`. The new public category template explicitly opts into indexability.
- Removed unused external Wikimedia hero preload and replaced it with a preload of the SVG actually used by the CSS hero. No synthetic performance metrics are claimed.

## Deliberate non-goals

- No invented review/aggregateRating, price discounts, local address, telephone, legal IDs, official social handles, shipping delivery time, return days, return fees or guaranteed delivery coverage.
- Product image/social cards use actual catalog media when present; product pages without media do not invent OG product photos.
- Do not emit shipping/returns merchant listing schema without a published and operator-approved policy. Nationwide weight-based shipping depends on checkout context; an arbitrary flat schema amount would be incorrect.
- No automatic Search Console ownership confirmation, indexing requests, live performance assessment or promised organic ranking.

## Owner runbook after deploying master

1. In **Google Search Console** (https://search.google.com/search-console), add \`denapax.ir\` as a **Domain property** and verify the DNS TXT record as instructed by Google. Alternatively use an HTTPS URL-prefix property with the offered verification method if DNS access is unavailable.
2. Check deployed \`https://denapax.ir/robots.txt\` and \`https://denapax.ir/sitemap.xml\` return 200; verify \`https://denapax.ir/categories/{a-real-slug}\` and \`/products/{a-real-slug}\` resolve with self-canonical URLs.
3. Submit \`https://denapax.ir/sitemap.xml\` in Search Console > Sitemaps. Inspect home, a category and a product using URL Inspection; request indexing for representative pages if needed.
4. Test sample product pages in Google **Rich Results Test** (https://search.google.com/test/rich-results), check product offers match visible stock and actual sale prices; monitor the Product snippets / Merchant listings reports when Google makes them available.
5. Run mobile PageSpeed Insights (https://pagespeed.web.dev/) on home, category and product pages, record Core Web Vitals/LCP/CLS/INP, and improve any measured regressions instead of guessing scores.
6. After Google crawls, review Search Console Performance for branded vs nonbranded queries and indexing coverage. Adjust real product summaries and category content based on observed impressions/clicks. Never add product claims not evidenced by catalog records.
7. If publishing an official shipping/returns policy in the future, add links and verified terms to the site **before** adding matching merchant policy structured data.

## Key regression checks

- Active and orderable catalog rules are shared with homepage, product route and sitemap.
- Inactive/unavailable products and inactive/empty categories never appear as indexable URLs.
- Sensitive customer/admin pages never gain public indexable metadata.
- Canonical URLs are stable and pagination does not collapse distinct pages onto page one.
- Product JSON-LD uses IRR (database unit), with no fictitious merchant policy or review signals.
