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
                if (!$categoryId) {
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
                if (!$productId) {
                    $productId = DB::table('products')->insertGetId([
                        'category_id' => $categoryId,
                        'name' => $legacyProduct->nama_produk,
                        'slug' => Str::slug($legacyProduct->nama_produk.'-'.$legacyProduct->id_produk),
                        'description' => $legacyProduct->deskripsi ?: 'Migrated legacy product',
                        'price_from' => 0,
                        'price_strike' => null,
                        'badge' => 'none',
                        'sku' => 'LEGACY-'.$legacyProduct->id_produk,
                        'weight_gram' => 0,
                        'flavor' => null,
                        'shopee_url' => null,
                        'favorite_clicks' => 0,
                        'published_at' => null,
                        'is_active' => strtolower((string) $legacyProduct->status_produk) === 'aktif',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                if (Schema::hasTable('varian_produk')) {
                    $variants = DB::table('varian_produk')->where('id_produk', $legacyProduct->id_produk)->get();
                    foreach ($variants as $variant) {
                        $exists = DB::table('product_variants')
                            ->where('product_id', $productId)
                            ->where('size', $variant->ukuran ?: '-')
                            ->where('flavor', $variant->rasa ?: '-')
                            ->where('price', $variant->harga_jual ?: 0)
                            ->exists();

                        if (!$exists) {
                            DB::table('product_variants')->insert([
                                'product_id' => $productId,
                                'size' => $variant->ukuran ?: '-',
                                'flavor' => $variant->rasa ?: '-',
                                'stock_info' => $variant->stok_varian ? 'Stok: '.$variant->stok_varian : null,
                                'price' => $variant->harga_jual ?: 0,
                                'is_active' => true,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
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
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // Down migration intentionally empty because legacy schema is removed permanently.
    }
};
