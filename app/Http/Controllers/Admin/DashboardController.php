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
            'clicks' => Product::sum('click_count'),
        ];

        $mostClicked = Product::query()
            ->where('click_count', '>', 0)
            ->orderByDesc('click_count')
            ->orderBy('name')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'mostClicked'));
    }
}
