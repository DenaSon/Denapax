# Homepage progressive scrolling with crawlable page URLs

The home page keeps server-rendered 12-product chunks and existing `/?page=n` canonical URLs. It renders the first 12 products through a nested Livewire 4 component; successive blocks of 12 append when a `wire:intersect.once.margin.200px` sentinel becomes visible. To keep the footer reachable, only two subsequent blocks are automatic; users can then explicitly load further blocks. Maximum DOM growth is capped at 30 blocks, followed by a native next-page link.

The controller still validates impossible pages as 404, preserves category redirects, and gives every paginated URL its own canonical and title. Every chunk's native `<a href>` link remains crawlable (and visible inside `<noscript>` for no-JS browsing). Direct product links, published/sellable filters, category/search pages and sitemap are preserved. No schema changes or new ENV variables.

Tests verify initial SSR, deep page canonical, sequential links, Livewire append, no duplicates and footer access. Browser QA should include Livewire navigation/back, errors/offline, RTL widths 320–430px and no-JS mode.

Reference: https://developers.google.com/search/docs/specialty/ecommerce/pagination-and-incremental-page-loading
