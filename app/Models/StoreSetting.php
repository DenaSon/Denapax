<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = ['store_name', 'support_contact', 'card_number', 'card_holder_name', 'updated_by'];
}
