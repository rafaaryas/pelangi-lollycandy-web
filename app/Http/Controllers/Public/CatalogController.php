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
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'integer', 'min:1'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
        ]);
        $sort = match ($request->query('sort')) {
            'cheapest', 'highest' => $request->query('sort'),
            default => 'latest',
        };

        $products = Product::query()
            ->visible()
            ->with(['category', 'images'])
            ->when(! empty($filters['q']), fn ($query) => $query->where('name', 'like', '%'.$filters['q'].'%'))
            ->when(! empty($filters['category']), fn ($query) => $query->where('category_id', $filters['category']))
            ->when(isset($filters['min_price']), fn ($query) => $query->where('price_from', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']), fn ($query) => $query->where('price_from', '<=', $filters['max_price']))
            ->when($sort === 'cheapest', fn ($query) => $query->orderBy('price_from')->orderByDesc('id'))
            ->when($sort === 'highest', fn ($query) => $query->orderByDesc('price_from')->orderByDesc('id'))
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
        abort_unless(Product::query()->visible()->whereKey($product->getKey())->exists(), 404);
        $product->load(['images', 'category']);
        $relatedProducts = Product::query()
            ->visible()
            ->with(['images', 'category'])
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->take(4)
            ->get();

        return view('public.product-detail', compact('product', 'relatedProducts'));
    }
}
