<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminGroupAlertLog extends Model
{
    protected $fillable = [
        'outbox_event_id', 'chat_id', 'status', 'attempts',
        'telegram_message_id', 'last_error', 'claimed_at',
        'next_attempt_at', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'claimed_at' => 'datetime',
            'next_attempt_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }
}
