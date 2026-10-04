<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        // Category admin listing: products are grouped by these records.
        $categories = Category::withCount('products')
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->q.'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->status === 'active'))
            ->latest()->paginate(15)->withQueryString();

        return view('admin.categories.categories', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:240'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');
        Category::create($validated);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Kategori berhasil ditambahkan.']);
        }

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:240'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');
        $category->update($validated);

        if (! $validated['is_active']) {
            $category->products()->update(['is_active' => false]);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Kategori berhasil diperbarui.']);
        }

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil diupdate.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'Kategori masih memiliki produk. Pindahkan produk ke kategori lain sebelum menghapus kategori ini.'], 422);
            }
            return redirect()->route('admin.categories.index')->withErrors(['delete' => 'Kategori masih memiliki produk. Pindahkan produk ke kategori lain sebelum menghapus kategori ini.']);
        }
        $category->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Kategori berhasil dihapus.']);
        }

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
