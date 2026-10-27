<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\GeminiProductAnalyzer;
use App\Services\ProductAiException;
use Illuminate\Http\Request;

class ProductAiController extends Controller
{
    public function __invoke(Request $request, GeminiProductAnalyzer $analyzer)
    {
        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpeg,png,webp', 'max:4096'],
            'name' => ['nullable', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! config('services.gemini.ai_autofill_enabled')) {
            return response()->json(['message' => 'AI Autofill sedang dinonaktifkan.'], 503);
        }

        $categories = Category::where('is_active', true)->get(['id', 'name']);
        if ($categories->isEmpty()) {
            return response()->json(['message' => 'Belum ada kategori aktif. Aktifkan kategori terlebih dahulu.'], 422);
        }

        try {
            $result = $analyzer->analyze($request->file('image'), $categories, [
                'name' => $validated['name'] ?? '',
                'description' => $validated['description'] ?? '',
            ]);

            return response()->json($result);
        } catch (ProductAiException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->httpStatus);
        }
    }
}
