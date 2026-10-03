<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Production;
use App\Models\RawMaterial;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBusinessDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_summarizes_real_confirmed_business_data_and_stock_alerts(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 3)->startOfDay());
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::create(['name' => 'Permen', 'slug' => 'permen']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Lolly Pelangi',
            'slug' => 'lolly-pelangi',
            'description' => 'Permen warna-warni',
            'price_from' => 5000,
            'badge' => 'none',
            'stock_quantity' => 2,
            'minimum_stock' => 5,
        ]);
        $supplier = Supplier::create(['name' => 'Supplier Gula', 'code' => 'SUP-001']);
        $customer = Customer::create(['name' => 'Pelanggan Uji']);
        $rawMaterial = RawMaterial::create([
            'name' => 'Gula', 'code' => 'RM-001', 'unit' => 'kg',
            'current_stock' => 1, 'minimum_stock' => 3,
        ]);
        $sale = Sale::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'PJ-TEST-001',
            'sale_date' => '2026-10-02',
            'status' => 'confirmed',
            'total_amount' => 25000,
        ]);
        $production = Production::create([
            'production_number' => 'PRD-TEST-001',
            'production_date' => '2026-10-02',
            'status' => 'confirmed',
        ]);
        $production->results()->create(['product_id' => $product->id, 'quantity_produced' => 12]);
        Sale::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'PJ-TEST-DRAFT',
            'sale_date' => '2026-10-02',
            'status' => 'draft',
            'total_amount' => 999999,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Total Produk')
            ->assertSee('Penjualan Bulan Ini')
            ->assertSee('Rp25.000')
            ->assertSee('1 transaksi terkonfirmasi')
            ->assertSee('12')
            ->assertSee('Gula')
            ->assertSee('Lolly Pelangi')
            ->assertSee('PJ-TEST-001')
            ->assertSee('PRD-TEST-001')
            ->assertSee('PJ-TEST-DRAFT');

        $this->assertTrue($sale->exists);
        $this->assertTrue($rawMaterial->exists);
        $this->assertTrue($supplier->exists);
    }
}
