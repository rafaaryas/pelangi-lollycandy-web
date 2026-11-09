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
    public function test_selected_month_preserves_totals_and_drill_down_context(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 10)->startOfDay());
        $admin = User::factory()->create(['is_admin' => true]);
        foreach ([['OCT', '2026-10-31', 'confirmed', 45000], ['DRAFT', '2026-10-15', 'draft', 999], ['NOV', '2026-11-01', 'confirmed', 80000]] as [$reference, $date, $status, $amount]) {
            Sale::create(['invoice_number' => $reference, 'sale_date' => $date, 'status' => $status, 'total_amount' => $amount]);
        }
        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['month' => '2026-10']))->assertOk();
        $response->assertViewHas('stats', fn ($stats) => $stats['salesAmount'] === 45000.0 && $stats['salesCount'] === 1);
        $response->assertViewHas('months', fn ($months) => $months->count() === 6 && $months->last()['key'] === '2026-10' && $months->last()['sales'] === 45000.0);
        $filters = ['from' => '2026-10-01', 'to' => '2026-10-31', 'status' => 'confirmed'];
        foreach (['sales', 'productions'] as $kind) {
            $response->assertSee(e(route('admin.'.$kind.'.index', $filters)), false);
            $this->get(route('admin.'.$kind.'.index', $filters))->assertOk();
        }
        $this->get(route('admin.sales.index', $filters))->assertSee('OCT')->assertDontSee('DRAFT')->assertDontSee('NOV');
        $this->getJson(route('admin.dashboard', ['month' => '2026-13']))->assertUnprocessable();
        $this->getJson(route('admin.dashboard', ['month' => '2026-12']))->assertUnprocessable();
    }

    public function test_dashboard_limits_history_and_stock_and_links_to_exact_items(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        for ($i = 1; $i <= 18; $i++) {
            Sale::create(['invoice_number' => 'SALE-'.$i, 'sale_date' => now()->subDays($i), 'status' => 'draft', 'total_amount' => 0]);
            RawMaterial::create(['name' => 'Material '.$i, 'code' => 'MAT-'.$i, 'unit' => 'kg', 'current_stock' => $i === 18 ? 0 : 2, 'minimum_stock' => 3]);
        }
        $response = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $response->assertViewHas('activities', fn ($items) => $items->count() === 5 && $items->first()['reference'] === 'SALE-1' && $items->last()['reference'] === 'SALE-5');
        $response->assertViewHas('attentionStock', fn ($items) => $items->count() === 5 && $items->first()['name'] === 'Material 18');
        foreach ($response->viewData('attentionStock') as $item) {
            $this->get($item['url'])->assertOk()->assertSee($item['name']);
        }
        foreach ($response->viewData('activities') as $item) {
            $this->get($item['url'])->assertOk()->assertSee($item['reference']);
        }
    }

    public function test_empty_dashboard_and_access_control(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->actingAs(User::factory()->create(['is_admin' => false]))->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('admin.dashboard'))
            ->assertOk()->assertSee('Tidak ada stok yang perlu diperhatikan.')
            ->assertSee('Belum ada aktivitas transaksi.')->assertSee('Belum ada data produksi pada periode ini.');
    }
}
