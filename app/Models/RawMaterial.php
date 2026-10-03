<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RawMaterial extends Model
{
    protected $fillable = ['name', 'code', 'unit', 'current_stock', 'minimum_stock', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['current_stock' => 'decimal:3', 'minimum_stock' => 'decimal:3', 'is_active' => 'boolean'];
    }

    public function purchaseDetails(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function productionMaterials(): HasMany
    {
        return $this->hasMany(ProductionMaterial::class);
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'stockable');
    }
}
