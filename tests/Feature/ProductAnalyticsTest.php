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

    public function test_product_detail_page_does_not_track_clicks(): void
    {
        $product = $this->createProduct('Permen Stiki', 'permen-stiki');

        $this->get(route('products.show', $product->slug))
            ->assertOk();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Permen Stiki']);
        $this->assertFalse(array_key_exists('click_count', $product->refresh()->getAttributes()));
    }

    public function test_admin_dashboard_does_not_show_click_analytics(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->createProduct('Permen Stiki', 'permen-stiki');
        $this->createProduct('Arbanat', 'arbanat');
        $this->createProduct('Satru Asam', 'satru-asam');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Total Produk')
            ->assertDontSee('Clicked Products')
            ->assertDontSee('Most Clicked Products');
    }

    public function test_old_popular_sort_is_ignored_without_click_tracking(): void
    {
        $this->createProduct('Satru Asam', 'satru-asam');

        $this->get(route('products.index', ['sort' => 'popular']))
            ->assertOk()
            ->assertDontSee('value="popular"');
    }

    private function createProduct(string $name, string $slug): Product
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
            'is_active' => true,
        ]);
    }
}
