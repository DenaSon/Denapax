<?php

return [
    // disabled by default; 'log' is restricted to local/testing.
    // 'webhook' sends JSON {mobile, message} with a Bearer token over HTTPS.
    'sms_driver' => env('CUSTOMER_SMS_DRIVER', 'disabled'),
    'sms_url' => env('CUSTOMER_SMS_URL', ''),
    'sms_token' => env('CUSTOMER_SMS_TOKEN', ''),
];
