<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Production;
use App\Models\Purchase;
use App\Models\RawMaterial;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DemoBusinessDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModulePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_admin_navigation_modules_render_for_an_admin(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        foreach ([
            'admin.dashboard', 'admin.categories.index', 'admin.products.index',
            'admin.raw-materials.index', 'admin.suppliers.index', 'admin.customers.index',
            'admin.purchases.index', 'admin.purchases.create', 'admin.productions.index',
            'admin.productions.create', 'admin.sales.index', 'admin.sales.create',
            'admin.stock.index', 'admin.stock.index', 'admin.reports.index',
            'admin.marketplace-links.index',
        ] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }

        $this->get(route('home'))->assertOk();

        $category = Category::create(['name' => 'Lolly', 'slug' => 'lolly']);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Produk', 'slug' => 'produk',
            'description' => 'Deskripsi', 'price_from' => 5000, 'badge' => 'none', 'stock_quantity' => 0,
        ]);
        $this->get(route('admin.products.show', $product))->assertOk()->assertSee('Informasi produk');
        $this->post(route('admin.products.store'), [
            'category_id' => $category->id, 'name' => 'Produk Baru', 'description' => 'Produk baru',
            'price_from' => 8000, 'badge' => 'none', 'minimum_stock' => 4, 'is_active' => '1',
        ])->assertRedirect(route('admin.products.index'));
        $newProduct = Product::where('slug', 'produk-baru')->firstOrFail();
        $this->put(route('admin.products.update', $newProduct), [
            'category_id' => $category->id, 'name' => 'Produk Baru Diperbarui', 'description' => 'Produk baru',
            'price_from' => 8500, 'badge' => 'none', 'minimum_stock' => 4, 'is_active' => '1',
        ])->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', ['id' => $newProduct->id, 'slug' => 'produk-baru-diperbarui']);

        $this->seed(DemoBusinessDataSeeder::class);
        $purchase = Purchase::firstOrFail();
        $production = Production::firstOrFail();
        $sale = Sale::firstOrFail();
        $material = RawMaterial::firstOrFail();
        $supplier = Supplier::firstOrFail();
        $customer = Customer::firstOrFail();
        $stockProduct = Product::whereNotNull('stock_quantity')->firstOrFail();
        $this->get(route('admin.purchases.show', $purchase))->assertOk();
        $this->get(route('admin.productions.show', $production))->assertOk();
        $this->get(route('admin.sales.show', $sale))->assertOk();
        $this->get(route('admin.stock.history', ['materials', $material->id]))->assertOk();
        $this->get(route('admin.stock.history', ['products', $stockProduct->id]))->assertOk();
        $this->get(route('admin.raw-materials.show', $material))->assertOk();
        $this->get(route('admin.suppliers.show', $supplier))->assertOk();
        $this->get(route('admin.customers.show', $customer))->assertOk();
        foreach (['sales', 'purchases', 'productions', 'stock'] as $tab) {
            $this->get(route('admin.reports.index', ['tab' => $tab]))->assertOk();
        }
    }

    public function test_demo_business_data_seeder_is_consistent_and_idempotent(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 3)->startOfDay());
        $seeder = app(DemoBusinessDataSeeder::class);
        $seeder->run();
        $seeder->run();

        $this->assertDatabaseCount('purchases', 6);
        $this->assertDatabaseCount('productions', 6);
        $this->assertDatabaseCount('sales', 6);
        $this->assertDatabaseCount('stock_movements', 48);
        $this->assertDatabaseCount('purchase_details', 18);
        $this->assertDatabaseCount('production_materials', 18);
        $this->assertDatabaseCount('production_results', 6);
        $this->assertDatabaseCount('sale_details', 6);

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('DEMO-PJ-202610')
            ->assertSee('Rp576.000');
    }
}
