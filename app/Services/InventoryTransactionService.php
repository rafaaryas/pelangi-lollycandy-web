<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Production;
use App\Models\Purchase;
use App\Models\RawMaterial;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryTransactionService
{
    public function savePurchase(?Purchase $purchase, array $header, array $lines, bool $confirm = false): Purchase
    {
        return DB::transaction(function () use ($purchase, $header, $lines, $confirm): Purchase {
            $header['reference_number'] = $header['reference_number'] ?? $purchase?->reference_number ?? 'TMP-'.Str::uuid();
            $purchase = $purchase
                ? Purchase::query()->lockForUpdate()->findOrFail($purchase->id)
                : new Purchase;
            $this->ensureDraft($purchase->exists ? $purchase->status : 'draft');
            $purchase->fill($header + ['status' => 'draft']);
            $purchase->save();

            $purchase->details()->delete();
            $total = 0;
            foreach ($lines as $line) {
                $subtotal = round((float) $line['quantity'] * (float) $line['unit_price'], 2);
                $line['unit'] = RawMaterial::findOrFail($line['raw_material_id'])->unit;
                $purchase->details()->create($line + ['subtotal' => $subtotal]);
                $total += $subtotal;
            }
            $purchase->update(['total_amount' => round($total, 2)]);

            if (! $purchase->reference_number || str_starts_with($purchase->reference_number, 'TMP-')) {
                $purchase->update(['reference_number' => 'PB-'.now()->format('Y').'-'.str_pad((string) $purchase->id, 5, '0', STR_PAD_LEFT)]);
            }

            if ($confirm) {
                $this->applyPurchase($purchase);
            }

            return $purchase->fresh(['supplier', 'details.rawMaterial']);
        });
    }

    public function confirmPurchase(Purchase $purchase): Purchase
    {
        return DB::transaction(function () use ($purchase): Purchase {
            $purchase = Purchase::with('details')->lockForUpdate()->findOrFail($purchase->id);
            $this->applyPurchase($purchase);

            return $purchase->fresh(['supplier', 'details.rawMaterial']);
        });
    }

    private function applyPurchase(Purchase $purchase): void
    {
        $this->ensureDraft($purchase->status);
        if ($purchase->details->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Pembelian harus memiliki minimal satu bahan baku.']);
        }

        foreach ($purchase->details->sortBy('raw_material_id') as $detail) {
            $material = RawMaterial::query()->lockForUpdate()->findOrFail($detail->raw_material_id);
            $material->increment('current_stock', $detail->quantity);
            $this->movement($material, $detail, 'purchase_in', $detail->quantity, 'Stok masuk dari '.$purchase->reference_number, $purchase->purchase_date);
        }
        $purchase->update(['status' => 'confirmed']);
    }

    public function cancelPurchase(Purchase $purchase): Purchase
    {
        return DB::transaction(function () use ($purchase): Purchase {
            $purchase = Purchase::with('details')->lockForUpdate()->findOrFail($purchase->id);
            if ($purchase->status === 'cancelled') {
                throw ValidationException::withMessages(['status' => 'Pembelian ini sudah dibatalkan.']);
            }
            if ($purchase->status === 'confirmed') {
                foreach ($purchase->details->sortBy('raw_material_id') as $detail) {
                    $material = RawMaterial::query()->lockForUpdate()->findOrFail($detail->raw_material_id);
                    if ((float) $material->current_stock < (float) $detail->quantity) {
                        throw ValidationException::withMessages(['status' => "Pembatalan ditolak: stok {$material->name} sudah digunakan."]);
                    }
                    $material->decrement('current_stock', $detail->quantity);
                    $this->movement($material, $detail, 'adjustment_out', $detail->quantity, 'Pembatalan '.$purchase->reference_number, now());
                }
            }
            $purchase->update(['status' => 'cancelled']);

            return $purchase;
        });
    }

    public function saveProduction(?Production $production, array $header, array $materials, array $results, bool $confirm = false): Production
    {
        return DB::transaction(function () use ($production, $header, $materials, $results, $confirm): Production {
            $header['production_number'] = $header['production_number'] ?? $production?->production_number ?? 'TMP-'.Str::uuid();
            $production = $production
                ? Production::query()->lockForUpdate()->findOrFail($production->id)
                : new Production;
            $this->ensureDraft($production->exists ? $production->status : 'draft');
            $production->fill($header + ['status' => 'draft']);
            $production->save();
            $production->materials()->delete();
            $production->results()->delete();
            foreach ($materials as $line) {
                $line['unit'] = RawMaterial::findOrFail($line['raw_material_id'])->unit;
                $production->materials()->create($line);
            }
            foreach ($results as $line) {
                $production->results()->create($line);
            }
            if (! $production->production_number || str_starts_with($production->production_number, 'TMP-')) {
                $production->update(['production_number' => 'PRD-'.now()->format('Y').'-'.str_pad((string) $production->id, 5, '0', STR_PAD_LEFT)]);
            }
            if ($confirm) {
                $this->applyProduction($production);
            }

            return $production->fresh(['materials.rawMaterial', 'results.product']);
        });
    }

    public function confirmProduction(Production $production): Production
    {
        return DB::transaction(function () use ($production): Production {
            $production = Production::with(['materials', 'results'])->lockForUpdate()->findOrFail($production->id);
            $this->applyProduction($production);

            return $production->fresh(['materials.rawMaterial', 'results.product']);
        });
    }

    private function applyProduction(Production $production): void
    {
        $this->ensureDraft($production->status);
        if ($production->materials->isEmpty() || $production->results->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Produksi membutuhkan bahan baku dan minimal satu hasil produk.']);
        }
        foreach ($production->materials->sortBy('raw_material_id') as $line) {
            $material = RawMaterial::query()->lockForUpdate()->findOrFail($line->raw_material_id);
            if ((float) $material->current_stock < (float) $line->quantity_used) {
                throw ValidationException::withMessages(['items' => "Stok {$material->name} tidak mencukupi untuk produksi."]);
            }
            $material->decrement('current_stock', $line->quantity_used);
            $this->movement($material, $line, 'production_out', $line->quantity_used, 'Bahan produksi '.$production->production_number, $production->production_date);
        }
        foreach ($production->results->sortBy('product_id') as $line) {
            $product = Product::query()->lockForUpdate()->findOrFail($line->product_id);
            if ($product->stock_quantity === null) {
                throw ValidationException::withMessages(['items' => "Stok awal produk {$product->name} belum dicatat. Catat stok awal sebelum produksi."]);
            }
            $product->increment('stock_quantity', $line->quantity_produced);
            $this->movement($product, $line, 'production_in', $line->quantity_produced, 'Hasil produksi '.$production->production_number, $production->production_date);
        }
        $production->update(['status' => 'confirmed']);
    }

    public function cancelProduction(Production $production): Production
    {
        return DB::transaction(function () use ($production): Production {
            $production = Production::with(['materials', 'results'])->lockForUpdate()->findOrFail($production->id);
            if ($production->status === 'cancelled') {
                throw ValidationException::withMessages(['status' => 'Produksi ini sudah dibatalkan.']);
            }
            if ($production->status === 'confirmed') {
                foreach ($production->results->sortBy('product_id') as $line) {
                    $product = Product::query()->lockForUpdate()->findOrFail($line->product_id);
                    if ((float) ($product->stock_quantity ?? 0) < (float) $line->quantity_produced) {
                        throw ValidationException::withMessages(['status' => "Pembatalan ditolak: hasil {$product->name} sudah terjual atau digunakan."]);
                    }
                    $product->decrement('stock_quantity', $line->quantity_produced);
                    $this->movement($product, $line, 'adjustment_out', $line->quantity_produced, 'Pembatalan '.$production->production_number, now());
                }
                foreach ($production->materials->sortBy('raw_material_id') as $line) {
                    $material = RawMaterial::query()->lockForUpdate()->findOrFail($line->raw_material_id);
                    $material->increment('current_stock', $line->quantity_used);
                    $this->movement($material, $line, 'adjustment_in', $line->quantity_used, 'Pengembalian bahan dari pembatalan '.$production->production_number, now());
                }
            }
            $production->update(['status' => 'cancelled']);

            return $production;
        });
    }

    public function saveSale(?Sale $sale, array $header, array $lines, bool $confirm = false): Sale
    {
        return DB::transaction(function () use ($sale, $header, $lines, $confirm): Sale {
            $header['invoice_number'] = $header['invoice_number'] ?? $sale?->invoice_number ?? 'TMP-'.Str::uuid();
            $sale = $sale ? Sale::query()->lockForUpdate()->findOrFail($sale->id) : new Sale;
            $this->ensureDraft($sale->exists ? $sale->status : 'draft');
            $sale->fill($header + ['status' => 'draft']);
            $sale->save();
            $sale->details()->delete();
            $total = 0;
            foreach ($lines as $line) {
                $subtotal = round((float) $line['quantity'] * (float) $line['unit_price'], 2);
                $sale->details()->create($line + ['subtotal' => $subtotal]);
                $total += $subtotal;
            }
            $sale->update(['total_amount' => round($total, 2)]);
            if (! $sale->invoice_number || str_starts_with($sale->invoice_number, 'TMP-')) {
                $sale->update(['invoice_number' => 'PJ-'.now()->format('Y').'-'.str_pad((string) $sale->id, 5, '0', STR_PAD_LEFT)]);
            }
            if ($confirm) {
                $this->applySale($sale);
            }

            return $sale->fresh(['customer', 'details.product']);
        });
    }

    public function confirmSale(Sale $sale): Sale
    {
        return DB::transaction(function () use ($sale): Sale {
            $sale = Sale::with('details')->lockForUpdate()->findOrFail($sale->id);
            $this->applySale($sale);

            return $sale->fresh(['customer', 'details.product']);
        });
    }

    private function applySale(Sale $sale): void
    {
        $this->ensureDraft($sale->status);
        if ($sale->details->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Penjualan harus memiliki minimal satu produk.']);
        }
        foreach ($sale->details->sortBy('product_id') as $line) {
            $product = Product::query()->lockForUpdate()->findOrFail($line->product_id);
            if ($product->stock_quantity === null || (float) $product->stock_quantity < (float) $line->quantity) {
                throw ValidationException::withMessages(['items' => "Stok {$product->name} belum dicatat atau tidak mencukupi."]);
            }
            $product->decrement('stock_quantity', $line->quantity);
            $this->movement($product, $line, 'sale_out', $line->quantity, 'Penjualan '.$sale->invoice_number, $sale->sale_date);
        }
        $sale->update(['status' => 'confirmed']);
    }

    public function cancelSale(Sale $sale): Sale
    {
        return DB::transaction(function () use ($sale): Sale {
            $sale = Sale::with('details')->lockForUpdate()->findOrFail($sale->id);
            if ($sale->status === 'cancelled') {
                throw ValidationException::withMessages(['status' => 'Penjualan ini sudah dibatalkan.']);
            }
            if ($sale->status === 'confirmed') {
                foreach ($sale->details->sortBy('product_id') as $line) {
                    $product = Product::query()->lockForUpdate()->findOrFail($line->product_id);
                    if ($product->stock_quantity === null) {
                        throw ValidationException::withMessages(['status' => "Stok {$product->name} belum dicatat sehingga penjualan tidak dapat dibatalkan."]);
                    }
                    $product->increment('stock_quantity', $line->quantity);
                    $this->movement($product, $line, 'adjustment_in', $line->quantity, 'Pembatalan '.$sale->invoice_number, now());
                }
            }
            $sale->update(['status' => 'cancelled']);

            return $sale;
        });
    }

    private function movement(Model $stockable, Model $source, string $type, string|float $quantity, string $notes, mixed $date): void
    {
        $movement = $stockable->stockMovements()->make([
            'movement_type' => $type,
            'quantity' => abs((float) $quantity),
            'unit' => $stockable instanceof RawMaterial ? $stockable->unit : 'pcs',
            'movement_date' => $date,
            'notes' => $notes,
        ]);
        $movement->source()->associate($source);
        $movement->save();
    }

    private function ensureDraft(string $status): void
    {
        if ($status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Hanya transaksi draft yang dapat diubah atau dikonfirmasi.']);
        }
    }
}
