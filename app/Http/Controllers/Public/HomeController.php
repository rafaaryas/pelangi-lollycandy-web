<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceLink;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $featuredProducts = Product::query()
            ->visible()
            ->with(['images', 'category'])
            ->latest()
            ->take(4)
            ->get();

        $marketplaces = MarketplaceLink::query()->where('is_active', true)->get();
        return view('public.home', compact('featuredProducts', 'marketplaces'));
    }
}
