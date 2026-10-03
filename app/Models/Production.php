<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Production extends Model
{
    protected $fillable = ['production_number', 'production_date', 'status', 'notes'];

    protected function casts(): array
    {
        return ['production_date' => 'date'];
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ProductionMaterial::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ProductionResult::class);
    }
}
