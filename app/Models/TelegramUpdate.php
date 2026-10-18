<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramUpdate extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'update_id';

    public $incrementing = false;

    protected $fillable = ['update_id', 'status', 'received_at', 'started_at', 'processed_at', 'error_summary'];

    protected function casts(): array
    {
        return [
            'update_id' => 'integer', 'received_at' => 'datetime',
            'started_at' => 'datetime', 'processed_at' => 'datetime',
        ];
    }
}
