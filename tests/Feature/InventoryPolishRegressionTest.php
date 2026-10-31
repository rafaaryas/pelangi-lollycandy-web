<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryPolishRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        return Product::create([
            'category_id' => Category::create(['name' => 'Permen', 'slug' => 'permen'])->id,
            'name' => 'Lolly', 'slug' => 'lolly', 'description' => 'Permen', 'price_from' => 5000,
            'stock_quantity' => 10,
        ]);
    }

    private function admin(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_production_history_keeps_polymorphic_ids_paired_with_their_types(): void
    {
        $this->admin();
        $service = app(InventoryTransactionService::class);
        $product = $this->product();
        $sugar = RawMaterial::create(['name' => 'Gula', 'code' => 'G', 'unit' => 'kg', 'current_stock' => 10]);
        $flavor = RawMaterial::create(['name' => 'Perisa', 'code' => 'P', 'unit' => 'kg', 'current_stock' => 10]);
        $first = $service->saveProduction(null, ['production_date' => '2026-10-01'], [
            ['raw_material_id' => $sugar->id, 'quantity_used' => 1],
            ['raw_material_id' => $flavor->id, 'quantity_used' => 1],
        ], [['product_id' => $product->id, 'quantity_produced' => 2]], true);
        $service->saveProduction(null, ['production_date' => '2026-10-02'], [
            ['raw_material_id' => $sugar->id, 'quantity_used' => 1],
        ], [['product_id' => $product->id, 'quantity_produced' => 3]], true);

        $this->get(route('admin.productions.show', $first))->assertOk()
            ->assertViewHas('movements', fn ($movements) => $movements->count() === 3
                && $movements->every(fn ($movement) => str_contains($movement->notes, $first->production_number)));
    }

    public function test_service_rejects_unrepresentable_quantities_and_prices_before_writes(): void
    {
        $service = app(InventoryTransactionService::class);
        $product = $this->product();
        foreach ([['1.0004', '10000'], ['0.0004', '10000'], ['1', '10.001']] as [$quantity, $price]) {
            try {
                $service->saveSale(null, ['sale_date' => '2026-10-01'], [[
                    'product_id' => $product->id, 'quantity' => $quantity, 'unit_price' => $price,
                ]], true);
                $this->fail('Unsupported decimal precision must be rejected.');
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame('10.000', $product->fresh()->stock_quantity);
    }

    public function test_service_preserves_supported_fractional_quantities_and_money(): void
    {
        $product = $this->product();
        $sale = app(InventoryTransactionService::class)->saveSale(null, ['sale_date' => '2026-10-01'], [[
            'product_id' => $product->id, 'quantity' => '1.125', 'unit_price' => '12.34',
        ]], true);
        $this->assertSame('1.125', $sale->details->first()->quantity);
        $this->assertSame('12.34', $sale->details->first()->unit_price);
        $this->assertSame('13.88', $sale->total_amount);
        $this->assertSame('8.875', $product->fresh()->stock_quantity);
    }

    public function test_direct_purchase_and_production_calls_validate_precision(): void
    {
        $service = app(InventoryTransactionService::class);
        foreach ([
            fn () => $service->savePurchase(null, [], [['quantity' => '1', 'unit_price' => '10.001']]),
            fn () => $service->saveProduction(null, [], [['quantity_used' => '1.0004']], [['quantity_produced' => 1]]),
            fn () => $service->saveProduction(null, [], [['quantity_used' => 1]], [['quantity_produced' => '1.0004']]),
            fn () => $service->saveSale(null, [], [['quantity' => '99999999999', 'unit_price' => '999999999999']]),
        ] as $operation) {
            try {
                $operation();
                $this->fail('Invalid numeric input must fail before document creation.');
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
        $this->assertDatabaseCount('purchases', 0);
        $this->assertDatabaseCount('productions', 0);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_http_stock_and_transaction_inputs_reject_excess_precision(): void
    {
        $this->admin();
        $product = $this->product();
        $material = RawMaterial::create(['name' => 'Gula', 'code' => 'G', 'unit' => 'kg']);
        $this->postJson(route('admin.sales.store'), ['sale_date' => '2026-10-01', 'items' => [[
            'product_id' => $product->id, 'quantity' => '1.0004', 'unit_price' => 100,
        ]]])->assertUnprocessable()->assertJsonValidationErrors('items.0.quantity');
        $this->postJson(route('admin.stock.adjust'), [
            'stock_type' => 'product', 'stock_id' => $product->id, 'direction' => 'in',
            'quantity' => '0.0004', 'notes' => 'Stock opname',
        ])->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->postJson(route('admin.stock.add-material'), [
            'raw_material_id' => $material->id, 'quantity' => '1.0004', 'movement_date' => '2026-10-01',
        ])->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->postJson(route('admin.raw-materials.store'), [
            'name' => 'Perisa', 'code' => 'P', 'unit' => 'kg', 'minimum_stock' => 0, 'opening_stock' => '0.0004',
        ])->assertUnprocessable()->assertJsonValidationErrors('opening_stock');
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_material_stock_addition_accepts_omitted_optional_notes(): void
    {
        $this->admin();
        $material = RawMaterial::create(['name' => 'Gula', 'code' => 'G', 'unit' => 'kg']);
        $this->post(route('admin.stock.add-material'), [
            'raw_material_id' => $material->id, 'quantity' => '0.125', 'movement_date' => '2026-10-01',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('0.125', $material->fresh()->current_stock);
        $this->assertDatabaseHas('stock_movements', ['notes' => 'Penambahan stok bahan baku', 'unit' => 'kg']);
    }

    public function test_history_and_export_show_document_references_without_lazy_parent_queries(): void
    {
        $this->admin();
        $service = app(InventoryTransactionService::class);
        $product = $this->product();
        $material = RawMaterial::create(['name' => 'Gula', 'code' => 'G', 'unit' => 'kg']);
        $supplier = Supplier::create(['name' => 'Pemasok', 'code' => 'SUP']);
        $purchase = $service->savePurchase(null, ['supplier_id' => $supplier->id, 'purchase_date' => '2026-10-01'], [[
            'raw_material_id' => $material->id, 'quantity' => 5, 'unit_price' => 10000,
        ]], true);
        $production = $service->saveProduction(null, ['production_date' => '2026-10-02'], [
            ['raw_material_id' => $material->id, 'quantity_used' => 1],
        ], [['product_id' => $product->id, 'quantity_produced' => 2]], true);
        $sale = $service->saveSale(null, ['sale_date' => '2026-10-03'], [[
            'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10000,
        ]], true);
        $movements = StockMovement::withSourceDocument()->orderBy('id')->get();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->assertSame([$purchase->reference_number, $production->production_number, $production->production_number, $sale->invoice_number], $movements->pluck('reference')->all());
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
        $this->get(route('admin.stock.history', ['materials', $material->id]))->assertOk()->assertSee($purchase->reference_number)->assertSee($production->production_number);
        $this->get(route('admin.raw-materials.show', $material))->assertOk()->assertSee($purchase->reference_number);
        $csv = $this->get(route('admin.export', ['module' => 'stock-history', 'format' => 'csv', 'type' => 'products', 'id' => $product->id]))->assertOk()->streamedContent();
        $this->assertStringContainsString($production->production_number, $csv);
        $this->assertStringContainsString($sale->invoice_number, $csv);
    }

    public function test_category_name_collisions_and_empty_slugs_return_validation_errors(): void
    {
        $this->admin();
        $existing = Category::create(['name' => 'Lolly Pop', 'slug' => 'lolly-pop']);
        $other = Category::create(['name' => 'Other', 'slug' => 'other']);
        $this->postJson(route('admin.categories.store'), ['name' => 'Lolly-Pop'])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->postJson(route('admin.categories.store'), ['name' => '!!!'])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->putJson(route('admin.categories.update', $other), ['name' => 'Lolly-Pop'])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->putJson(route('admin.categories.update', $existing), ['name' => 'Lolly Pop', 'is_active' => true])->assertOk();
        $this->assertDatabaseCount('categories', 2);
        $this->assertSame('other', $other->fresh()->slug);
    }
}
