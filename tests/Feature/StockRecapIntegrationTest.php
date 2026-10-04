<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryTransactionService;
use App\Services\RawMaterialStockReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockRecapIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_addition_and_production_feed_the_same_monthly_recap_and_detail(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $material = RawMaterial::create([
            'code' => 'RM-TEST', 'name' => 'Gula Uji', 'unit' => 'kg',
            'current_stock' => 0, 'minimum_stock' => 5, 'is_active' => true,
        ]);
        $date = now()->startOfMonth()->toDateString();

        $this->post(route('admin.stock.add-material'), [
            'raw_material_id' => $material->id, 'quantity' => 50,
            'movement_date' => $date, 'notes' => 'Pembelian stok uji',
        ])->assertRedirect(route('admin.stock.index', ['tab' => 'materials']));

        $category = Category::create(['name' => 'Uji', 'slug' => 'uji']);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Permen Uji', 'slug' => 'permen-uji',
            'description' => 'Produk untuk tes.', 'price_from' => 1000, 'stock_quantity' => 0,
            'is_active' => true,
        ]);
        app(InventoryTransactionService::class)->saveProduction(null, [
            'production_number' => 'PRD-TEST-001', 'production_date' => $date,
        ], [['raw_material_id' => $material->id, 'quantity_used' => 12]], [[
            'product_id' => $product->id, 'quantity_produced' => 10,
        ]], true);

        $row = app(RawMaterialStockReport::class)->summary(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString())->first();
        $this->assertSame(0.0, $row['opening']);
        $this->assertSame(50.0, $row['incoming']);
        $this->assertSame(12.0, $row['used']);
        $this->assertSame(38.0, $row['closing']);
        $this->assertSame(38.0, (float) $material->fresh()->current_stock);
        $this->assertSame(2, StockMovement::where('stockable_type', RawMaterial::class)->count());

        $period = ['tab' => 'stock', 'month' => now()->month, 'year' => now()->year];
        $this->get(route('admin.reports.index', $period))->assertOk()
            ->assertSee('Rekap Stok Bahan Baku')->assertSee('Gula Uji')
            ->assertSee('Stok Awal')->assertSee('Stok Masuk')->assertSee('Stok Terpakai')->assertSee('Stok Akhir');
        $this->get(route('admin.reports.index', $period + ['mode' => 'detail']))->assertOk()
            ->assertSee('Pembelian stok uji')->assertSee('PRD-TEST-001');
    }
}
