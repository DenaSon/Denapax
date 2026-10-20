# Landing refinement — centered nature hero, AJAX catalog & compact order

**Date:** 2026-10-20 · **Owner-requested post-launch refinement** on `DenaSon/Denapax`.

## Changes

- Center-align the “از دل دنا” hero, simplify decorative detail and reduce its height (desktop and mobile).
- Replace the illustrated mountain foreground with a real photograph from the Dena region. Source: [Dena mountains, Kohgiluyeh and Boyer-Ahmad Province](https://commons.wikimedia.org/wiki/File:Dena_mountains_کوههای_دنا_کهگیلویه_بویر_احمد_-_panoramio.jpg) by **Farid Atar**, licensed [CC BY 3.0](https://creativecommons.org/licenses/by/3.0/). Attribution is included in the page footer. The landscape uses Wikimedia's static image URL; the previously committed local SVG remains as a CSS fallback if the remote photo cannot load. An external CDN is a deployment dependency until a properly credited optimized copy is self-hosted.
- Remove the standalone “هر محصول، روایتی از یک جاست...” introduction. Place the SSR product catalog directly below the hero.
- Preserve genuine server-side category filtering and pagination while using Alpine-enhanced `fetch()` to replace only the product section's HTML, no full document reload. Keep real filter/page links for no-JS, keyboard, network fallback and Google indexing. Browser History pushState/popstate supports Back/Forward.
- Continue to refresh page-specific **public-only** product previews when category/page changes, so the details dialog always uses the current catalog rather than stale values.
- Replace the oversized “از اینجا به تلگرام” panel with a compact, accessible Telegram action. Preserve the configured public username validation and meaningful missing-bot status.
- Keep brand/origin section, live catalog filtering rules, product permalinks, real toman prices, Telegram checkout and admin unchanged.

## Release/QA

- Focused existing PHP tests for catalog and one compact regression for markup and navigable filters; no new database schema or Python dependency.
- Run repository CI (PHPUnit, Pint, Composer audit and frontend Vite build). Squash merge only after green.
- On Runflare, verify hero/photo/CDN access, mobile/desktop text alignment, category chips and pagination **without full browser reload**, browser Back/Forward, modal details after switching filters, available product images/prices and Telegram button.
- If Wikimedia CDN fails from Iranian ISP/network, replace the remote URL with a locally hosted optimized JPEG/WebP downloaded under the attribution license; do not remove required credit.
- E07 operations/security and backup/restore issues remain independent.

