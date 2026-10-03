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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_models_link_documents_and_stock_movements(): void
    {
        $supplier = Supplier::create(['name' => 'Supplier', 'code' => 'SUP-001']);
        $rawMaterial = RawMaterial::create(['name' => 'Gula', 'code' => 'RM-001', 'unit' => 'kg']);
        $purchase = Purchase::create([
            'supplier_id' => $supplier->id,
            'reference_number' => 'PB-001',
            'purchase_date' => '2026-10-01',
        ]);
        $purchaseDetail = $purchase->details()->create([
            'raw_material_id' => $rawMaterial->id,
            'quantity' => 2.5,
            'unit_price' => 10000,
            'subtotal' => 25000,
        ]);

        $product = Product::create([
            'category_id' => Category::create(['name' => 'Permen', 'slug' => 'permen'])->id,
            'name' => 'Permen',
            'slug' => 'permen-produk',
            'description' => 'Produk uji',
            'price_from' => 5000,
            'badge' => 'none',
        ]);
        $customer = Customer::create(['name' => 'Pelanggan']);
        $sale = Sale::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'PJ-001',
            'sale_date' => '2026-10-02',
        ]);
        $saleDetail = $sale->details()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 5000,
            'subtotal' => 5000,
        ]);

        $production = Production::create([
            'production_number' => 'PRD-001',
            'production_date' => '2026-10-03',
        ]);
        $productionMaterial = $production->materials()->create([
            'raw_material_id' => $rawMaterial->id,
            'quantity_used' => 0.25,
        ]);
        $productionResult = $production->results()->create([
            'product_id' => $product->id,
            'quantity_produced' => 10,
        ]);
        $movement = $rawMaterial->stockMovements()->create([
            'movement_type' => 'purchase_in',
            'quantity' => 2.5,
            'movement_date' => '2026-10-01 10:00:00',
        ]);

        $this->assertTrue($purchaseDetail->rawMaterial->is($rawMaterial));
        $this->assertTrue($purchase->supplier->is($supplier));
        $this->assertTrue($saleDetail->product->is($product));
        $this->assertTrue($sale->customer->is($customer));
        $this->assertTrue($productionMaterial->rawMaterial->is($rawMaterial));
        $this->assertTrue($productionResult->product->is($product));
        $this->assertTrue($movement->stockable->is($rawMaterial));
        $this->assertSame('2.500', $purchaseDetail->fresh()->quantity);
    }
}
