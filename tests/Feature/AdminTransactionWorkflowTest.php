<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTransactionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_submit_and_confirm_purchase_production_and_sale(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $supplier = Supplier::create(['name' => 'Supplier', 'code' => 'SUP-01']);
        $customer = Customer::create(['name' => 'Customer']);
        $material = RawMaterial::create(['name' => 'Gula', 'code' => 'RM-01', 'unit' => 'kg']);
        $product = Product::create([
            'category_id' => Category::create(['name' => 'Lolly', 'slug' => 'lolly'])->id,
            'name' => 'Lolly', 'slug' => 'lolly-produk', 'description' => 'Permen', 'price_from' => 5000,
            'badge' => 'none', 'stock_quantity' => 0,
        ]);

        $purchaseResponse = $this->post(route('admin.purchases.store'), [
            'supplier_id' => $supplier->id, 'purchase_date' => '2026-10-01', 'submit_action' => 'confirm',
            'items' => [['raw_material_id' => $material->id, 'quantity' => 4, 'unit_price' => 10000]],
        ]);
        $purchaseResponse->assertRedirect();
        $this->assertSame('4.000', $material->fresh()->current_stock);

        $productionResponse = $this->post(route('admin.productions.store'), [
            'production_date' => '2026-10-02', 'submit_action' => 'confirm',
            'materials' => [['raw_material_id' => $material->id, 'quantity_used' => 1]],
            'results' => [['product_id' => $product->id, 'quantity_produced' => 5]],
        ]);
        $productionResponse->assertRedirect();
        $this->assertSame('3.000', $material->fresh()->current_stock);
        $this->assertSame('5.000', $product->fresh()->stock_quantity);

        $saleResponse = $this->post(route('admin.sales.store'), [
            'customer_id' => $customer->id, 'sale_date' => '2026-10-03', 'submit_action' => 'confirm',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 5000]],
        ]);
        $saleResponse->assertRedirect();
        $this->assertSame('3.000', $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('stock_movements', 4);
    }
}
