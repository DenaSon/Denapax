<?php

use App\Integrations\Telegram\StorefrontBot;
use SergiX44\Nutgram\Nutgram;

/** @var Nutgram $bot */
$bot->onMessage(function (Nutgram $bot): void {
    app(StorefrontBot::class)->onMessage($bot);
});

$bot->onCallbackQuery(function (Nutgram $bot): void {
    app(StorefrontBot::class)->onCallback($bot);
});
