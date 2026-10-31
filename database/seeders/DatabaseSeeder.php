<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MarketplaceLink;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Demo catalog data only. Administrators are provisioned explicitly with
        // `php artisan admin:create`; seeding must never reset a user's credentials.
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $category = Category::query()->firstOrCreate(['slug' => 'lollipop'], [
            'name' => 'Lollipop',
            'is_active' => true,
        ]);

        $products = [
            'Lollipop Love Bulat 4cm isi 20', 'Lollipop Love Bulat 6cm', 'Lollipop Bulat Besar',
            'Permen Stik Toples 100gr', 'Permen Stik Pouch 75gr', 'Permen Stik 1kg',
            'Permen Ulir/Spiral 4cm isi 20', 'Toples isi 20 / 30 / 50', 'Permen Kupu-Kupu',
            'Permen Bunga Matahari', 'Permen Bentuk Campur', 'Permen Satrau Asam', 'Arbanat / Rambut Nenek',
        ];

        foreach ($products as $name) {
            Product::query()->firstOrCreate(['slug' => Str::slug($name)], [
                'category_id' => $category->id,
                'name' => $name,
                'description' => 'Permen premium colorful dengan rasa manis yang konsisten.',
                'price_from' => rand(12000, 58000),
                'badge' => rand(0, 1) ? 'new' : 'best_seller',
                'is_active' => true,
            ]);
        }

        foreach (['Shopee', 'TikTok Shop', 'Tokopedia', 'Instagram', 'WhatsApp', 'Facebook'] as $platform) {
            MarketplaceLink::query()->firstOrCreate(['platform' => $platform], [
                'url' => 'https://example.com/'.Str::slug($platform),
                'is_active' => true,
            ]);
        }
    }
}
