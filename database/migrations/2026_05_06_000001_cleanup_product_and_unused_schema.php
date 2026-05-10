<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Keep the live database aligned with the simplified catalog/admin scope.
     */
    public function up(): void
    {
        foreach (['testimonials', 'inquiries', 'subscribers', 'settings', 'faqs'] as $table) {
            Schema::dropIfExists($table);
        }

        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'description')) {
            Schema::table('categories', function (Blueprint $table): void {
                $table->dropColumn('description');
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table): void {
                foreach (['price_strike', 'sku', 'weight_gram', 'flavor'] as $column) {
                    if (Schema::hasColumn('products', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // The cleanup removes unused legacy surface area and is intentionally one-way.
    }
};
