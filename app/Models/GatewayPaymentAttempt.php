<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatewayPaymentAttempt extends Model
{
    protected $fillable = [
        'order_id', 'provider', 'authority', 'reference_id', 'amount_irr', 'status',
        'last_error', 'verification_started_at', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_irr' => 'integer',
            'verification_started_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
