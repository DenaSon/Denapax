<?php

return [
    // Requires verified merchant onboarding before any production activation.
    'enabled' => (bool) env('ZARINPAL_ENABLED', false),
    'merchant_id' => env('ZARINPAL_MERCHANT_ID', ''),
    'sandbox' => (bool) env('ZARINPAL_SANDBOX', false),
];
