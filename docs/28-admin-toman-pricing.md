# Admin toman pricing — 2026-10-19

**Code release:** [PR #69](https://github.com/DenaSon/Denapax/pull/69) merged in `master` at `edf3805811478683d1a23d79f1c7a43dfa72ee5d`; [master CI 39287004609](https://github.com/DenaSon/Denapax/actions/runs/39287004609) passed. **Owner confirmation:** multiple real products were defined and each checked once in the Telegram bot. This is useful functional acceptance, **not a documented exhaustive test of all old fractional IRR values or price snapshots**. See [MVP launch/freeze](29-mvp-launch-freeze.md).

- Admin product variants and nationwide shipping tariffs are entered in **toman**; all admin order/payment amounts and Telegram amounts display toman.
- The database (`*_irr`), checkout and shipping calculations, immutable order snapshots and existing financial records remain integer **IRR**. No migration, data update or repricing.
- Money conversion is integer-only. A legacy price ending in 1–9 IRR is shown as a single decimal tenth of a toman and can be edited without silent rounding. New amounts should generally be whole toman.
- Price fields are text with decimal numeric keyboard, not browser number inputs subject to mouse-wheel increments. Persian and Arabic digits and grouped thousands separators are accepted.
- Publication requires at least one orderable variant; newly-added variants default to orderable.

## Production smoke (after CI + deploy)
1. Edit a product to `1389000` toman; verify `13890000` IRR storage and `1,389,000 تومان` in the bot.
2. Validate historical sub-toman amounts are visible and preserved until explicitly corrected; do not rewrite historical orders.
3. Edit shipping fees; verify checkout, receipt-review panel, order detail and Telegram payment text agree.
4. Check a product with every variant unavailable cannot be newly published.

No production setting or price is changed by this code update. The production deployment and live smoke must be verified separately.
