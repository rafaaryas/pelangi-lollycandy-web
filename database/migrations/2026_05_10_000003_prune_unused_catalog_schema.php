<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['product_click_logs', 'product_variants', 'hero_banners'] as $table) {
            Schema::dropIfExists($table);
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'favorite_clicks')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropColumn('favorite_clicks');
            });
        }

        if (Schema::hasTable('marketplace_links') && Schema::hasColumn('marketplace_links', 'label')) {
            Schema::table('marketplace_links', function (Blueprint $table): void {
                $table->dropColumn('label');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'favorite_clicks')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->unsignedBigInteger('favorite_clicks')->default(0)->after('shopee_url');
            });
        }

        if (Schema::hasTable('marketplace_links') && ! Schema::hasColumn('marketplace_links', 'label')) {
            Schema::table('marketplace_links', function (Blueprint $table): void {
                $table->string('label')->nullable()->after('url');
            });
        }
    }
};
