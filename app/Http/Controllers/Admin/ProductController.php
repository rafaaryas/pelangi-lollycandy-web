<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        // Product admin listing: eager-load related data used by table rows and modals.
        $products = Product::with(['category', 'images'])->latest()->paginate(12);
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateProduct($request);

        DB::transaction(function () use ($request, $validated): void {
            $product = Product::create($validated);
            $this->syncPrimaryImage($product, $request);
        });

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product)
    {
        $validated = $this->validateProduct($request);

        DB::transaction(function () use ($request, $product, $validated): void {
            $product->update($validated);
            $this->syncPrimaryImage($product, $request);
        });

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diupdate.');
    }

    public function destroy(Product $product)
    {
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dihapus.');
    }

    private function validateProduct(Request $request): array
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:190'],
            'description' => ['required', 'string'],
            'price_from' => ['required', 'numeric', 'min:0'],
            'badge' => ['required', 'in:none,new,best_seller'],
            'shopee_url' => ['nullable', 'url', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);
        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    private function syncPrimaryImage(Product $product, Request $request): void
    {
        // Product images are stored on Laravel's public disk:
        // storage/app/public/products. Run `php artisan storage:link` so that
        // public/storage points there and asset('storage/'.$path) can serve them.
        if (! $request->hasFile('image')) {
            return;
        }

        $currentImage = $product->images()->where('is_primary', true)->first();
        if ($currentImage) {
            Storage::disk('public')->delete($currentImage->image_path);
            $currentImage->delete();
        }

        $path = $request->file('image')->store('products', 'public');
        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => $path,
            'is_primary' => true,
            'sort_order' => 0,
        ]);
    }
}
