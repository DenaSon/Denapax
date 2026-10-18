<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramSession extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'customer_id';

    public $incrementing = false;

    protected $fillable = ['customer_id', 'state', 'context', 'expires_at'];

    protected function casts(): array
    {
        return ['context' => 'array', 'expires_at' => 'datetime', 'updated_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
