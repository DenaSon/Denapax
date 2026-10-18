# Denapax — Telegram UI/UX & Brand Experience Guidelines v1.0

**Status:** Product direction approved by owner on 2026-10-19; implementation is **planned**, except features explicitly identified as existing below.  
**Channel:** Regular Telegram Bot (messages + inline keyboards), **not** a Mini App.  
**Scope:** User-facing Telegram storefront UI/UX polish within MVP v1.1; this document supplements [MVP Scope](01-mvp-scope.md), [Workflows](04-workflows.md), and [Execution Agreement](09-execution-agreement.md).  
**Release gate:** Real Telegram/device/browser, MySQL/Redis, webhook and operational proof remain part of [Phase 6 readiness](15-phase-6-readiness-plan.md), regardless of automated checks.

## 1. Brand character and experience principles

Dena ("دنا") is an inviting, trustworthy souvenirs and dried-fruit shop. Its experience should evoke **authenticity, nature, freshness, warmth and confident simplicity**, without overpromising origin or product qualities that are not documented.

**Approved tagline (exact wording):** «دنا؛ طعم اصالت، عطر طبیعت»

Customer experience principles, in order:
1. **One obvious next action:** show the customer's next meaningful step before secondary options.
2. **Calm, uncluttered chat:** favor a compact, readable message over repeated batches of photos/text and navigational noise.
3. **Human Persian:** warm, respectful, direct, short. No bureaucratic language, empty superlatives or excessive emoji.
4. **State clarity:** never blur "order registered", "receipt received", "payment approved", "preparing" and "shipped".
5. **Trustworthy amounts:** consistently display **toman** to customers; keep database financial values in integer IRR and existing immutable snapshots unchanged.
6. **Wayfinding:** a customer should be able to return to the relevant preceding step and/or the main menu; never strand them after an error or empty result.
7. **Accessibility:** useful text labels in addition to emoji; readable messages; no essential status conveyed by color alone.

### Visual direction, not a Telegram theme override

- Mood: natural, restrained, inviting; mountain/nature cues and appetizing, honest product photography.
- Suggested brand palette for any **controllable** assets/admin refinements (not an approved mandatory color spec): evergreen `#275D49`, warm cream `#F7F2E8`, dried-fruit brown `#A57548`. Verify contrast before usage.
- Telegram inline button styling/color, fonts and chat background belong to the **Telegram client/theme**; the bot cannot impose custom CSS/colors, fonts or a bespoke shopping screen.
- Use descriptive photos with consistent quality and aspect framing where feasible; never synthesize or misrepresent actual products.
- Emoji taxonomy: 🌰 catalog/products, 🔎 search, 🛒 cart, 📍 addresses, 🧾 receipt/payment, 📦 orders/preparing, 🚚 shipped, ✅ confirmed, ❌ rejected, ☎️/💬 support, 📖 help, 🏠 home.
- Use roughly one contextual emoji per heading/button; do not decorate every sentence or display multiple redundant status icons.

## 2. Approved startup copy (canonical)

On `/start`, show the following text followed immediately by the main inline keyboard. Preserve wording and punctuation (formatting can use Telegram-supported parse mode only when safe):

```text
🏔️ دنا؛ طعم اصالت، عطر طبیعت

به دنیای خوش‌طعم سوغات و خشکبار دنا خوش آمدید. 🌿

🌰 برای شروع، از منوی زیر انتخاب کنید.
```

Only this copy is explicitly approved as **final customer-facing wording**. Other sample texts in this guide are behavioral guidance subject to implementation review.

### Main menu layout (two columns where meaningful)

| Row | Left/right buttons | Callback/behavior |
| --- | --- | --- |
| 1 | 🌰 محصولات · 🔎 جستجو | `menu:catalog`, `menu:search` |
| 2 | 📍 نشانی‌های من | `menu:addresses` |
| 3 | 🧾 ارسال رسید | `menu:receipt` |
| 4 | 🛒 سبد خرید · 📦 سفارش‌های من | `menu:cart`, `menu:orders` |
| 5 | 📖 راهنما · ☎️ پشتیبانی | `menu:help`, `menu:support` |

Keep each important standalone action on a single full-width row if its label or mobile readability requires it. Do not switch to persistent ReplyKeyboard or Mini App without explicit product approval.

**Existing implementation note (as of guide authoring):** product/cart/address/orders and support menu exist in `master`; help + `/help` are implemented **on the draft Phase 6 PR #36**, not yet merged to `master`. The direct Telegram support chat link and new greeting copy are **not yet implemented**. Never confuse this approved guide with deployed software.

## 3. Interaction and copy patterns

### Navigation

- Every important screen/flow must offer an appropriate **«⬅️ بازگشت»** and/or **«🏠 منوی اصلی»**. Back must go to the actual parent action rather than an unrelated/new checkout state.
- Keep the obvious primary action visible; use no more than two concise inline buttons per row in normal customer flows.
- On repeated navigation, **edit the existing bot message when safe and supported** to reduce clutter; for media/text mode changes and other API restrictions, send a new well-scoped message instead. Never blindly edit a message that might be in a different state.
- Always acknowledge callback queries promptly; stale/invalid callbacks explain what happened and link to a safe recovery action.
- `/start`, `/help`, `/cancel` should remain discoverable and predictable; do not silently destroy an in-progress receipt/address task except per agreed reset semantics.
- No fake "buttons": use Telegram inline keyboard API with callback data or safe URL targets.

### Catalog and product discovery

- Paginate; avoid broadcasting a multi-product image/message wall for each page request.
- Prefer **one focused product card/message at a time** when feasible: truthful image, short product title, concise summary, relevant price/variant action, and previous/next navigation.
- Detail: title, key attributes, variant names/weights or sale units, **variant prices in toman**, availability, optional gallery, clearly labeled **«🛒 افزودن به سبد»**.
- Keep photo captions within Telegram limits. Place overflow detail in a separate concise message only when necessary.
- Missing image/empty search/unavailable variant: helpful plain-language fallback with **«🌰 مشاهده محصولات»**, retry or home.
- Product imagery and price examples in design mockups are **illustrative**, not real catalog facts.

### Cart and checkout

- Cart view: product + selected variant, quantity, per-line amount, **goods subtotal**, intuitive `➕`/`➖`/«حذف», **«✅ ادامه و ثبت سفارش»**, and return-to-products.
- After add/update, short confirmation plus the updated cart/next action; avoid repeated success messages on every minor click.
- Show address selection or entry, parcel weight/shipping fee, goods subtotal and total **before final order creation**. Mark payment instructions as after confirmation; don't call an order "paid" at this stage.
- Protect domain rules unchanged: stock availability flag, authoritative reprice, idempotent confirmation, ownership checks, immutable financial/address snapshots.

### Manual card-to-card payment and receipt flow

1. Once order is confirmed, show **order number, amount, card transfer instructions and «در انتظار پرداخت»**; offer **«🧾 ارسال رسید پرداخت»** and **«📦 مشاهده سفارش»**.
2. From the main «ارسال رسید» entry, **first show the customer's eligible orders** and require selection; never ambiguously attach an upload to another order.
3. Show chosen order number and amount; accept the valid private image and confirm **«رسید دریافت شد؛ در انتظار بررسی پرداخت»**. This is **not payment approval**.
4. If rejected, show human rejection reason and a direct route to upload a **new receipt for the same selected order**.
5. Only authorized manual bank verification triggers `payment.approved` and order state `confirmed`. Enter `preparing` only after the separate fulfillment action; later `shipped` shows carrier/tracking.
6. Every receipt view/download retains private-disk and staff-authorization guarantees. Never print or expose payment media in public product URLs.

### Own orders and transactional messages

- Order summaries should surface **number, current payment state, current fulfillment state, total**, and tracking when present. Keep status text consistent between detail screen and notifications.
- Required event-specific messages: order created; receipt submitted/rejected/approved; preparing; shipped (carrier + tracking); delivered; canceled.
- Use factual past-tense for completed transitions; avoid claiming dispatch at approval time. If notification delivery fails, surface to operators; do not mutate order state to simulate a sent message.
- Outbox/queue retry and Telegram at-least-once semantics remain authoritative.

### Customer support and help

- **Direct chat** is the chosen MVP support UX: present **«💬 گفت‌وگو با پشتیبانی»** opening a valid configured Telegram `t.me` username/link in Telegram. If configured support contact cannot be safely turned into a Telegram URL, show the existing text contact rather than constructing an invalid or unsafe link. Do not build ticketing/live agent software.
- **📖 راهنما** is adjacent to support on the home menu. Help covers: browse/search → choose variant → cart/address → confirm order → card payment → select order → submit receipt → manual review → follow statuses/tracking. Offer shortcuts home, catalog, cart, own orders, support.
- The guide can also be reached via `/help`. Keep it compact and scannable.

### Empty, error and waiting states

- Empty cart: «سبد خرید شما خالی است» plus **«🌰 مشاهده محصولات»**.
- Empty orders: respectful no-order state plus **«🌰 مشاهده محصولات»**.
- Search no results: suggest another keyword plus **«🔎 جستجوی دوباره»** / catalog.
- Expired form or stale preview: clearly explain re-entry path without losing persisted order/receipt state.
- Validation errors explain the specific fix; no raw framework errors, technical stack traces, secrets or unexplained codes to customers.
- Avoid ungrounded delivery estimates, postal fee promises and "successful payment" for unverified receipts.

## 4. Execution plan — three independent UX polish phases

These are **UX-1 / UX-2 / UX-3 sub-phases**, not a renumbering/replacement of the approved six delivery phases. Use scoped branches, focused PRs, lean essential checks and Persian completion reports. A phase is complete only after its own acceptance criteria and green CI; then merge into `master` per [execution agreement](09-execution-agreement.md). Existing Phase 6 PR #36 must be reconciled to avoid duplicate help implementation or overlapping edits.

### UX-1 — Brand, entry point and navigation

**Scope**
- Replace `/start` greeting with the exact approved startup copy; retain existing customer/session semantics.
- Main keyboard arrangement including «📖 راهنما» beside «☎️ پشتیبانی»; reconcile current PR #36 help work.
- Introduce safe, valid direct Telegram support-chat button and graceful fallback for non-link contacts.
- Standardize key «⬅️ بازگشت», «🏠 منوی اصلی», button labels, helpful guide shortcuts and restrained emoji usage.

**Acceptance**
- `/start`, help callback and `/help` show intended text and working actions in private chat.
- Support opens a configured legitimate Telegram account or displays fallback contact; no malformed links.
- Core screens have clear navigation with no accidental checkout/receipt state corruption.
- Focused bot/keyboard tests + Pint/CI pass; real mobile Telegram smoke remains a separate Phase 6 release gate.

### UX-2 — Catalog discovery and cart readability

**Scope**
- Refine paginated catalog/product message layout and image handling to avoid chat flooding.
- Make product attributes/variants/prices legible and actionable; normalize browse/search/gallery navigation and empty states.
- Clarify cart amounts, quantities and primary checkout action; concise add/update feedback.

**Acceptance**
- Browsing a page does not send an uncontrolled gallery/message wall; previous/next/return buttons lead to the intended place.
- Products with/without media remain readable; unavailable items cannot be ordered via stale buttons.
- Cart quantities and IRR-backed toman display remain correct; unchanged monetary and ownership invariants.
- Lean targeted tests + CI green; mobile message-volume/RTL review on real Telegram during Phase 6.

### UX-3 — Checkout, receipts, order status and final customer-flow polish

**Scope**
- Polish address/quote/confirmation and payment instruction formatting, selected-order receipt UX and resubmission path.
- Normalize own-order detail and transactional notification text for `under_review`, `approved`, `preparing`, `shipped`, etc.
- Add recovery actions for empty/error/expired states, reduce duplicate chat messages without risking financial state.
- Exercise a realistic buyer journey and accessibility/readability checks on Telegram mobile in the Phase 6 Gate B environment.

**Acceptance**
- Test the actual full purchase/receipt/rejection/resubmission/approval/preparation/shipping/tracking journey when controlled environment access is available, and record evidence; do not mark live acceptance as passed from mocks.
- Approval and preparation are visually/semantically distinct, and upload is never described as payment success.
- No leaks of other customers' orders or receipts; no regression in idempotence, amounts, historical snapshots or notifications.
- Focused automated checks + CI green **and** required phase/Phase 6 live evidence before declaring complete.

## 5. Delivery and release constraints

- UX polish is **not** permission to add inventory quantities, a payment gateway, Telegram Mini App, a customer website, support ticketing, marketing or new business workflows.
- Keep backend Actions/DB schema/security semantics intact whenever possible; start with messages, inline keyboard organization and minimal support-link handling.
- Avoid broad regression suites: target core customer flow and highest-risk payment/privacy logic, plus existing PHPUnit, Pint, Composer/Vite checks.
- Do not merge an **incomplete** UX phase as completed; do not conflate UX PRs or a docs-only PR with **Phase 6 launch readiness**. The staging MySQL/Redis/Telegram, privacy, backup/restore, rollback and owner go/no-go remain mandatory.
- After each UX phase report in Persian: scope delivered, tests, PR/merge SHA, untested risks, next UX phase.

**Reference code (at creation):** `app/Integrations/Telegram/StorefrontBot.php`, `CatalogMessages.php`, `CartCheckoutMessages.php`, `ReceiptBot.php`, `CustomerOrdersBot.php`, `TelegramNotificationText.php`; update this guideline if user-approved copy/flows change.
