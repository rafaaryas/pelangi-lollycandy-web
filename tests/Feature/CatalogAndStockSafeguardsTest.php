<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogAndStockSafeguardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpublished_products_are_hidden_from_catalog_and_detail(): void
    {
        $category = Category::create(['name' => 'Lolipop', 'slug' => 'lolipop', 'is_active' => true]);
        $visible = $this->product($category, 'Terlihat', true);
        $hidden = $this->product($category, 'Tersembunyi', false);

        $this->get(route('products.index'))->assertOk()->assertSee($visible->name)->assertDontSee($hidden->name);
        $this->get(route('products.show', $visible->slug))->assertOk();
        $this->get(route('products.show', $hidden->slug))->assertNotFound();
    }

    public function test_unknown_product_stock_can_be_initialized_with_a_reason(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::create(['name' => 'Lolipop', 'slug' => 'lolipop', 'is_active' => true]);
        $product = $this->product($category, 'Stok Awal', true);
        $this->assertNull($product->stock_quantity);

        $this->actingAs($admin)->get(route('admin.stock.index', ['tab' => 'products', 'status' => 'unknown']))
            ->assertOk()->assertSee('Catat saldo awal')->assertSee($product->name);

        $payload = ['stock_type' => 'product', 'stock_id' => $product->id, 'direction' => 'in', 'quantity' => 5];
        $this->post(route('admin.stock.adjust'), $payload)->assertSessionHasErrors('notes');
        $this->assertNull($product->fresh()->stock_quantity);

        $this->post(route('admin.stock.adjust'), $payload + ['notes' => 'Saldo awal stok opname'])->assertRedirect();
        $this->assertSame('5.000', $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', ['stockable_type' => Product::class, 'stockable_id' => $product->id, 'notes' => 'Saldo awal stok opname']);
    }

    public function test_csv_report_contains_only_confirmed_sales_and_escapes_spreadsheet_formulas(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Sale::create(['invoice_number' => 'PJ-001', 'sale_date' => '2026-10-02', 'status' => 'confirmed', 'total_amount' => 12000]);
        Sale::create(['invoice_number' => '=HYPERLINK("bad")', 'sale_date' => '2026-10-03', 'status' => 'confirmed', 'total_amount' => 2000]);
        Sale::create(['invoice_number' => 'PJ-DRAFT', 'sale_date' => '2026-10-04', 'status' => 'draft', 'total_amount' => 100000]);

        $response = $this->actingAs($admin)->get(route('admin.reports.export', ['tab' => 'sales', 'from' => '2026-10-01', 'to' => '2026-10-31']));
        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('PJ-001', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString('PJ-DRAFT', $csv);
    }

    public function test_contact_requires_a_valid_whatsapp_number_and_encodes_the_message(): void
    {
        $message = ['name' => 'Ayu', 'phone' => '123', 'message' => 'Permen & hampers?'];
        $this->postJson(route('contact.store'), $message)->assertUnprocessable()->assertJsonValidationErrors('phone');

        $response = $this->postJson(route('contact.store'), array_replace($message, ['phone' => '081234567890']));
        $response->assertOk();
        $this->assertStringContainsString('Permen+%26+hampers%3F', $response->json('url'));
    }

    public function test_invalid_business_whatsapp_destination_is_not_rendered_or_used(): void
    {
        config()->set('business.whatsapp_number', 'invalid');
        $this->get(route('contact.index'))->assertOk()->assertSee('WhatsApp sedang tidak tersedia')->assertDontSee('https://wa.me/invalid');
        $this->postJson(route('contact.store'), ['name' => 'Ayu', 'phone' => '081234567890', 'message' => 'Halo'])
            ->assertStatus(503);
    }

    private function product(Category $category, string $name, bool $active): Product
    {
        return Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => str($name)->slug(),
            'description' => 'Permen',
            'price_from' => 5000,
            'badge' => 'none',
            'is_active' => $active,
        ]);
    }
}
