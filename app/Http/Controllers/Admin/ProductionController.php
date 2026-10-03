<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionMaterial;
use App\Models\ProductionResult;
use App\Models\RawMaterial;
use App\Models\StockMovement;
use App\Services\InventoryTransactionService;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        $items = Production::with(['materials.rawMaterial', 'results.product'])
            ->when($request->filled('q'), fn ($query) => $query->where('production_number', 'like', '%'.$request->q.'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('production_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('production_date', '<=', $request->to))
            ->latest('production_date')->latest('id')->paginate(15)->withQueryString();

        return view('admin.transactions.index', [
            'kind' => 'productions', 'title' => 'Produksi', 'description' => 'Kelola pemakaian bahan baku dan hasil produksi.', 'items' => $items,
        ]);
    }

    public function create()
    {
        return $this->form(null, 'Produksi Baru');
    }

    private function form(?Production $production, string $title)
    {
        return view('admin.transactions.form', [
            'kind' => 'productions', 'title' => $title, 'record' => $production,
            'materials' => RawMaterial::where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit', 'current_stock']),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'stock_quantity']),
        ]);
    }

    public function store(Request $request, InventoryTransactionService $service)
    {
        $validated = $this->validateProduction($request);
        $production = $service->saveProduction(null, collect($validated)->only(['production_number', 'production_date', 'notes'])->all(), $validated['materials'], $validated['results'], $request->input('submit_action') === 'confirm');

        return redirect()->route('admin.productions.show', $production)->with('success', $production->status === 'confirmed'
            ? 'Produksi dikonfirmasi; stok bahan dan produk diperbarui.' : 'Draft produksi berhasil disimpan.');
    }

    public function show(Production $production)
    {
        $production->load(['materials.rawMaterial', 'results.product']);
        $sourceIds = $production->materials->pluck('id')->merge($production->results->pluck('id'));
        $movements = StockMovement::whereIn('source_type', [ProductionMaterial::class, ProductionResult::class])
            ->whereIn('source_id', $sourceIds)->with('stockable')->latest('movement_date')->get();

        return view('admin.transactions.show', ['kind' => 'productions', 'title' => 'Detail Produksi', 'record' => $production, 'movements' => $movements]);
    }

    public function edit(Production $production)
    {
        abort_unless($production->status === 'draft', 404);

        return $this->form($production->load(['materials', 'results']), 'Edit Draft Produksi');
    }

    public function update(Request $request, Production $production, InventoryTransactionService $service)
    {
        abort_unless($production->status === 'draft', 404);
        $validated = $this->validateProduction($request, $production);
        $production = $service->saveProduction($production, collect($validated)->only(['production_number', 'production_date', 'notes'])->all(), $validated['materials'], $validated['results'], $request->input('submit_action') === 'confirm');

        return redirect()->route('admin.productions.show', $production)->with('success', $production->status === 'confirmed'
            ? 'Produksi dikonfirmasi; stok bahan dan produk diperbarui.' : 'Draft produksi berhasil diperbarui.');
    }

    public function confirm(Production $production, InventoryTransactionService $service)
    {
        $service->confirmProduction($production);

        return back()->with('success', 'Produksi dikonfirmasi; stok bahan dan produk diperbarui.');
    }

    public function cancel(Production $production, InventoryTransactionService $service)
    {
        $service->cancelProduction($production);

        return back()->with('success', 'Produksi dibatalkan dan pergerakan stok disesuaikan.');
    }

    private function validateProduction(Request $request, ?Production $production = null): array
    {
        return $request->validate([
            'production_number' => ['nullable', 'string', 'max:80', 'unique:productions,production_number'.($production ? ','.$production->id : '')],
            'production_date' => ['required', 'date'], 'notes' => ['nullable', 'string'],
            'materials' => ['required', 'array', 'min:1'], 'materials.*.raw_material_id' => ['required', 'distinct', 'exists:raw_materials,id'],
            'materials.*.quantity_used' => ['required', 'numeric', 'gt:0'],
            'results' => ['required', 'array', 'min:1'], 'results.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'results.*.quantity_produced' => ['required', 'numeric', 'gt:0'],
        ]);
    }
}
