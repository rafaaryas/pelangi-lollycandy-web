<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('stock_quantity', 14, 3)->nullable()->default(null)->change();
        });

        DB::table('products')->where('stock_quantity', 0)->update(['stock_quantity' => null]);
    }

    public function down(): void
    {
        DB::table('products')->whereNull('stock_quantity')->update(['stock_quantity' => 0]);

        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('stock_quantity', 14, 3)->default(0)->nullable(false)->change();
        });
    }
};
