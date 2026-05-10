<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    protected $fillable = ['product_id', 'image_path', 'is_primary', 'sort_order'];

    public const PLACEHOLDER = 'images/product-placeholder.svg';

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function setImagePathAttribute(?string $value): void
    {
        $this->attributes['image_path'] = self::normalizeStoragePath($value);
    }

    public function storagePath(): ?string
    {
        $path = self::normalizeStoragePath($this->image_path);

        return $path && Storage::disk('public')->exists($path) ? $path : null;
    }

    public static function normalizeStoragePath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $path = str_replace('\\', '/', trim($path));
        $path = preg_replace('#^.*?/storage/app/public/#i', '', $path);
        $path = preg_replace('#^.*?/public/storage/#i', '', $path);
        $path = preg_replace('#^/?storage/#i', '', $path);
        $path = ltrim($path, '/');

        return $path ?: null;
    }
}
