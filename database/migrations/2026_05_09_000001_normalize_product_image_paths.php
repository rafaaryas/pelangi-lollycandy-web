<?php

use App\Models\ProductImage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_images')) {
            return;
        }

        DB::table('product_images')
            ->select('id', 'image_path')
            ->orderBy('id')
            ->each(function (object $image): void {
                DB::table('product_images')
                    ->where('id', $image->id)
                    ->update([
                        'image_path' => ProductImage::normalizeStoragePath($image->image_path),
                    ]);
            });
    }

    public function down(): void
    {
        //
    }
};
