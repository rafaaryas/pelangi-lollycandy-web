<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MarketplaceLink extends Model
{
    protected $fillable = ['platform', 'url', 'is_active'];

    private const ICONS = [
        'facebook' => 'facebook.svg',
        'instagram' => 'instagram.svg',
        'shopee' => 'shopee.svg',
        'tiktok' => 'tiktok.svg',
        'tokopedia' => 'tokopedia.svg',
        'whatsapp' => 'whatsapp.svg',
        'website' => 'globe.svg',
    ];

    public function iconPath(): string
    {
        return 'images/marketplace-icons/'.$this->iconFilename();
    }

    public function iconFilename(): string
    {
        $platform = $this->normalizedPlatform();

        foreach (self::ICONS as $keyword => $filename) {
            if (str_contains($platform, $keyword)) {
                return $filename;
            }
        }

        return self::ICONS['website'];
    }

    private function normalizedPlatform(): string
    {
        return Str::of($this->platform)
            ->ascii()
            ->lower()
            ->replace(['-', '_', '.', '/', '&'], ' ')
            ->squish()
            ->replace(' ', '')
            ->value();
    }
}
