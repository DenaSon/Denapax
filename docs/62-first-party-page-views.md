# Denapax — first-party page-view analytics (MVP)

## Scope
- Counts **displayed homepage** and **publicly orderable product page** views, not HTTP GETs.
- The browser sends one small, CSRF-protected POST after initial page display or a completed Livewire \`livewire:navigated\` event. \`wire:navigate.hover\` prefetch does not send a view.
- First-party browser token is random; only a day-scoped HMAC hash is stored. No raw IP, phone, customer ID, user-agent, referrer, address, or persistent cross-day identifier is stored in analytics tables.
- Repeat beacons from the same browser on the same page inside 10 seconds are ignored. After that, another displayed view can count. "Unique" is per **page per day**, not a global count of people.
- Known bot user agents, staff sessions, invalid/unpublished product IDs, malformed beacons, and non-JavaScript views are excluded. Bot detection is best-effort and the figures should not be interpreted as an exact number of human users.
- The counter is a short MySQL transaction with unique-key arbitration and atomic increments. No queue workers, third-party scripts, external analytics provider, or full request logs are required.

## Production rollout

1. Deploy code and run \`php artisan migrate --force\` through the normal approved deployment procedure **before** accepting page-view beacons.
2. Keep the existing Laravel Scheduler running. It removes anonymous per-page visitor hashes older than 90 days at 03:40 Asia/Tehran via \`denapax:analytics:prune\`. Daily aggregate counts remain indefinitely.
3. Verify \`GET /\` and \`GET /products/{slug}\` return SEO metadata unchanged and an analytics marker in the page body.
4. In a real browser, open the homepage and a valid product; verify POST \`/analytics/page-view\` returns 204 and inspect \`page_view_daily\` totals. A Livewire hover-prefetch must not count.
5. Analytics starts at deployment time and cannot reconstruct historical visits. Privacy tools/JS blockers may suppress some events. Reporting UI is a separate PR.

## Read-only queries

\`\`\`sql
SELECT view_date, page_type, product_id, views, unique_visitors
FROM page_view_daily
ORDER BY view_date DESC, views DESC
LIMIT 30;
\`\`\`

No raw analytics visitor identities should appear in admin reports or logs.
