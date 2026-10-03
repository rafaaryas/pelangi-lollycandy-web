<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    public const TYPES = ['purchase_in', 'production_in', 'production_out', 'sale_out', 'adjustment_in', 'adjustment_out'];

    protected $fillable = ['movement_type', 'quantity', 'unit', 'movement_date', 'notes'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'movement_date' => 'datetime'];
    }

    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
