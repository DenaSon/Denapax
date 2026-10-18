<?php

return [
    'token' => env('TELEGRAM_TOKEN'),
    // The dedicated webhook controller verifies the Bot API secret header
    // before Nutgram sees the payload; do not enable a second, differing check.
    'safe_mode' => false,
    'config' => [],
    'routes' => true,
    'mixins' => false,
    'namespace' => app_path('Telegram'),
    'log_channel' => env('TELEGRAM_LOG_CHANNEL', 'null'),
];
