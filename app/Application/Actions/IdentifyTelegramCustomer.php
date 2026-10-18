<?php

namespace App\Application\Actions;

use App\Models\Customer;

class IdentifyTelegramCustomer
{
    public function execute(int $userId, int $chatId, string $displayName): Customer
    {
        return Customer::query()->updateOrCreate(
            ['telegram_user_id' => $userId],
            [
                'telegram_chat_id' => $chatId,
                'display_name' => mb_substr(trim($displayName), 0, 220),
            ],
        );
    }
}
