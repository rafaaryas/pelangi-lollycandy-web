<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        if (! Schema::hasColumn('products', 'click_count')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->integer('click_count')->default(0)->after('shopee_url');
            });
        }

        DB::table('products')
            ->whereNull('click_count')
            ->update(['click_count' => 0]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'click_count')) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('click_count');
        });
    }
};
