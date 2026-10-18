# UX-3 — Telegram catalog, gallery and cart UX handoff

**Tracking:** [Issue #40](https://github.com/DenaSon/Denapax/issues/40) · [Implementation PR #45](https://github.com/DenaSon/Denapax/pull/45) · [Approved UX guideline](telegram-ui-ux-guidelines.md).  
**Scope:** UX polish over existing approved MVP; not the original backend Phase 3, a Mini App or a Phase 6 launch approval. Check GitHub PR for merge status and latest CI.

## Delivered customer UX

- Catalog or search result pages now use **one short bot message + inline keyboard per five products**, not five unsolicited product image/text messages plus a sixth navigation message. Product rows show a cropped title, *starting price of currently available variants in toman*, and a detail callback; category/search page callbacks are retained.
- Catalog queries eager-load available-variant prices (no per-row variant SQL queries); listing avoids loading galleries/features until the user requests detail.
- Selecting a product opens its focus view: image when present, short summary/description/features, available variants with weights/units and prices, «🛒 افزودن», gallery, optional variant images, cart/home/catalog navigation. If all detail text fits a Telegram-safe photo caption, **one message** carries both photo and buttons. Longer detail uses photo followed by a full text/keyboard message rather than truncating the actionable information.
- Gallery images and variant photos remain **on demand** with previous/next and parent-detail navigation; missing images and stale/hidden products provide catalog/home recovery rather than trapping the customer. Business-side variant availability and order validation remain authoritative.
- Cart now shows up to **five lines per message**, stable `id` ordering, numbered per-row `➕`/`➖`/«🗑️ حذف» controls, **unit price × quantity = line total**, cart-wide item count and **goods subtotal**, and an explicit note that shipping is calculated at checkout.
- Cart `cart:page:N` callbacks are customer-scoped and clamped to actual pages; missing/empty cart provides product/home shortcuts, while checkout and continue-shopping remain immediately accessible. Existing `ManageCart` and `CreateOrder` validation, ownership and idempotence are unchanged.
- Existing UX-2 button style semantics are reused conservatively; destructive actions use `danger`, checkout a success action, catalog discovery `primary`, and navigation defaults to neutral.

## Validation and limits

- Focused FakeNutgram checks: six products yield one result message per page with pagination and prices; available variant only (unavailable variant and stale inactive products excluded); empty search recovers; cart with six variants paginates with unchanged quantities/subtotal; empty cart recovers to catalog.
- Always check [PR #45](https://github.com/DenaSon/Denapax/pull/45) and its latest CI run for actual green/merge proof. Static/mocked feature checks **do not prove** Telegram iOS/Android message layouts, RTL, carrier integration, real media URLs or upload flows.
- **Unverified**: actual Telegram phone rendering (including photos, long captions, gallery paging), old client/theme behavior, actual MySQL/Redis environment and complete purchase cycle. Record these as part of [Phase 6 Gate B #24](https://github.com/DenaSon/Denapax/issues/24). The primary release Gate A/B/C and owner go/no-go remain open.

## Next work and safe integration

- [UX-4 #41](https://github.com/DenaSon/Denapax/issues/41): checkout/address/receipt/payment states, order status and notification UX. Keep quote snapshots, manual approval and receipt privacy invariants.
- Draft [Phase 6 PR #36](https://github.com/DenaSon/Denapax/pull/36) is based on older code. Before it is eventually merged, reconcile all overlapping bot and test files with latest `master`, keeping UX-1/2/3 improvements and current CI coverage. No stale overwrite.
