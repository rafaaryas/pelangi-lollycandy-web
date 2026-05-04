<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Product;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'products' => Product::count(),
            'inquiries' => Inquiry::count(),
        ];

        $mostClicked = Product::query()->orderByDesc('favorite_clicks')->take(5)->get();

        return view('admin.dashboard', compact('stats', 'mostClicked'));
    }
}
