<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductClickLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $sort = match ($request->query('sort')) {
            'cheapest', 'highest', 'popular' => $request->query('sort'),
            default => 'latest',
        };

        $products = Product::query()
            ->with(['category', 'images', 'variants' => fn ($query) => $query->where('is_active', true)])
            ->where('is_active', true)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->q.'%'))
            ->when($request->filled('category'), fn ($query) => $query->where('category_id', $request->integer('category')))
            ->when($request->filled('min_price'), fn ($query) => $query->where('price_from', '>=', $request->min_price))
            ->when($request->filled('max_price'), fn ($query) => $query->where('price_from', '<=', $request->max_price))
            ->when($sort === 'cheapest', fn ($query) => $query->orderBy('price_from')->orderByDesc('id'))
            ->when($sort === 'highest', fn ($query) => $query->orderByDesc('price_from')->orderByDesc('id'))
            ->when($sort === 'popular', fn ($query) => $query->orderByDesc('click_count')->orderByDesc('id'))
            ->when($sort === 'latest', fn ($query) => $query->latest())
            ->paginate(12)
            ->withQueryString();

        // Collapsible category filter data shown above the catalog grid.
        $categories = Category::query()
            ->with(['products' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('public.products', compact('products', 'categories', 'sort'));
    }

    public function show(Product $product)
    {
        Product::query()
            ->whereKey($product->id)
            ->update(['click_count' => DB::raw('COALESCE(click_count, 0) + 1')]);

        $product->load(['images', 'variants', 'category']);
        $relatedProducts = Product::query()
            ->with('images')
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->take(4)
            ->get();

        return view('public.product-detail', compact('product', 'relatedProducts'));
    }

    public function click(Product $product, Request $request)
    {
        // Click counter used by the admin dashboard "Most Clicked Products" widget.
        ProductClickLog::query()->create([
            'product_id' => $product->id,
            'target' => $request->input('target', 'marketplace'),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        $product->increment('favorite_clicks');

        return response()->json(['ok' => true]);
    }
}
