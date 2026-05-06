<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    // Admin category fields: used for sidebar filters and product grouping.
    protected $fillable = ['name', 'slug', 'is_active'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
