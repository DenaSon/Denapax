<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    protected $fillable = [
        'telegram_user_id', 'telegram_chat_id', 'display_name', 'full_name', 'mobile',
    ];

    protected function casts(): array
    {
        return ['telegram_user_id' => 'integer', 'telegram_chat_id' => 'integer'];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function telegramSession(): HasOne
    {
        return $this->hasOne(TelegramSession::class);
    }
}
