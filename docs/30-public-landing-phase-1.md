# Denapax — Public Landing Phase L1

**Date:** 2026-10-19 · **Owner approval:** public landing/catalog at denapax.ir; scope is strictly editorial design + only products dynamic. Telegram remains the only checkout/payment channel.

## Scope delivered

- `GET /` changes from old admin redirect to an RTL public homepage. `/admin` remains staff-only and unchanged.
- Static custom cinematic Dena-inspired vector landscape, contour art, editorial typography, light/dark nature palette, origin narrative, Telegram CTA and footer.
- Responsive desktop/mobile layouts, accessible menus, gradual entry reveals and subtle pointer movement. Reduced-motion supported. Hero artwork is original local SVG (no external image license).
- Only product catalog is dynamic: active product + category, at least one available positive-price variant. Dynamic category pills, pagination (12 per page), product gallery image with available variant-media fallback, shared integer-IRR to toman formatter, safe empty state. No new schema, product CMS or duplicated data.
- Telegram outbound links are generalized bot home links. Product-specific deep linking is **not** claimed (Nutgram currently does not process product start payloads).
- Filter/pagination use server-rendered URLs. Alpine only handles navigation/visual enhancement. Page is useful without JS.

## Architecture

- New `PublicLandingController` with efficient eager loading of gallery + variants/media.
- `resources/views/landing/index.blade.php` — accessible semantic Blade page, standard SSR, SEO meta/OG/canonical.
- `resources/css/landing.css` — separate Tailwind 4 entry without daisyUI/admin theme, with Vazirmatn from optional Google Fonts and local fallback.
- `resources/js/landing.js` — standalone Alpine 3, never mounted in admin Livewire layout.
- SVG illustrations in `public/images`.
- Existing IRR DB prices/order snapshots, product publishing, admin RBAC, private receipts and webhook are unchanged.

## Production activation

1. Deploy only after green PR CI; install frontend dependencies and build four Vite entries. No DB migrations needed for this change.
2. Set `TELEGRAM_BOT_USERNAME=YOUR_REAL_BOT_USERNAME` as a private environment variable (without @). It is **not** the secret TELEGRAM_TOKEN. Refresh Laravel configuration using `php artisan optimize:clear` and `php artisan config:cache`. If missing/invalid, UI announces that the link is pending instead of guessing.
3. Ensure `APP_URL=https://denapax.ir` is correct, TLS/reverse proxy works, product media symlink is present, and private receipt images remain inaccessible to anonymous users.
4. Verify on real phone and desktop: layout/contrast/RTL, menu, reduced-motion, product images, selected filter, pagination, exact toman prices, empty state and opening the configured bot URL. Live Runflare deployment and actual visual QA must be confirmed separately; CI is not proof of deployment.
5. Keep security/ops debt Issues #11/#24/#25 open. Do not infer backup/restore acceptance from this feature.

## Acceptance checks

- Targeted tests cover guest homepage availability, exclusion of unpublished/unavailable catalog items, filter/pagination, toman amount and valid/invalid bot handle.
- Run PHPUnit + Pint + Composer validation/audit + Vite CI; recheck master CI after merge.
- No third-party product API, online payment, web cart, speculative settings or invasive tests.

**Next:** Owner-reviewed post-deploy real-browser visual smoke; further changes only from approved feedback. Full scope remains versioned in [MVP freeze](29-mvp-launch-freeze.md) and [AGENTS](../AGENTS.md).
