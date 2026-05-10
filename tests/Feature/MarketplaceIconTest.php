<?php

namespace Tests\Feature;

use App\Models\MarketplaceLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceIconTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_icon_is_detected_from_platform_name(): void
    {
        $this->assertSame('shopee.svg', MarketplaceLink::make(['platform' => 'SHOPEE'])->iconFilename());
        $this->assertSame('whatsapp.svg', MarketplaceLink::make(['platform' => 'Whats App'])->iconFilename());
        $this->assertSame('tiktok.svg', MarketplaceLink::make(['platform' => 'TikTok Shop'])->iconFilename());
        $this->assertSame('globe.svg', MarketplaceLink::make(['platform' => 'Marketplace Baru'])->iconFilename());
    }

    public function test_homepage_renders_dynamic_marketplace_cards_with_icons(): void
    {
        MarketplaceLink::query()->create([
            'platform' => 'Shopee',
            'url' => 'https://shopee.example',
            'is_active' => true,
        ]);

        MarketplaceLink::query()->create([
            'platform' => 'Marketplace Baru',
            'url' => 'https://marketplace.example',
            'is_active' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Shopee')
            ->assertSee('Marketplace Baru')
            ->assertSee('marketplace-icons/shopee.svg', false)
            ->assertSee('marketplace-icons/globe.svg', false);
    }

    public function test_admin_marketplace_table_renders_detected_icons(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        MarketplaceLink::query()->create([
            'platform' => 'Instagram',
            'url' => 'https://instagram.example',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.marketplace-links.index'))
            ->assertOk()
            ->assertSee('Instagram')
            ->assertSee('marketplace-icons/instagram.svg', false);
    }
}
