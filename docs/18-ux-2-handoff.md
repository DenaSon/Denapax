# UX-2 — Native Telegram Button Styles & Icons Handoff

**Tracked in:** [Issue #39](https://github.com/DenaSon/Denapax/issues/39).  
**Reference:** [Telegram UI/UX Guidelines v1.1](telegram-ui-ux-guidelines.md).  
**Scope:** Styling and semantic button labels only. No business state, database, pricing or API workflow changes.

## Implemented

- A single `StorefrontButtons` class defines three opt-in emphasis roles using native Nutgram `ButtonStyle`: **primary → blue**, **success → green**, **danger → red**. Neutral/secondary actions use ordinary `Button::make()` without `style`.
- `StorefrontBot`: 🌰 products main-menu entry is primary; help/support, address navigation, home and other utilities remain neutral. The customer-facing approved welcome text is unchanged.
- `CatalogMessages`: “all products” and “view variants/details” are primary; add-a-variant-to-cart is success.
- `CartCheckoutMessages`: clear Persian `➕ افزایش`, `➖ کاهش` buttons remain neutral; `🗑️ حذف` is danger; continue-to-checkout and final order confirmation are success. Browse products and the post-order receipt-selection shortcut are primary, **not** an assertion that payment succeeded.
- `ReceiptBot`: upload-receipt **entry** is primary (not success; receipt upload does not prove payment).
- `CustomerOrdersBot`: empty order list's product-discovery shortcut is primary.
- Existing support direct URL stays a neutral button; help, pagination and “🏠 منوی اصلی” remain uncolored by design.
- Button text includes normal Unicode emoji or words, **never** relies on color alone. All existing callback_data and URL destinations are unchanged.

## Native Telegram boundaries

Telegram Bot API permits only `primary`, `success` and `danger` presets for this use case; it does **not** provide arbitrary HEX button backgrounds. The Telegram client/theme owns final rendering and will determine how its UI appears on each device.

Nutgram 4.50.3 exposes `style` and `icon_custom_emoji_id`. The optional custom emoji property is **deliberately not used**: account/Fragment eligibility and a suitable icon ID have not been independently verified. **No paid/premium prerequisite** is introduced; ordinary emoji labels remain the reliable default/fallback.

## Validation and remaining release gate

- [x] Code-level API serialization assertions for all three styles and for neutral buttons, with callback data/Unicode text preserved and no `icon_custom_emoji_id` output.
- [x] Existing FakeNutgram main menu assertion now verifies the primary catalog button alongside neutral help/support, preserving other rows.
- [ ] **Real Telegram device acceptance:** iOS/Android, Persian RTL, icon placement, dark/light and older-client fallback. This is **UNVERIFIED** until [Phase 6 Gate B](15-phase-6-readiness-plan.md) and [E2E/security issue #24](https://github.com/DenaSon/Denapax/issues/24) produce sanitized evidence.
- [ ] Confirm Bot API style rendering on the real configured bot, including unsupported-version behavior; do not interpret mocked CI as live proof.

Run existing PHP feature tests, Pint, Composer validation and Vite in GitHub CI for the actual implementation PR head; see PR for final run/merge evidence.

## Handoff

**Next:** [UX-3 #40](https://github.com/DenaSon/Denapax/issues/40) — catalog/page/media/chat-volume/cart presentation. It should **reuse StorefrontButtons** instead of applying ad-hoc styles or replacing callbacks.

**Separate:** Phase 6 PR #36 remains a draft, overlapping older StorefrontBot code; reconcile against latest master after UX-1/UX-2 before considering its eventual release merge. Phase 6 readiness and actual launch remain unresolved.
