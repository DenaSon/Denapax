# Denapax — Telegram UI/UX & Brand Experience Guidelines v1.1

**Status:** Brand welcome and basic UX direction approved by owner on 2026-10-19; **four UX sub-phases** approved afterward. Implementation is **planned**, except features explicitly identified as existing below. Track [UX-1 #38](https://github.com/DenaSon/Denapax/issues/38), [UX-2 #39](https://github.com/DenaSon/Denapax/issues/39), [UX-3 #40](https://github.com/DenaSon/Denapax/issues/40) and [UX-4 #41](https://github.com/DenaSon/Denapax/issues/41).  
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
- Telegram Bot API permits **three native button `style` presets**: `primary` (blue), `success` (green), `danger` (red). **Omit** `style` for neutral client-default buttons. The client/theme controls actual rendering, and the bot **cannot** set arbitrary HEX button colors, fonts, CSS or chat backgrounds.
- Optional native `icon_custom_emoji_id` is **eligibility-gated** (qualifying Fragment username or Telegram Premium bot owner under Telegram's conditions). **Normal Unicode emoji labels are the default**, require no eligibility, and remain the fallback on older clients or if custom icons are unavailable. Custom emoji IDs/eligibility must be verified before enabling.
- Current `composer.lock` uses **Nutgram 4.50.3**; its `InlineKeyboardButton::make(..., style: ..., icon_custom_emoji_id: ...)` supports these fields. Actual rendering, RTL icon placement and legacy client fallback **require real device checks**. See [official Telegram buttons documentation](https://core.telegram.org/api/bots/buttons).
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

## 4. Execution plan — four independent UX polish phases

**Product decision:** four **UX-1 through UX-4** sub-phases, not a seventh through tenth core delivery phase and not a replacement for the existing six main phases. UX-2 specifically owns native Telegram button colors/icon policy so presentation stays consistent across later UX work.

**Tracking:** [UX-1 #38](https://github.com/DenaSon/Denapax/issues/38) → [UX-2 #39](https://github.com/DenaSon/Denapax/issues/39) → [UX-3 #40](https://github.com/DenaSon/Denapax/issues/40) → [UX-4 #41](https://github.com/DenaSon/Denapax/issues/41).

**Working policy:** dedicated branch and small, reviewable PR per phase; implement the issue checklist with minimal, meaningful automated checks; merge completed phase to `master` **only after its own acceptance and green CI**; link the merge SHA and send a short Persian report. Do not claim production release from these PRs. Coordinate overlapping work already on draft [Phase 6 PR #36](https://github.com/DenaSon/Denapax/pull/36); especially reuse the existing help implementation instead of duplicating it.

### UX-1 — Brand welcome, menu, navigation and support ([Issue #38](https://github.com/DenaSon/Denapax/issues/38))

**Implementation tasks**
1. Replace current `/start` message with the **exact approved three-line greeting** in §2 (preserve the customer's reset rules).
2. Finalize compact home Inline Keyboard: catalog/search, addresses, receipt, cart/orders and **«📖 راهنما» next to «☎️ پشتیبانی»**.
3. Reuse `/help` + `menu:help` from PR #36 and provide helpful shopping/payment/tracking shortcuts (no duplicate menu handlers).
4. Provide **«💬 گفت‌وگو با پشتیبانی»** as a direct, safe Telegram URL for an eligible configured username/`t.me` link. For phone/free-text support contact, keep a plain-text fallback; reject invalid/malicious links.
5. Normalize important navigation `⬅️ بازگشت`/`🏠 منوی اصلی` and copy consistency in major screens, without resetting an in-progress order/receipt state unintentionally.

**Acceptance:** exact welcome, working help and support actions, functional return paths, session safety; essential Nutgram/keyboard/security tests, Pint, Composer and Vite CI green. Actual Telegram mobile smoke to be recorded in existing Phase 6 Gate B.

**Handoff to UX-2:** keyboard architecture and action labels settled; no native color/icon overhaul yet.

### UX-2 — Native Telegram styles, icons and visual language ([Issue #39](https://github.com/DenaSon/Denapax/issues/39))

**Implementation tasks**
1. Establish a centralized minimal **button-token/style policy** and a consistent emoji vocabulary:
   - **`primary` (blue):** key navigation/discovery, e.g. 🌰 محصولات where an emphasized entry action is helpful.
   - **`success` (green):** proceed/confirm order, positive business actions such as ✅ تأیید سفارش.
   - **`danger` (red):** destructive actions only, e.g. 🗑️ حذف از سبد (with suitable guard where needed).
   - **client-default:** پشتیبانی، راهنما، بازگشت، منوی اصلی and noncritical secondary actions.
2. Apply `style` selectively to existing Inline Keyboard buttons without altering callbacks, ownership checks, checkout/payment transitions or server semantics. Do **not** paint every button.
3. Use clear Persian labels and regular Unicode emojis across main menu, catalog, cart and checkout; avoid emoji-only actions where meaning is ambiguous.
4. Evaluate `icon_custom_emoji_id` only after verifying bot eligibility and suitable icons; optional feature, **no paid dependency** and always a plain emoji/text fallback.
5. Verify **RTL/Persian legibility** and icon alignment, light/dark appearance and older Telegram-client behavior. Preserve native rendering limitations and avoid invented HEX customization or Mini App requirements.

**Acceptance:** button styles serialize correctly with Nutgram, callbacks/URLs remain intact, neutral actions remain neutral, standard emoji fallback works without Premium, essential automated checks and CI green. Device/theme acceptance is recorded during Phase 6, not inferred from CI.

**Handoff to UX-3:** reusable styles/icons used consistently, so shopping screens can focus on layout and flow.

### UX-3 — Catalog, product discovery, gallery and cart ([Issue #40](https://github.com/DenaSon/Denapax/issues/40))

**Implementation tasks**
1. Refine catalog page output to **avoid a wall of multiple photo/messages**, using a focused product view and concise pagination/previous/next as supported by Telegram message constraints.
2. Improve title, short description, product-specific features, variant choices, price in **toman**, availability and distinct «🛒 افزودن به سبد» actions.
3. Keep gallery and variant images discoverable, including safe fallback for missing media. Avoid duplicate messages when feasible; only edit prior messages where Telegram APIs allow it safely.
4. Normalize catalog/search/gallery navigation and empty-results recovery; ensure stale buttons cannot expose inactive/unavailable products.
5. Polish cart: selected product/variant, quantity, line totals, **goods subtotal**, intuitive +/-/delete, «✅ ادامه و ثبت سفارش», continue-shopping and empty-cart recovery.
6. Apply the native styles/icon vocabulary from UX-2 with restraint.

**Acceptance:** understandable browsing without excessive message flooding, working pagination/prev/next, price and quantities accurate, no broken unavailable-item/ownership boundaries, lean targeted tests + CI green; real Telegram page volume and RTL checks in Phase 6.

**Handoff to UX-4:** a readable cart reliably feeds the existing financial checkout.

### UX-4 — Checkout, manual receipt, orders, notifications and end-to-end UX ([Issue #41](https://github.com/DenaSon/Denapax/issues/41))

**Implementation tasks**
1. Format address selection, final quote and shipping breakdown clearly; show authoritative goods subtotal, shipping fee, total and recipient before confirm, with safe return/retry controls.
2. After order creation show number, total, card payment instructions and the accurate **«در انتظار پرداخت»** state; surface «🧾 ارسال رسید پرداخت» and «📦 مشاهده سفارش».
3. From the main «ارسال رسید» entry, first **choose the eligible owned order**; then show selected order number and amount and accept its private receipt image. Keep the exact order ownership and private-storage guarantees.
4. Clearly separate `under_review` (receipt received), `rejected` (reason + resubmit), `approved` payment/`confirmed` order, `preparing` (distinct staff transition), `shipped` (carrier + tracking), delivered/canceled.
5. Polish «سفارش‌های من» and eight existing transactional notification texts; use helpful next-step and recovery buttons for empty, invalid and expired states.
6. Review mobile Persian RTL/readability, duplicate-message noise, and state communication. When the **real** Phase 6 Gate B environment is available, execute and record purchase → receipt → reject/resubmit → approve → preparing → shipped → tracking and notification UX acceptance.

**Acceptance:** no false "paid" messaging on receipt upload; accurate state machine and safe recovery; critical authorization/privacy/idempotence/financial snapshot regressions pass with lean automated CI; real Telegram staging evidence remains mandatory for launch and for declaring full customer-flow acceptance.

### Cross-phase decision rules

- **Each UX issue contains the executable checklist**; this guide is the overarching specification. On implementation, link branch/PR, essential checks, screenshots/evidence (scrub PII), merge SHA and outstanding risks in its issue.
- Use existing Laravel Actions, Nutgram adapter messages and Livewire staff screens; no wholesale rewrites.
- Test small **behavioral** slices, not dozens of snapshots/color assertions. A real Telegram account/mobile screen review is essential for visual claims.
- Where Phase 6 external services are unavailable, do not merge or label a UX slice **complete** if its own acceptance requires evidence not yet captured; record the blocker. UX completion never substitutes for full launch Gates A/B/C.

## 5. Delivery and release constraints

- UX polish is **not** permission to add inventory quantities, a payment gateway, Telegram Mini App, a customer website, support ticketing, marketing or new business workflows.
- Keep backend Actions/DB schema/security semantics intact whenever possible; start with messages, inline keyboard organization and minimal support-link handling.
- Avoid broad regression suites: target core customer flow and highest-risk payment/privacy logic, plus existing PHPUnit, Pint, Composer/Vite checks.
- Do not merge an **incomplete** UX phase as completed; do not conflate UX PRs or a docs-only PR with **Phase 6 launch readiness**. The staging MySQL/Redis/Telegram, privacy, backup/restore, rollback and owner go/no-go remain mandatory.
- After each UX phase report in Persian: scope delivered, tests, PR/merge SHA, untested risks, next UX phase.

**Reference code (at creation):** `app/Integrations/Telegram/StorefrontBot.php`, `CatalogMessages.php`, `CartCheckoutMessages.php`, `ReceiptBot.php`, `CustomerOrdersBot.php`, `TelegramNotificationText.php`; update this guideline if user-approved copy/flows change.
