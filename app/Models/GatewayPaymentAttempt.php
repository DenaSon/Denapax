<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatewayPaymentAttempt extends Model
{
    protected $fillable = [
        'order_id', 'provider', 'authority', 'reference_id', 'amount_irr', 'status',
        'last_error', 'verification_started_at', 'verification_token', 'verified_at',
        'inquiry_status', 'inquiry_checked_at', 'resolved_by', 'resolved_at', 'resolution_note',
    ];

    protected function casts(): array
    {
        return [
            'amount_irr' => 'integer',
            'verification_started_at' => 'datetime',
            'verified_at' => 'datetime',
            'inquiry_checked_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function blocksNewPayment(): bool
    {
        // An old timeout, error or age-based 'expired' flag is NOT proof of non-payment.
        return $this->status !== 'cleared_unpaid';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
