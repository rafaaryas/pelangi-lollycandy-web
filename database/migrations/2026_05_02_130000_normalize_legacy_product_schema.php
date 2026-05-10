<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('produk')) {
            $legacyProducts = DB::table('produk')->get();

            foreach ($legacyProducts as $legacyProduct) {
                $categoryName = $legacyProduct->kategori_produk ?: 'Uncategorized';
                $categorySlug = Str::slug($categoryName);

                $categoryId = DB::table('categories')->where('slug', $categorySlug)->value('id');
                if (! $categoryId) {
                    $categoryId = DB::table('categories')->insertGetId([
                        'name' => $categoryName,
                        'slug' => $categorySlug,
                        'description' => 'Migrated from legacy table produk',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $productId = DB::table('products')->where('name', $legacyProduct->nama_produk)->value('id');
                if (! $productId) {
                    $productId = DB::table('products')->insertGetId([
                        'category_id' => $categoryId,
                        'name' => $legacyProduct->nama_produk,
                        'slug' => Str::slug($legacyProduct->nama_produk.'-'.$legacyProduct->id_produk),
                        'description' => $legacyProduct->deskripsi ?: 'Migrated legacy product',
                        'price_from' => 0,
                        'badge' => 'none',
                        'shopee_url' => null,
                        'click_count' => 0,
                        'published_at' => null,
                        'is_active' => strtolower((string) $legacyProduct->status_produk) === 'aktif',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        Schema::disableForeignKeyConstraints();
        DB::statement('DROP VIEW IF EXISTS v_nota_penjualan');
        foreach ([
            'detail_penjualan',
            'detail_pembelian',
            'detail_produksi',
            'penjualan',
            'pembelian_bahan',
            'produksi',
            'pembeli',
            'supplier',
            'bahan_baku',
            'varian_produk',
            'produk',
            'settings',
            'seo',
        ] as $legacyTable) {
            Schema::dropIfExists($legacyTable);
        }
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Down migration intentionally empty because legacy schema is removed permanently.
    }
};
