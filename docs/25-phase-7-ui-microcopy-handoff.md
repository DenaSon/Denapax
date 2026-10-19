# Denapax — Post-MVP Phase 7: UI & Microcopy Handoff

**Tracking:** [Phase 7 #57](https://github.com/DenaSon/Denapax/issues/57) · [Sprint #56](https://github.com/DenaSon/Denapax/issues/56) · [Scope freeze](24-post-mvp-sprint-1.md).  
**Owner-approved scope:** seven findings, PMVP-002 / 003 / 005 / 007 / 008 / 010 / 014.

## Changes delivered in the phase branch

- **Cart (002, 003, 010):** compact item + variant, line arithmetic `quantity × unit price = line total`, subtotal emphasized and shipping note retained; omit page counter if there is only one page. Reword the existing quantity buttons to **«افزایش +۱» / «کاهش -۱»**, and the catalog-return button to **«🛍️ افزودن محصول دیگر»**; callback data and checkout behavior unchanged. Deliberately keep the current **five-line** pagination limit for Phase 8's approved **nine-line** change.
- **Shared formatting (005):** `App\Support\TelegramHtml` escapes user/operator-provided strings and generates minimal safe bold/quote fragments. Important totals, statuses, labels and product titles use Telegram native `ParseMode::HTML` where modified.
- **Payment and order confirmation (007, 008):** compact hierarchy for confirmation, status, goods/shipping/total, card snapshot and receipt instructions. Use an affirmative but accurate note: **«پس از بررسی و تأیید پرداخت، سفارش شما نهایی شده و در صف آماده‌سازی قرار می‌گیرد.»** Receipt submission remains **pending manual independent review**. Both synchronous bot replies and queued customer notices are aligned, including receipt-received wording. No card-copy button until Phase 8.
- **Product text (014):** skip redundant description when it matches the summary after whitespace normalization, highlight product/section names and simplify variants to their merchant-friendly titles instead of duplicate technical weights. Existing Spatie image and gallery behavior remains unchanged; Phase 8 will investigate the reported missing primary image.

## Guardrails

- No changes to database schema, shipping arithmetic, card payment/receipt approval, stock flags, customer ownership, callback identifiers, Telegram webhook or order events.
- Strings interpolated into HTML are escaped, notably customer address and merchant product text/cardholder details. Public product media code is intentionally unchanged.
- Keep the **ten Phase 8 findings** PMVP-001/004/006/009/011/012/013/015/016/017 in [#58](https://github.com/DenaSon/Denapax/issues/58); do not treat this phase as completing them.
- The owner requested internal Telegram group alerts **after the currently open work**, targeting one configured group ID. No design/implementation of group alerts is part of Phase 7 or 8.

## Essential verification

- Focused existing feature tests cover cart, product details, order replay, receipt selection, ownership and transactional notifications.
- Additional assertions confirm safe HTML escaping, visual totals/button wording, no duplicate description, and positive receipt confirmation.
- Check PHPUnit/Pint, Composer audit/validate and npm build in the phase PR CI. Note that CI's FakeNutgram is not real iOS/Android rendering; a live smoke after deployment remains necessary.

**Implementation PR:** [#61](https://github.com/DenaSon/Denapax/pull/61). Check [Phase 7 Issue #57](https://github.com/DenaSon/Denapax/issues/57) for verified merge SHA / CI closeout after acceptance; the document alone is not a claim of production deployment.
