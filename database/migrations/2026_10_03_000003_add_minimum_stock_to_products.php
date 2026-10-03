<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'minimum_stock')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->decimal('minimum_stock', 14, 3)->default(0)->after('stock_quantity');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'minimum_stock')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropColumn('minimum_stock');
            });
        }
    }
};
