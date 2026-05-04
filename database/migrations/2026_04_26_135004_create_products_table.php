<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->longText('description');
            $table->decimal('price_from', 12, 2);
            $table->decimal('price_strike', 12, 2)->nullable();
            $table->enum('badge', ['none', 'new', 'best_seller'])->default('none');
            $table->string('sku')->unique();
            $table->unsignedInteger('weight_gram')->default(0);
            $table->string('flavor')->nullable();
            $table->string('shopee_url')->nullable();
            $table->unsignedBigInteger('favorite_clicks')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
