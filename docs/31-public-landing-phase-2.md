# Denapax — Public Landing Phase L2: Motion & Responsive Refinement

**Decision:** 2026-10-19 · **Scope:** owner-approved second phase. Phase L1 already delivered the public route and dynamic catalog. Phase L2 improves editorial motion, mobile and keyboard experience, and graceful fallbacks; **no new dynamic sections or checkout scope**.

## Code delivered

- Editorial visual system: Hero light accent and orbit, scroll-progress strip, numbered wide-screen chapter rail, SVG topographic line drawing, product image-wipe.
- Alpine interactions: requestAnimationFrame-coalesced scroll and pointer updates, IntersectionObserver-based reveals, detached listeners and disconnected observers on destroy.
- Accessible mobile menu: expanded state, first-link focus on open, Tab/Shift+Tab cycle, Escape restores toggle focus, outside click and desktop resize close the menu.
- Without JavaScript, SSR catalog is still readable and a noscript mobile navigation is provided. Animation is optional, not required to discover products.
- Reduced-motion disables decorative motion; high-contrast headings have solid text; keyboard product focus has the same visual affordance as mouse hover.
- Google font stylesheet moves from blocking CSS import into HTML head with preconnect, display=swap and system fallback.
- No changes to admin assets, bot checkout, pricing arithmetic, catalog query, database schema or environment variables.

## Files

- resources/js/landing.js — lifecycle-safe Alpine motion and mobile focus behavior
- resources/css/landing.css — design motion, contours, progress rail and accessibility fallbacks
- resources/views/landing/index.blade.php — semantic section anchors, visual identity and no-JS navigation
- tests/Feature/PublicLandingTest.php — lean SSR/navigation contract test

## Verification and rollout

1. Scoped PR CI: PHPUnit, Pint, Composer validation + dependency audit, and Vite production build; merge only when green.
2. Verify master CI separately after merge. Code delivery is not proof of public domain rollout.
3. Deploy updated Vite assets normally; no migrations. TELEGRAM_BOT_USERNAME configured during L1 is still required for real bot links.
4. On real devices check 320px phone, tablet, desktop, keyboard Escape/Tab, no-JS fallback, reduced-motion, image reveal, no sideways overflow and live Telegram CTA. Not asserted until actual deployment.
5. Original E07 security/backup #11/#24/#25 remain open independently.

Related: [Phase L1](30-public-landing-phase-1.md) · [MVP freeze](29-mvp-launch-freeze.md) · [AGENTS](../AGENTS.md).
