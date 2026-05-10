<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MarketplaceLink;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $featuredProducts = Product::query()
            ->visible()
            ->with('images')
            ->latest()
            ->take(6)
            ->get();

        $marketplaces = MarketplaceLink::query()->where('is_active', true)->get();
        $stats = [
            'products' => Product::query()->visible()->count(),
            'categories' => Category::query()
                ->where('is_active', true)
                ->whereHas('products', fn ($query) => $query->visible())
                ->count(),
            'marketplaces' => $marketplaces->count(),
        ];

        return view('public.home', compact('featuredProducts', 'marketplaces', 'stats'));
    }
}
