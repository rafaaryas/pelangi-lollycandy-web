<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionMaterial extends Model
{
    protected $fillable = ['production_id', 'raw_material_id', 'quantity_used', 'unit'];

    protected function casts(): array
    {
        return ['quantity_used' => 'decimal:3'];
    }

    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class);
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }
}
