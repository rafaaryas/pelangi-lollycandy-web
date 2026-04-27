<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\HeroBanner;
use App\Models\MarketplaceLink;
use App\Models\Product;
use App\Models\Testimonial;

class HomeController extends Controller
{
    public function index()
    {
        $hero = HeroBanner::query()->where('is_active', true)->latest()->first();
        $featuredProducts = Product::query()
            ->with('images')
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->latest()
            ->take(6)
            ->get();
        $marketplaces = MarketplaceLink::query()->where('is_active', true)->get();
        $testimonials = Testimonial::query()->where('is_active', true)->latest()->take(3)->get();
        $stats = [
            'products' => Product::query()->where('is_active', true)->count(),
            'variants' => \App\Models\ProductVariant::query()->where('is_active', true)->count(),
            'marketplaces' => $marketplaces->count(),
        ];

        return view('public.home', compact('hero', 'featuredProducts', 'marketplaces', 'testimonials', 'stats'));
    }
}
