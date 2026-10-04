<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\ProductionMaterial;
use App\Models\ProductionResult;
use App\Models\Product;
use App\Models\Production;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\RawMaterial;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\InventoryTransactionService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoBusinessDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Data demo hanya boleh digunakan di lingkungan non-produksi.');
        }

        $products = Product::query()->where('is_active', true)->orderBy('id')->get();
        if ($products->isEmpty()) {
            throw new \RuntimeException('Isi produk asli terlebih dahulu sebelum menjalankan seeder demo.');
        }

        $supplierNames = ['Pemasok Manis Nusantara', 'Sumber Kemasan Ceria', 'Aroma Rasa Indonesia', 'Sentra Aksesori Permen'];
        $suppliers = collect($supplierNames)->map(fn (string $name, int $index) => Supplier::firstOrCreate(
            ['code' => sprintf('DEMO-SUP-%02d', $index + 1)],
            ['name' => $name, 'phone' => sprintf('081290010%03d', $index + 1), 'is_active' => true]
        ));

        $customerNames = ['Toko Ceria', 'Kedai Pelangi', 'Mira Andini', 'Toko Manis Jaya', 'Nadia Putri', 'Hampers Kita', 'Rani Pratama', 'Toko Bintang', 'Ayu Lestari', 'Kios Bahagia', 'Nusa Gift', 'Dina Maharani'];
        $customers = collect($customerNames)->map(fn (string $name, int $index) => Customer::firstOrCreate(
            ['phone' => sprintf('081290020%03d', $index + 1)],
            ['name' => $name, 'is_active' => true]
        ));

        $materialData = [
            ['Gula Pasir', 'kg', 8], ['Glucose Syrup', 'kg', 4], ['Pewarna Makanan', 'liter', 0.3],
            ['Perisa Buah', 'liter', 0.4], ['Stik Lolipop', 'pcs', 150], ['Plastik Kemasan', 'pcs', 120],
            ['Pita Kemasan', 'pcs', 70], ['Label Produk', 'pcs', 100],
        ];
        $materials = collect($materialData)->map(fn (array $data, int $index) => RawMaterial::firstOrCreate(
            ['code' => sprintf('DEMO-RM-%02d', $index + 1)],
            ['name' => $data[0], 'unit' => $data[1], 'current_stock' => 0, 'minimum_stock' => $data[2], 'is_active' => true]
        ));

        // Initialise only operational stock; existing catalog records and categories are reused.
        $products->each(function (Product $product): void {
            if ($product->stock_quantity === null && ! $product->stockMovements()->exists()) {
                $product->update(['stock_quantity' => 0]);
            }
        });

        $service = app(InventoryTransactionService::class);
        $start = CarbonImmutable::now()->startOfMonth()->subMonths(5);
        $batches = [90, 105, 98, 115, 110, 125];

        foreach (range(0, 5) as $offset) {
            $month = $start->addMonths($offset);
            $suffix = $month->format('Ym');
            $currentDay = CarbonImmutable::now()->day;
            foreach (range(1, 2) as $cycle) {
                $batch = $batches[$offset] + ($cycle === 2 ? 14 : 0);
                $purchaseDate = $offset === 5 ? $month->addDays(min($cycle === 1 ? 1 : 3, $currentDay) - 1) : $month->addDays($cycle === 1 ? 2 : 15);
                $productionDate = $offset === 5 ? $month->addDays(min($cycle === 1 ? 2 : 4, $currentDay) - 1) : $purchaseDate->addDays(2);
                $product = $products[($offset * 2 + $cycle - 1) % $products->count()];
                $purchaseNumber = "DEMO-PB-{$suffix}-{$cycle}";
                $productionNumber = "DEMO-PRD-{$suffix}-{$cycle}";

                if (! Purchase::where('reference_number', $purchaseNumber)->exists()) {
                    $lines = [[8, 18000], [5, 26000], [0.24, 175000], [0.5, 90000], [$batch + 28, 180], [$batch + 22, 260], [45, 350], [$batch + 15, 110]];
                    $service->savePurchase(null, [
                        'supplier_id' => $suppliers[($offset + $cycle) % $suppliers->count()]->id,
                        'purchase_date' => $purchaseDate->toDateString(), 'reference_number' => $purchaseNumber,
                        'notes' => 'Pembelian bahan dan kemasan untuk produksi demo.',
                    ], collect($lines)->map(fn (array $line, int $index) => [
                        'raw_material_id' => $materials[$index]->id, 'quantity' => $line[0], 'unit_price' => $line[1],
                    ])->all(), true);
                }
                if ($offset === 5) {
                    $purchase = Purchase::where('reference_number', $purchaseNumber)->firstOrFail();
                    $purchase->update(['purchase_date' => $purchaseDate->toDateString()]);
                    StockMovement::where('source_type', PurchaseDetail::class)->whereIn('source_id', $purchase->details()->pluck('id'))->update(['movement_date' => $purchaseDate]);
                }

                if (! Production::where('production_number', $productionNumber)->exists()) {
                    $used = [3.2, 1.8, 0.1, 0.18, $batch, $batch, 40, $batch];
                    $service->saveProduction(null, [
                        'production_date' => $productionDate->toDateString(), 'production_number' => $productionNumber,
                        'notes' => 'Batch demo menggunakan produk katalog yang sudah ada.',
                    ], collect($used)->map(fn (float|int $quantity, int $index) => [
                        'raw_material_id' => $materials[$index]->id, 'quantity_used' => $quantity,
                    ])->all(), [['product_id' => $product->id, 'quantity_produced' => $batch]], true);
                }
                if ($offset === 5) {
                    $production = Production::where('production_number', $productionNumber)->firstOrFail();
                    $production->update(['production_date' => $productionDate->toDateString()]);
                    StockMovement::where('source_type', ProductionMaterial::class)->whereIn('source_id', $production->materials()->pluck('id'))->update(['movement_date' => $productionDate]);
                    StockMovement::where('source_type', ProductionResult::class)->whereIn('source_id', $production->results()->pluck('id'))->update(['movement_date' => $productionDate]);
                }

                foreach (range(1, 2) as $saleIndex) {
                    $invoice = "DEMO-PJ-{$suffix}-{$cycle}-{$saleIndex}";
                    $saleDate = $offset === 5 ? $month->addDays(min($cycle === 1 ? ($saleIndex === 1 ? 3 : 4) : 4, $currentDay) - 1) : $productionDate->addDays($saleIndex);
                    if (Sale::where('invoice_number', $invoice)->exists()) {
                        if ($offset === 5) {
                            $sale = Sale::where('invoice_number', $invoice)->firstOrFail();
                            $sale->update(['sale_date' => $saleDate->toDateString()]);
                            StockMovement::where('source_type', SaleDetail::class)->whereIn('source_id', $sale->details()->pluck('id'))->update(['movement_date' => $saleDate]);
                        }
                        continue;
                    }
                    $quantity = $saleIndex === 1 ? (int) floor($batch * 0.32) : (int) floor($batch * 0.38);
                    $service->saveSale(null, [
                        'customer_id' => $customers[($offset * 4 + $cycle * 2 + $saleIndex) % $customers->count()]->id,
                        'sale_date' => $saleDate->toDateString(),
                        'invoice_number' => $invoice, 'notes' => 'Penjualan demo dari batch produksi.',
                    ], [['product_id' => $product->id, 'quantity' => $quantity, 'unit_price' => $product->price_from]], true);
                }
            }

            $stockNote = 'DEMO-STOCK-'.$suffix.' · Penambahan stok kemasan';
            $material = $materials[5];
            if (! $material->stockMovements()->where('notes', $stockNote)->exists()) {
                DB::transaction(function () use ($material, $month, $stockNote): void {
                    $locked = RawMaterial::query()->lockForUpdate()->findOrFail($material->id);
                    $locked->increment('current_stock', 20);
                    $locked->stockMovements()->create([
                        'movement_type' => 'adjustment_in', 'quantity' => 20, 'unit' => $locked->unit,
                        'movement_date' => $month->addDays(min(12, CarbonImmutable::now()->day) - 1)->setTime(12, 0), 'notes' => $stockNote,
                    ]);
                });
            }
            if ($offset === 5) {
                $material->stockMovements()->where('notes', $stockNote)
                    ->update(['movement_date' => $month->addDays(min(12, $currentDay) - 1)->setTime(12, 0)]);
            }
        }
    }
}
