<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_detail_page_increments_click_count(): void
    {
        $product = $this->createProduct('Permen Stiki', 'permen-stiki', 0);

        $this->get(route('products.show', $product->slug))
            ->assertOk();

        $this->assertSame(1, $product->refresh()->click_count);
    }

    public function test_admin_dashboard_shows_total_and_most_clicked_products(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->createProduct('Permen Stiki', 'permen-stiki', 25);
        $this->createProduct('Arbanat', 'arbanat', 17);
        $this->createProduct('Satru Asam', 'satru-asam', 0);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Total Clicked Products')
            ->assertSee('42')
            ->assertSee('Most Clicked Products')
            ->assertSee('Permen Stiki')
            ->assertSee('25 klik')
            ->assertSee('Arbanat')
            ->assertSee('17 klik')
            ->assertDontSee('Satru Asam');
    }

    public function test_admin_dashboard_shows_empty_click_message(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->createProduct('Satru Asam', 'satru-asam', 0);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Belum ada aktivitas kunjungan produk.');
    }

    private function createProduct(string $name, string $slug, int $clickCount): Product
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'permen'],
            ['name' => 'Permen', 'is_active' => true]
        );

        return Product::query()->create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => $slug,
            'description' => 'Produk permen untuk pengujian analytics.',
            'price_from' => 10000,
            'badge' => 'none',
            'click_count' => $clickCount,
            'is_active' => true,
        ]);
    }
}
