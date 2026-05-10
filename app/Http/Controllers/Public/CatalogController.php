<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $sort = match ($request->query('sort')) {
            'cheapest', 'highest', 'popular' => $request->query('sort'),
            default => 'latest',
        };

        $products = Product::query()
            ->visible()
            ->with(['category', 'images'])
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
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('public.products', compact('products', 'categories', 'sort'));
    }

    public function show(Product $product)
    {
        Product::query()->whereKey($product->id)->increment('click_count');

        $product->load(['images', 'category']);
        $relatedProducts = Product::query()
            ->visible()
            ->with('images')
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->take(4)
            ->get();

        return view('public.product-detail', compact('product', 'relatedProducts'));
    }
}
