<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    // Catalog product fields shown on homepage, product pages, and admin tables.
    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'price_from',
        'price_strike', 'badge', 'sku', 'weight_gram', 'flavor',
        'shopee_url', 'favorite_clicks', 'click_count',
        'published_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_active' => 'boolean',
            'click_count' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}
