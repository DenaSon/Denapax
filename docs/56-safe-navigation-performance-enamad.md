# Denapax — safe navigation performance and eNamad domain verification

**Date:** 2026-11-18. This release only contains code and static-file changes; production TTFB and eNamad portal verification require post-deployment checks.

## Optimizations

- Home: a single reactive Livewire header-cart component renders desktop and mobile cart links, removing an extra controller cart read and a duplicate Livewire component. Authenticated customer totals use one SQL sum with an indexed cart join, rather than fetching cart then item count.
- Product: StoreCart queries cart quantities only for the currently visible sellable variant IDs (and reads only those keys from the guest session). Existing stock, prices, cart mutations, checkout and ownership checks remain server-authoritative.
- Selective Livewire 4 `wire:navigate.hover` on catalog cards and a few primary public header links only. No blanket prefetch of checkout/financial actions or private admin navigation. Prefetch may hide some waiting but does not lower backend processing time and can add speculative GET traffic.
- Admin orders: date filters use an inclusive start-of-day and exclusive next-day SQL range. This avoids wrapping indexed `created_at` in `DATE()`. Status filtering and `Gate::authorize('orders.view')` remain unchanged.
- No new database migration, Redis dependency or caching of personalized pages.

## eNamad verification

- The file `public/45504479.txt` is **zero bytes**. It must be served directly by the web server at `https://denapax.ir/45504479.txt` rather than returned by a Laravel Blade response or route.
- The homepage `<head>` includes `<meta name="enamad" content="45504479" />`.
- After deploying the merged commit, verify from outside your production cluster:

```bash
curl -sSI https://denapax.ir/45504479.txt
curl -fsS https://denapax.ir/45504479.txt | wc -c
curl -fsS https://denapax.ir/ | grep 'name="enamad"'
```

Expected: **200** for the .txt file, zero content bytes, and a meta tag with exact content `45504479`. Beware HTML fallbacks, redirects, proxy caching and stale deployment replicas. Only then click the **«تایید بارگذاری»** and **«تایید متاتگ»** buttons in the eNamad portal.

## Acceptance and measurement

- Run PHPUnit, Pint, Composer validation/audit and Vite on the PR, and repeat on merged master.
- Check guest and authenticated cart badge counts across homepage, product, cart after add/increase/decrease/remove and after `wire:navigate`. Confirm no empty persistent cart is created on a public page visit.
- Check product variant selection, cart quantity reconciliation and native POST fallback on non-JS navigation.
- For admin order filtering, verify end date includes late-evening order records, start date includes midnight records and permission checks remain unchanged.
- Compare Chrome Network Navigation TTFB and the number of Livewire requests against the prior release. Browser measurements and server metrics are needed before claiming an actual speed improvement.

No payment, gateway, checkout, order authorization, private receipt media or deployment secrets changed.
