<?php

return [
    // disabled by default. 'smsir' uses the configured approved Verify template.
    // 'webhook' remains available for an explicitly configured HTTPS transport.
    'sms_driver' => env('CUSTOMER_SMS_DRIVER', 'disabled'),
    'sms_url' => env('CUSTOMER_SMS_URL', ''),
    'sms_token' => env('CUSTOMER_SMS_TOKEN', ''),
];
