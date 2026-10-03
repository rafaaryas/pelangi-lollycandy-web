<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\Supplier;
use App\Services\InventoryTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryTransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirming_purchase_production_and_sale_updates_stock_and_records_units(): void
    {
        $service = app(InventoryTransactionService::class);
        $supplier = Supplier::create(['name' => 'Pemasok', 'code' => 'SUP-01']);
        $customer = Customer::create(['name' => 'Pelanggan']);
        $material = RawMaterial::create(['name' => 'Gula', 'code' => 'RM-01', 'unit' => 'kg']);
        $product = Product::create([
            'category_id' => Category::create(['name' => 'Permen', 'slug' => 'permen'])->id,
            'name' => 'Lolly', 'slug' => 'lolly', 'description' => 'Permen', 'price_from' => 5000,
            'badge' => 'none', 'stock_quantity' => 0,
        ]);

        $purchase = $service->savePurchase(null, ['supplier_id' => $supplier->id, 'purchase_date' => '2026-10-01'], [[
            'raw_material_id' => $material->id, 'quantity' => 5, 'unit_price' => 10000,
        ]], true);
        $this->assertSame('confirmed', $purchase->status);
        $this->assertSame('5.000', $material->fresh()->current_stock);
        $this->assertSame('kg', $purchase->details->first()->unit);

        $production = $service->saveProduction(null, ['production_date' => '2026-10-02'], [[
            'raw_material_id' => $material->id, 'quantity_used' => 2,
        ]], [['product_id' => $product->id, 'quantity_produced' => 10]], true);
        $this->assertSame('3.000', $material->fresh()->current_stock);
        $this->assertSame('10.000', $product->fresh()->stock_quantity);
        $this->assertSame('kg', $production->materials->first()->unit);

        $sale = $service->saveSale(null, ['customer_id' => $customer->id, 'sale_date' => '2026-10-03'], [[
            'product_id' => $product->id, 'quantity' => 3, 'unit_price' => 7000,
        ]], true);
        $this->assertSame('7.000', $product->fresh()->stock_quantity);
        $this->assertSame('21000.00', $sale->total_amount);
        $this->assertDatabaseCount('stock_movements', 4);
    }

    public function test_failed_confirmation_rolls_back_document_and_stock_changes(): void
    {
        $service = app(InventoryTransactionService::class);
        $material = RawMaterial::create(['name' => 'Gula', 'code' => 'RM-01', 'unit' => 'kg', 'current_stock' => 1]);
        $product = Product::create([
            'category_id' => Category::create(['name' => 'Permen', 'slug' => 'permen'])->id,
            'name' => 'Lolly', 'slug' => 'lolly', 'description' => 'Permen', 'price_from' => 5000,
            'badge' => 'none', 'stock_quantity' => 4,
        ]);

        try {
            $service->saveProduction(null, ['production_date' => '2026-10-02'], [[
                'raw_material_id' => $material->id, 'quantity_used' => 2,
            ]], [['product_id' => $product->id, 'quantity_produced' => 10]], true);
            $this->fail('Production with insufficient ingredients should be rejected.');
        } catch (ValidationException) {
            $this->assertSame('1.000', $material->fresh()->current_stock);
            $this->assertSame('4.000', $product->fresh()->stock_quantity);
            $this->assertDatabaseCount('productions', 0);
            $this->assertDatabaseCount('stock_movements', 0);
        }

        $sale = $service->saveSale(null, ['sale_date' => '2026-10-03'], [[
            'product_id' => $product->id, 'quantity' => 5, 'unit_price' => 7000,
        ]]);
        $this->expectException(ValidationException::class);
        $service->confirmSale($sale);
    }

    public function test_cancelling_confirmed_sale_restores_stock_once(): void
    {
        $service = app(InventoryTransactionService::class);
        $product = Product::create([
            'category_id' => Category::create(['name' => 'Permen', 'slug' => 'permen'])->id,
            'name' => 'Lolly', 'slug' => 'lolly', 'description' => 'Permen', 'price_from' => 5000,
            'badge' => 'none', 'stock_quantity' => 8,
        ]);
        $sale = $service->saveSale(null, ['sale_date' => '2026-10-03'], [[
            'product_id' => $product->id, 'quantity' => 3, 'unit_price' => 7000,
        ]], true);
        $service->cancelSale($sale);
        $this->assertSame('8.000', $product->fresh()->stock_quantity);
        $this->assertSame('cancelled', $sale->fresh()->status);
        $this->assertDatabaseCount('stock_movements', 2);

        $this->expectException(ValidationException::class);
        $service->cancelSale($sale->fresh());
    }
}
