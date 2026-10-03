<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_details', function (Blueprint $table): void {
            $table->string('unit', 40)->nullable()->after('quantity');
        });

        Schema::table('production_materials', function (Blueprint $table): void {
            $table->string('unit', 40)->nullable()->after('quantity_used');
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->string('unit', 40)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', fn (Blueprint $table) => $table->dropColumn('unit'));
        Schema::table('production_materials', fn (Blueprint $table) => $table->dropColumn('unit'));
        Schema::table('purchase_details', fn (Blueprint $table) => $table->dropColumn('unit'));
    }
};
