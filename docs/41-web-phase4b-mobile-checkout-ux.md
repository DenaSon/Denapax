# Denapax Web Phase 4B — Mobile-first checkout UX

**Implemented and merged into `master`:** [PR #83](https://github.com/DenaSon/Denapax/pull/83) · SHA `3ca052738bb5397f658306669f67ecb37375d55f` · [green CI](https://github.com/DenaSon/Denapax/actions/runs/43723331966), 77 tests / 523 assertions · 2026-11-15. **Release status:** NOT PRODUCTION-VERIFIED; real phone/device tests and the separate provider/restore gates remain pending. See [Phase 4 release plan](38-web-phase4-release-readiness.md).

## Goal

Make the web customer journey fast and comprehensible on small screens, with no extra steps or unsafe payment shortcuts. The intended happy path is:

**Product → choose ONE variant + quantity → cart → OTP (only if guest) → delivery and exact quote on ONE page → register order → pay on the existing order page.**

### Delivered

- Product: radio-style variant cards with visible individual prices, one shared quantity input and one primary add-to-cart button. Server-side variant ID/availability/price checks are unchanged. No dependency on JavaScript for placing an order.
- Cart: large one-tap **+ / −** buttons submit existing validated `PATCH /cart/items/{variantId}` requests with the absolute next quantity; `−` from 1 removes the line. Clear total, availability warnings and one wide checkout CTA. Guest users go to the protected checkout URL so Laravel remembers the intended destination while OTP is completed.
- Store header: readable touch-sized cart link and compact accessible account menu; smaller mobile padding, focusable skip link and clear validation/error text.
- OTP: show only the mobile form *or* the six-digit verification form as the primary step; resend/change-number stays accessible under optional details. Persian/Arabic OTP digits are normalized **before** the existing six-digit verification, without loosening OTP expiry/limits.
- Delivery form: verified customer phone prefilled (still editable for recipient), familiar browser autocomplete and number keyboard, postal digit normalization, optional address nickname after essential fields. When saving/editing during checkout, the user returns **directly to that same selected address**, not an unrelated default.
- **No extra review page for normal checkout**: `GET /checkout` selects the saved default address, calculates a fresh authoritative `PrepareCheckout` quote (including shipping) and creates/reuses a 30-minute session-bound checkout key with the same price/address/cart fingerprint. The customer sees the final **toman** total and taps **one** button `POST /checkout/confirm` to create the immutable order and continue to payment.
- Change address via a one-tap **GET /checkout?address_id=ID** choice. The request only accepts an address belonging to the logged-in customer; foreign/invalid IDs return 404. Price, cart or address changes rotate the checkout key and invalidate old confirmation forms.
- Previous `POST /checkout/preview` and `GET /checkout/review` still work for compatibility, but the new UI no longer routes ordinary buyers through that redundant screen. Server-side order validation/idempotency and Zarinpal reconciliation controls remain unchanged.

## Proof and acceptance

Automated feature tests added for default quote without extra preview, exact delivery and toman totals, refresh preserving the checkout idempotency key, direct confirm and single order, selecting a second address/denying another customer's address, changed-price invalidating old key, guest and customer quantity updates, and Persian OTP login preserving return-to-checkout.

**Not proved by CI:** physical iPhone/Android/touch interaction, keyboard focus with a screen reader, cross-browser rendering, actual OTP delivery, Zarinpal return with cookies/session expiring, merchant settlement, MySQL production migrations, private-media restore, visual metrics or time-to-purchase.

### Real device test checklist — still required

- [ ] Android Chrome and iOS Safari at 320–430 px; desktop Firefox/Chrome; portrait, landscape and browser zoom. Verify RTL layout, tappable product variant cards, +/− hit area, header details menu, single-page checkout summary and primary CTA do not overlap.
- [ ] Add two different variants, quantity to 0 and max 1000; remove unavailable variant and confirm total/CTA update.
- [ ] Guest checkout → SMS OTP → cart restored → checkout automatically resumed; use numeric keyboard with both Persian and Latin digits; resend cooldown and network error messages.
- [ ] First saved address; multiple addresses; change selected address; edit return, Persian postal code, name/mobile autocorrect; compare shipping and full total before commitment.
- [ ] Refresh checkout, navigate Back/Forward, stale cart/price, fast double-tap confirm; ensure no duplicate order or stale amount.
- [ ] Online payment succeeded, failed, browser closed or session expired; card-to-card receipt submission/review/rejection; do not offer a second charge when reconciliation is unresolved.
- [ ] Keyboard-only tab, screen-reader label text, viewport zoom and contrast; ensure validation messages identify the action needed. Record sanitized device/version/date/results in release Issue #24 before claiming UX acceptance.

## Decisions / future improvements

Keep checkout progressive and server-rendered rather than adding fragile client-side payment state. Address title is optional; no extra account/profile fields. Numeric inventory reservation, checkout analytics and automated browser testing may be considered after the release P0 gates. **Phase 4C/4D remains NO-GO** until validated provider credentials, live bank settlement, secure deployment/restore, monitoring and owner sign-off.


## Follow-up UX additions requiring device acceptance — 2026-11-17

The following code changes are merged but must be included in the physical-device walkthrough before #24 can close:

- Product detail (PR #137): add a selected variant, switch between variants already/not already in cart, increment/decrement/remove, confirm header count and selected price update without document navigation; exercise Livewire timeout/419 recovery and native POST fallback.
- Mobile cart (PR #139/#142): verify the fixed bottom dock at 320–430px widths, safe-area inset, last-item visibility, live toman subtotal/count, guest SMS-login label, authenticated delivery label and disabled checkout while an unavailable line remains.
- Checkout address picker (PR #139): verify recipient mobile and postal code are readable LTR, and the default address marker remains distinct from the selected address.
- Admin catalog/upload changes (PR #140/#143/#144) are separate admin acceptance: verify mobile cards at 320–390px, each gallery filename's independent progress/completion/error state, variant upload progress, temporary previews, remove-pending controls, file-picker lock during an active batch and save feedback.
