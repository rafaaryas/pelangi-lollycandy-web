<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductClickLog extends Model
{
    protected $fillable = [
        'product_id',
        'target',
        'ip_address',
        'user_agent',
    ];
}
