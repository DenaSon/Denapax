<?php

return [
    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],
    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'smsir' => [
        'api_key' => env('SMSIR_API_KEY'),
        'verify_template_id' => env('SMSIR_VERIFY_TEMPLATE_ID'),
        'verify_parameter' => env('SMSIR_VERIFY_PARAMETER', 'HOURS'),
        'timeout' => (float) env('SMSIR_TIMEOUT', 8),
        'connect_timeout' => (float) env('SMSIR_CONNECT_TIMEOUT', 3),
    ],
    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
];
