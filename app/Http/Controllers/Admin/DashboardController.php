<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Product;
use App\Models\ProductClickLog;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'products' => Product::count(),
            'scheduled' => Product::whereNotNull('published_at')->where('published_at', '>', now())->count(),
            'inquiries' => Inquiry::count(),
            'clicks' => ProductClickLog::count(),
        ];

        $mostClicked = Product::query()->orderByDesc('favorite_clicks')->take(5)->get();

        return view('admin.dashboard', compact('stats', 'mostClicked'));
    }
}
