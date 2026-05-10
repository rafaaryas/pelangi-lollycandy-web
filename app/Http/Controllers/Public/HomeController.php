<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HeroBanner;
use App\Models\MarketplaceLink;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        // Homepage data: keep this compact so the landing page stays fast.
        $hero = HeroBanner::query()->where('is_active', true)->latest()->first();
        $visibleProducts = Product::query()
            ->where('is_active', true)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()));

        $featuredProducts = (clone $visibleProducts)
            ->with(['images', 'variants' => fn ($query) => $query->where('is_active', true)])
            ->latest()
            ->take(6)
            ->get();
        $marketplaces = MarketplaceLink::query()->where('is_active', true)->get();
        $stats = [
            'products' => (clone $visibleProducts)->count(),
            'variants' => Category::query()
                ->where('is_active', true)
                ->whereHas('products', fn ($query) => $query
                    ->where('is_active', true)
                    ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now())))
                ->count(),
            'marketplaces' => $marketplaces->count(),
        ];

        return view('public.home', compact('hero', 'featuredProducts', 'marketplaces', 'stats'));
    }
}
