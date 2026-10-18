# UX-1 — Dena brand welcome, help, navigation & direct Telegram support

**Work item:** [UX-1 Issue #38](https://github.com/DenaSon/Denapax/issues/38)  
**Implementation:** [PR #43](https://github.com/DenaSon/Denapax/pull/43); check the PR itself for actual merge status.  
**Automated acceptance:** [GitHub Actions run 39167668420 — SUCCESS](https://github.com/DenaSon/Denapax/actions/runs/39167668420) at pre-handoff commit `4e16c3be6f124d7e2c5ceea9d838ac87394c2a65` (PHPUnit, Composer validation, Pint, Vite). A subsequent documentation-only commit requires its own green CI before merge.  
**Source of truth:** [Telegram Brand & UX Guide v1.1](telegram-ui-ux-guidelines.md).  
**Live Telegram verification:** **NOT performed**. Still required by Phase 6 [Gate B #24](https://github.com/DenaSon/Denapax/issues/24); this code slice does not approve launch.

## Delivered code and behavior

- Exact owner-approved **/start** text:
  ```text
  🏔️ دنا؛ طعم اصالت، عطر طبیعت

  به دنیای خوش‌طعم سوغات و خشکبار دنا خوش آمدید. 🌿

  🌰 برای شروع، از منوی زیر انتخاب کنید.
  ```
- Main inline keyboard retains existing browse/search, addresses, receipt, cart and own orders; **📖 راهنما** and **☎️ پشتیبانی** sit on the same final row.
- Reuses the working help handler and wording from Phase 6 draft [PR #36](https://github.com/DenaSon/Denapax/pull/36): `/help` and `menu:help`; help includes browse, cart, checkout, order selection before receipt, human payment review and tracking, plus catalog/cart/orders/support/home shortcuts.
- Safe, deliberately narrow `SupportContactLink`: recognize a configured explicit Telegram `@username` or direct `t.me/username` URL; present the customer a **«💬 گفت‌وگو با پشتیبانی»** URL button with normalized `https://t.me/<username>`. Do not generate link buttons from phone numbers, generic prose, invite links, arbitrary domains, URL queries or injected redirects. Preserve plain-text contact fallback and help/home navigation.
- Admin settings help text documents how staff should configure Telegram username or URL. No new DB field or migration.
- Improve **main-menu return** and back affordances across catalog/category/list/detail/gallery, address list, receipt selection/detail and own-order list/detail; invalid callback includes home recovery. Existing cart/checkout already provide some return-to-cart/catalog actions; their **deeper navigation and checkout copy** are reserved for UX-3/UX-4.
- Existing `/start` and `/cancel` conversation reset, order and payment state machine, receipt privacy and customer ownership remain unchanged. Returning to the menu via inline callback deliberately does **not** implicitly cancel a receipt/address flow.

## Validation boundary

Essential FakeNutgram checks cover the canonical greeting and home button layout, `/help` and help callback, Telegram username support URL, phone/text fallback and invalid URL allowlist. Existing feature tests cover core payments/checkout/webhook/permissions. Do not claim Telegram device screenshot verification, private-chat UX on a live bot, webhook connectivity, actual MySQL/Redis queue sends, or backup/restore.

## Operating configuration

In `/admin/settings`, set **«ارتباط با پشتیبانی»** to a real support account `@username`, `t.me/username` or `https://t.me/username`; the customer sees the direct link button. An ordinary phone number or descriptive text remains display-only. Avoid placeholders in production. A Telegram bot cannot guarantee opening an account that was deleted/renamed after configuration; operators must test the configured username.

## Phase boundary and next

- **Next UX slice: [UX-2 #39](https://github.com/DenaSon/Denapax/issues/39)** — native Telegram `primary`, `success`, `danger` button style rules and emoji/icon consistency; **no** arbitrary HEX or mandatory Premium emoji.
- **UX-3 #40:** catalog/card/gallery/cart message volume and cart representation.
- **UX-4 #41:** payment/receipt/fulfillment states and full UX journey.
- **Release Phase 6** remains open on #11/#24/#25. All external/live proof and security/deployment gates stay separate.

**Overlapping draft PR warning:** Draft Phase 6 PR #36 contains older `StorefrontBot.php` / `TelegramBotMenuTest.php` help edits. Before that PR can merge, rebase/reconcile it with UX-1 from `master` and retain the newer greeting, support-link and keyboard tests. Never merge a stale overwrite of UX-1 or describe PR #36 as launch-ready.
