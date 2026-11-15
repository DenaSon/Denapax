# Payment rollout — manual receipt first, Zarinpal env-gated

**Decision: 2026-11-16.** While the merchant account is pending activation, card-to-card transfer with a privately uploaded receipt and staff bank verification is the **primary customer payment path**. The existing Zarinpal v4 integration stays in the code and is activated only by deployment environment configuration after live merchant verification. This document describes intended behavior; a GitHub merge does not itself deploy or certify production.

## Customer and staff behavior

- With `ZARINPAL_ENABLED=false` (the safe default), the order payment page shows the exact **toman** amount, card number and card holder from the order snapshot, and a directly visible receipt-upload form. No disabled-gateway notice or "alternative method" label is shown.
- The customer transfers the exact amount, uploads a JPG/PNG/WebP image (up to 8 MB) and sees **رسید دریافت شد؛ در انتظار بررسی**. Receipt media stays on the private `receipts` disk and only the owning customer / authorized staff can view it.
- Staff go to `/admin/payments`; `payments.view` sees pending receipts and `payments.review` can approve after **checking the actual bank deposit**, or reject with a reason. Approval sets payment to `approved` and order to `confirmed`; rejection allows a corrected receipt. Receipt images alone are not bank payment evidence.
- With `ZARINPAL_ENABLED=true` **and a valid merchant ID**, Zarinpal is automatically the prominent primary CTA; manual receipt transfer remains a collapsible fallback. No source code edit or database migration is required to switch priority.
- An **unresolved historic gateway attempt** continues to block card-to-card and new online attempts even if the gateway is subsequently disabled. Operator bank reconciliation must clear that state; switching the feature flag is not an authorization to charge again.
- Missing or invalid store card instructions must be fixed in admin settings: checkout refuses to create a new order with unusable transfer instructions.

## Runflare / environment operation

Keep the following in the deployment environment while Zarinpal onboarding is pending:

```env
ZARINPAL_ENABLED=false
ZARINPAL_MERCHANT_ID=
ZARINPAL_SANDBOX=false
```

Ensure an actual 16-digit destination card and cardholder name are configured in `/admin/settings`, shipping and prices have been verified, and an authorized staff reviewer is available. Customer checkout still requires **working SMS OTP**; `CUSTOMER_SMS_DRIVER=disabled` does not allow real customers to log in. Configure a secure production SMS transport separately.

After merchant activation and controlled validation in staging, configure the **merchant ID** (not a generic SMS/API token), confirm IRR amount semantics, provider API and HTTPS callback, and complete one authorized low-value payment plus bank settlement reconciliation. Then change the deployment environment to:

```env
ZARINPAL_ENABLED=true
ZARINPAL_MERCHANT_ID=<merchant-uuid-from-secure-env>
ZARINPAL_SANDBOX=false
```

Replace the placeholder with the real merchant UUID **only in secret/deployment management**, never in GitHub or documentation. Redeploy / refresh Laravel's config cache as appropriate for Runflare, then verify the online-primary UI and callback. Keep the manual fallback available. Turning on the flag without a valid ID leaves the gateway effectively disabled.

## Safety / verification

- Do not use `migrate:fresh` or delete receipts to change payment priority.
- Do not automatically approve a receipt after upload; match exact bank credit and amount manually.
- Verify manual submit, admin approve/reject and receipt resubmit, customer ownership, private-media access, disabled gateway returning a validation error, enabled gateway still primary, and unresolved-attempt blocking.
- A passing GitHub CI job does **not** constitute testing on production MySQL, real SMS delivery, Zarinpal settlement or backup/restore. Follow the existing [release readiness gates](38-web-phase4-release-readiness.md).
