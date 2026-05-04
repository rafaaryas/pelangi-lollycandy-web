<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'images', 'variants'])->latest()->paginate(12);
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        return view('admin.products.index', compact('products', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateProduct($request);

        DB::transaction(function () use ($request, $validated): void {
            $product = Product::create($validated);
            $this->syncPrimaryImage($product, $request);
            $this->syncVariants($product, $request);
        });

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product)
    {
        $validated = $this->validateProduct($request, $product->id);

        DB::transaction(function () use ($request, $product, $validated): void {
            $product->update($validated);
            $this->syncPrimaryImage($product, $request);
            $this->syncVariants($product, $request);
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

    private function validateProduct(Request $request, ?int $productId = null): array
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:190'],
            'description' => ['required', 'string'],
            'price_from' => ['required', 'numeric', 'min:0'],
            'price_strike' => ['nullable', 'numeric', 'min:0'],
            'badge' => ['required', 'in:none,new,best_seller'],
            'sku' => ['required', 'string', 'max:80', Rule::unique('products', 'sku')->ignore($productId)],
            'weight_gram' => ['required', 'integer', 'min:0'],
            'flavor' => ['nullable', 'string', 'max:100'],
            'shopee_url' => ['nullable', 'url', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'variants' => ['nullable', 'array'],
            'variants.*.size' => ['nullable', 'string', 'max:100'],
            'variants.*.flavor' => ['nullable', 'string', 'max:100'],
            'variants.*.stock_info' => ['nullable', 'string', 'max:120'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.is_active' => ['nullable', 'boolean'],
        ]);
        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    private function syncPrimaryImage(Product $product, Request $request): void
    {
        if (!$request->hasFile('image')) {
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

    private function syncVariants(Product $product, Request $request): void
    {
        $variants = collect($request->input('variants', []))
            ->filter(fn (array $variant) => !empty($variant['size']) || !empty($variant['flavor']) || !empty($variant['price']))
            ->values();

        $existingIds = $product->variants()->pluck('id')->all();
        $incomingIds = $variants->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
        $toDelete = array_diff($existingIds, $incomingIds);
        if (!empty($toDelete)) {
            ProductVariant::whereIn('id', $toDelete)->delete();
        }

        foreach ($variants as $variant) {
            $payload = [
                'size' => $variant['size'] ?? '-',
                'flavor' => $variant['flavor'] ?? '-',
                'stock_info' => $variant['stock_info'] ?? null,
                'price' => $variant['price'] ?? 0,
                'is_active' => (bool) ($variant['is_active'] ?? true),
            ];

            if (!empty($variant['id'])) {
                $product->variants()->where('id', $variant['id'])->update($payload);
            } else {
                $product->variants()->create($payload);
            }
        }
    }
}
