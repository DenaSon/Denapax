<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReceipt extends Model
{
    protected $fillable = [
        'order_id', 'attempt_number', 'telegram_file_id', 'storage_path',
        'status', 'rejection_reason', 'reviewed_by', 'submitted_at', 'reviewed_at',
    ];

    protected $hidden = ['storage_path', 'telegram_file_id'];

    protected function casts(): array
    {
        return ['attempt_number' => 'integer', 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
