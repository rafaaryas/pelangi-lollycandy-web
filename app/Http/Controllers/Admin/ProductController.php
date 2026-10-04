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
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Product admin listing: eager-load related data used by table rows and modals.
        $products = Product::with(['category', 'images'])->withCount(['saleDetails', 'productionResults', 'stockMovements'])
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->q.'%'))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->category_id))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->status === 'active'))
            ->latest()->paginate(12)->withQueryString();
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function show(Product $product)
    {
        $product->load(['category', 'images']);
        $product->loadCount(['saleDetails', 'productionResults', 'stockMovements']);
        $recentMovements = $product->stockMovements()->latest('movement_date')->limit(10)->get();

        return view('admin.products.show', compact('product', 'recentMovements'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateProduct($request);

        DB::transaction(function () use ($request, $validated): void {
            $product = Product::create($validated);
            $this->syncPrimaryImage($product, $request);
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Produk berhasil ditambahkan.']);
        }
        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product)
    {
        $validated = $this->validateProduct($request, $product);

        DB::transaction(function () use ($request, $product, $validated): void {
            $product->update($validated);
            $this->syncPrimaryImage($product, $request);
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Produk berhasil diperbarui.']);
        }
        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        if ($product->saleDetails()->exists() || $product->productionResults()->exists() || $product->stockMovements()->exists()) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'Produk dengan histori transaksi atau stok tidak dapat dihapus. Nonaktifkan produk sebagai gantinya.'], 422);
            }
            return redirect()->route('admin.products.index')->withErrors(['delete' => 'Produk dengan histori transaksi atau stok tidak dapat dihapus. Nonaktifkan produk sebagai gantinya.']);
        }
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }
        $product->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Produk berhasil dihapus.']);
        }
        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dihapus.');
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:190'],
            'description' => ['required', 'string'],
            'price_from' => ['required', 'numeric', 'min:0'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'badge' => ['required', 'in:none,new,best_seller'],
            'shopee_url' => ['nullable', 'url', 'max:255'],
            'whatsapp_url' => ['nullable', 'url', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);
        $validated['slug'] = Str::slug($validated['name']);
        $slugRule = Rule::unique('products', 'slug');
        if ($product) {
            $slugRule->ignore($product->id);
        }
        validator(['slug' => $validated['slug']], ['slug' => ['required', 'max:190', $slugRule]])->validate();
        $validated['is_active'] = $request->boolean('is_active');
        $validated['minimum_stock'] = $validated['minimum_stock'] ?? 0;

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
