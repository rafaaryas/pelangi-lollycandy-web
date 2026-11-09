<?php

namespace Tests\Feature;

use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageFallbackTest extends TestCase
{
    public function test_packaged_product_photo_is_used_when_public_storage_is_empty(): void
    {
        Storage::fake('public');

        $image = new ProductImage([
            'image_path' => 'products/zp9N66k22IKcBihT11nVINz2skwEmd567AFAENgo.jpg',
        ]);

        $this->assertSame(
            asset('images/products/zp9N66k22IKcBihT11nVINz2skwEmd567AFAENgo.jpg'),
            $image->publicUrl(),
        );
    }

    public function test_missing_photo_does_not_point_to_a_broken_url(): void
    {
        Storage::fake('public');

        $image = new ProductImage(['image_path' => 'products/missing.jpg']);

        $this->assertNull($image->publicUrl());
    }
}
