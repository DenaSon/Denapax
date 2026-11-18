# Denapax — Livewire navigation progress visibility

**Date:** 2026-11-18. **Scope:** visual feedback for existing Livewire 4 SPA-like navigation on the public storefront and staff administration.

## Design

- Livewire 4's navigation progress bar uses the `--livewire-progress-bar-color` CSS custom property and ships with a 2px bar.
- Both public and admin stylesheets set the progress color to burnt orange `#c2410c`, providing a clear contrast against the forest-green Denapax palette and the light page backgrounds.
- Both stylesheets override the injected bar height to 4px. The selector `body #nprogress .bar` intentionally has greater specificity than Livewire's injected `#nprogress .bar`, so the application setting wins even though Livewire injects its stylesheet at runtime.
- The native NProgress animation, progress updates, spinner setting, 150ms display threshold, SPA navigation, history and prefetch behavior are unchanged.

## Scope and acceptance

- Applies to the public landing/storefront and the admin shell.
- No JavaScript changes, navigation locks, disabled links, or artificial loading delays.
- This improves visibility only; it does not change network latency or server render time. If users still experience slow navigation after the visual cue is deployed, profile the actual Livewire navigation request and server response separately.
- Feature regression coverage checks both stylesheets for the accent color and 4px override. Browser visual acceptance remains separate from CI.
