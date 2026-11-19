# Denapax — internal 31-province selector and checkout address polish

## Scope

- Uses a **self-contained** `App\Support\IranProvinces` list of 31 Iranian provinces (no external HTTP provider, Composer package, cities table, migrations or seeding).
- Delivery province is a native `<select>` with a required placeholder and proper label. City remains a customer-entered text field. All values continue to be persisted as **strings** in `customer_addresses` and order snapshots; other checkout quote/payment rules are untouched.
- `CustomerAddressInput`, `SaveCustomerAddress`, both the Livewire form and ordinary POST/PUT fallback accept only canonical provinces. The server revalidates province membership; forged province values cannot bypass the selector. Persian/Arabic yeh/kaf, zero-width joiners and extra whitespace are normalized to canonical labels.
- When editing older addresses, recognized spelling variants are selected automatically without rewriting stored values before save. An **unknown** legacy province stays visible in a marked option; the user must explicitly choose a recognized province before saving. City is never silently replaced.
- The form is divided into three compact sections (recipient, location, address details), has field-level errors, a distinct optional nickname, clear next-step copy, touch-sized controls and RTL/mobile layout. Native `<select>` remains fully usable without JavaScript; no custom select plugin required.
- The account-holder mobile is still prefilled **only as recipient mobile** and is editable. Login/OTP mobile remains independent.

## Checks

- Verify all 31 unique provinces, deterministic canonical labels, no bad province persisted through Livewire, native POST or direct service.
- Edit saved customer addresses with correct, Arabic-spelling and unknown legacy provinces; confirm original city, recipient, postcode and existing order snapshots remain unchanged until save.
- Test checkout return URL to the selected address, normal address-book save, validation errors, keyboard-select and mobile 320–430px widths in production.
- CI covers the server-side checks; after Deploy, perform real-device QA. No migration, extra ENV values or dependency installation.
