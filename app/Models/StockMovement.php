<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
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

    public function scopeWithSourceDocument(Builder $query): Builder
    {
        return $query->with(['source' => fn (MorphTo $source) => $source->morphWith([
            PurchaseDetail::class => ['purchase'],
            SaleDetail::class => ['sale'],
            ProductionMaterial::class => ['production'],
            ProductionResult::class => ['production'],
        ])]);
    }

    public function getReferenceAttribute(): string
    {
        return match (true) {
            $this->source instanceof PurchaseDetail => $this->source->purchase?->reference_number,
            $this->source instanceof SaleDetail => $this->source->sale?->invoice_number,
            $this->source instanceof ProductionMaterial, $this->source instanceof ProductionResult => $this->source->production?->production_number,
            default => null,
        } ?? 'Penyesuaian stok';
    }
}
