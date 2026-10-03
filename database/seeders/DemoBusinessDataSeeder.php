<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Production;
use App\Models\Purchase;
use App\Models\RawMaterial;
use App\Models\Sale;
use App\Models\Supplier;
use App\Services\InventoryTransactionService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class DemoBusinessDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Demo business data can only be seeded outside production.');
        }

        $category = Category::firstOrCreate(['slug' => 'demo-lolly'], ['name' => 'Lolly Pop', 'is_active' => true]);
        $supplier = Supplier::firstOrCreate(['code' => 'DEMO-SUP-01'], [
            'name' => 'Pemasok Bahan Manis', 'phone' => '081200000101', 'email' => 'bahan@example.test', 'is_active' => true,
        ]);
        $customers = collect([
            ['name' => 'Toko Ceria', 'phone' => '081200000201'],
            ['name' => 'Kedai Pelangi', 'phone' => '081200000202'],
            ['name' => 'Rani Pratama', 'phone' => '081200000203'],
        ])->map(fn (array $data) => Customer::firstOrCreate(['phone' => $data['phone']], $data + ['is_active' => true]));

        $sugar = RawMaterial::firstOrCreate(['code' => 'DEMO-RM-01'], [
            'name' => 'Gula Halus', 'unit' => 'kg', 'current_stock' => 0, 'minimum_stock' => 10, 'is_active' => true,
        ]);
        $flavor = RawMaterial::firstOrCreate(['code' => 'DEMO-RM-02'], [
            'name' => 'Perisa Stroberi', 'unit' => 'liter', 'current_stock' => 0, 'minimum_stock' => 2, 'is_active' => true,
        ]);
        $sticks = RawMaterial::firstOrCreate(['code' => 'DEMO-RM-03'], [
            'name' => 'Lolly Stick', 'unit' => 'pcs', 'current_stock' => 0, 'minimum_stock' => 100, 'is_active' => true,
        ]);

        $products = collect([
            ['name' => 'Lolly Stroberi', 'slug' => 'demo-lolly-stroberi', 'price' => 7500],
            ['name' => 'Lolly Jeruk', 'slug' => 'demo-lolly-jeruk', 'price' => 7500],
            ['name' => 'Lolly Anggur', 'slug' => 'demo-lolly-anggur', 'price' => 8000],
        ])->map(fn (array $data) => Product::firstOrCreate(['slug' => $data['slug']], [
            'category_id' => $category->id, 'name' => $data['name'], 'description' => 'Produk demo untuk alur operasional.',
            'price_from' => $data['price'], 'badge' => 'none', 'is_active' => true,
            'stock_quantity' => 0, 'minimum_stock' => 20,
        ]));

        $service = app(InventoryTransactionService::class);
        $start = CarbonImmutable::now()->startOfMonth()->subMonths(5);
        $productionQuantities = [90, 100, 105, 110, 115, 120];
        $salesQuantities = [42, 55, 60, 63, 68, 72];

        foreach (range(0, 5) as $offset) {
            $month = $start->addMonths($offset);
            $suffix = $month->format('Ym');
            $purchaseNumber = 'DEMO-PB-'.$suffix;
            $productionNumber = 'DEMO-PRD-'.$suffix;
            $invoiceNumber = 'DEMO-PJ-'.$suffix;

            if (! Purchase::where('reference_number', $purchaseNumber)->exists()) {
                $service->savePurchase(null, [
                    'supplier_id' => $supplier->id, 'purchase_date' => $month->startOfMonth()->toDateString(),
                    'reference_number' => $purchaseNumber, 'notes' => 'Data demo bulanan.',
                ], [
                    ['raw_material_id' => $sugar->id, 'quantity' => 3, 'unit_price' => 18000],
                    ['raw_material_id' => $flavor->id, 'quantity' => 0.6, 'unit_price' => 45000],
                    ['raw_material_id' => $sticks->id, 'quantity' => $productionQuantities[$offset], 'unit_price' => 150],
                ], true);
            }

            if (! Production::where('production_number', $productionNumber)->exists()) {
                $quantity = $productionQuantities[$offset];
                $service->saveProduction(null, [
                    'production_date' => $month->addDays(1)->toDateString(),
                    'production_number' => $productionNumber, 'notes' => 'Data demo bulanan.',
                ], [
                    ['raw_material_id' => $sugar->id, 'quantity_used' => 2],
                    ['raw_material_id' => $flavor->id, 'quantity_used' => 0.4],
                    ['raw_material_id' => $sticks->id, 'quantity_used' => $quantity],
                ], [
                    ['product_id' => $products[$offset % 3]->id, 'quantity_produced' => $quantity],
                ], true);
            }

            if (! Sale::where('invoice_number', $invoiceNumber)->exists()) {
                $quantity = $salesQuantities[$offset];
                $unitPrice = (float) $products[$offset % 3]->price_from;
                $service->saveSale(null, [
                    'customer_id' => $customers[$offset % $customers->count()]->id,
                    'sale_date' => $month->addDays(2)->toDateString(),
                    'invoice_number' => $invoiceNumber, 'notes' => 'Data demo bulanan.',
                ], [[
                    'product_id' => $products[$offset % 3]->id, 'quantity' => $quantity, 'unit_price' => $unitPrice,
                ]], true);
            }
        }
    }
}
