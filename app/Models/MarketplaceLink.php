<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceLink extends Model
{
    protected $fillable = ['platform', 'url', 'label', 'is_active'];
}
