<?php

return [
    // Disabled by default. Select 'mediana', 'smsir', or the HTTPS 'webhook' transport.
    // 'webhook' remains available for an explicitly configured HTTPS transport.
    'sms_driver' => env('CUSTOMER_SMS_DRIVER', 'disabled'),
    'sms_url' => env('CUSTOMER_SMS_URL', ''),
    'sms_token' => env('CUSTOMER_SMS_TOKEN', ''),

    // Shared OTP policy across providers; use config() at runtime for config:cache safety.
    'otp_length' => (int) env('CUSTOMER_OTP_LENGTH', 4),
    'otp_ttl_seconds' => (int) env('CUSTOMER_OTP_TTL', 120),
    'otp_max_attempts' => (int) env('CUSTOMER_OTP_MAX_ATTEMPTS', 3),
];
