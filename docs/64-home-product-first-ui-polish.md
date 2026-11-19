# Homepage — final product-first UI/UX polish (PR 2 of 2)

This change deliberately keeps the **SEO and scrolling contract** of PR #168 intact.

## What changed
- The root homepage features one short 180–216px Dena-branded introduction with a descriptive Persian `<h1>`, followed immediately by search, category navigation and server-rendered product cards.
- Removed the full-screen editorial hero, three-item assurance strip, standalone story/order sections, floating numbered chapter rail and their unnecessary Alpine scroll observers.
- The *first three active and orderable categories* by configured `sort_order` then name get stronger styling **within the same navigation** (no duplicate category links). On narrow screens the chips scroll horizontally.
- The footer now contains a short factual introduction to the Dena and Sisakht assortment and explicit purchase steps, with actual links to cart, orders, account and products; support and design credits remain.
- Product detail header's old #story link now leads to this footer's #shopping-guide anchor. Header remains sticky and opaque for predictable contrast.
- Desktop/tablet/mobile CSS is scoped to `.home-catalog` and `.home-*` classes so checkout, category and product screens retain their original layout.

## SEO guarantees
- Existing website and organization JSON-LD, indexed product permalinks, canonical `/?page=n` pages, descriptions, Persian page titles and `<meta name="robots" content="index,follow,...">` remain.
- Crawlable next-page links and real initial SSR product cards stay in the Livewire feed. Dedicated category and search URLs, sitemap and legacy redirects stay untouched.
- Product/local-origin content has not disappeared: concise, matter-of-fact Dena/Sisakht text is server-rendered in the H1, introduction and footer. Avoid exaggerated claims, fake availability or invented policy pages.

## QA checklist
- 320–430px Android Chrome/iOS Safari and tablet/desktop: focus on readable header, no horizontal overflow except intentional category-chip scroller, accessible category/footer links and images.
- Scroll 12→24→36 products, then use manual load: footer remains reachable, no duplicates, old page-next/prev controls are gone from visual presentation.
- Confirm `/?page=2`, no-JS pagination links, canonical/metadata, and return navigation from product detail.
- Test guest purchase, cart and customer-account routes as before. No changes to database or environment variables.
