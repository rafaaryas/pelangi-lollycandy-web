<?php

namespace App\Services;

use App\Models\RawMaterial;
use App\Models\StockMovement;
use Illuminate\Support\Collection;

class RawMaterialStockReport
{
    private const INCOMING = ['purchase_in', 'adjustment_in'];

    public function summary(string $from, string $to): Collection
    {
        $materials = RawMaterial::query()->orderBy('name')->get();
        $movements = StockMovement::query()->where('stockable_type', RawMaterial::class)
            ->where('movement_date', '>=', $from.' 00:00:00')->get()->groupBy('stockable_id');

        return $materials->map(function (RawMaterial $material) use ($movements, $to): array {
            $all = $movements->get($material->id, collect());
            $period = $all->filter(fn (StockMovement $movement) => $movement->movement_date->toDateString() <= $to);
            $incoming = (float) $period->whereIn('movement_type', self::INCOMING)->sum('quantity');
            $outgoing = (float) $period->whereNotIn('movement_type', self::INCOMING)->sum('quantity');
            $afterStartNet = (float) $all->sum(fn (StockMovement $movement) => in_array($movement->movement_type, self::INCOMING, true)
                ? (float) $movement->quantity : -(float) $movement->quantity);
            $opening = (float) $material->current_stock - $afterStartNet;

            return [
                'material' => $material,
                'opening' => $opening,
                'incoming' => $incoming,
                'used' => $outgoing,
                'closing' => $opening + $incoming - $outgoing,
            ];
        });
    }

    public function details(string $from, string $to)
    {
        return StockMovement::query()->with(['stockable', 'source'])
            ->where('stockable_type', RawMaterial::class)
            ->whereBetween('movement_date', [$from.' 00:00:00', $to.' 23:59:59'])
            ->orderByDesc('movement_date')->orderByDesc('id')->paginate(25)->withQueryString();
    }
}
