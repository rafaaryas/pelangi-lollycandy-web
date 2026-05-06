<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'products' => Product::count(),
            'categories' => Category::count(),
            'clicks' => Product::sum('favorite_clicks'),
        ];

        $mostClicked = Product::query()
            ->where('favorite_clicks', '>', 0)
            ->orderByDesc('favorite_clicks')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'mostClicked'));
    }
}
