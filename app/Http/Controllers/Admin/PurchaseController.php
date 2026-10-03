<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\RawMaterial;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\InventoryTransactionService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $items = Purchase::with('supplier')->withCount('details')
            ->when($request->filled('q'), fn ($query) => $query->where('reference_number', 'like', '%'.$request->q.'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('supplier_id'), fn ($query) => $query->where('supplier_id', $request->supplier_id))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('purchase_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('purchase_date', '<=', $request->to))
            ->latest('purchase_date')->latest('id')->paginate(15)->withQueryString();

        return view('admin.transactions.index', [
            'kind' => 'purchases', 'title' => 'Pembelian', 'description' => 'Catat pembelian bahan baku dari supplier.',
            'items' => $items, 'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        return view('admin.transactions.form', [
            'kind' => 'purchases', 'title' => 'Pembelian Baru', 'record' => null,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'materials' => RawMaterial::where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit']),
        ]);
    }

    public function store(Request $request, InventoryTransactionService $service)
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'], 'reference_number' => ['nullable', 'string', 'max:80', 'unique:purchases,reference_number'],
            'purchase_date' => ['required', 'date'], 'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'], 'items.*.raw_material_id' => ['required', 'distinct', 'exists:raw_materials,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'], 'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);
        $header = collect($validated)->only(['supplier_id', 'reference_number', 'purchase_date', 'notes'])->all();
        $lines = collect($validated['items'])->map(fn (array $item): array => [
            'raw_material_id' => $item['raw_material_id'], 'quantity' => $item['quantity'], 'unit_price' => $item['unit_price'],
        ])->all();
        $purchase = $service->savePurchase(null, $header, $lines, $request->input('submit_action') === 'confirm');

        return redirect()->route('admin.purchases.show', $purchase)->with('success', $purchase->status === 'confirmed'
            ? 'Pembelian dikonfirmasi dan stok bahan baku diperbarui.' : 'Draft pembelian berhasil disimpan.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'details.rawMaterial']);
        $movements = StockMovement::where('source_type', PurchaseDetail::class)
            ->whereIn('source_id', $purchase->details->pluck('id'))->with('stockable')->latest('movement_date')->get();

        return view('admin.transactions.show', ['kind' => 'purchases', 'title' => 'Detail Pembelian', 'record' => $purchase, 'movements' => $movements]);
    }

    public function edit(Purchase $purchase)
    {
        abort_unless($purchase->status === 'draft', 404);

        return view('admin.transactions.form', [
            'kind' => 'purchases', 'title' => 'Edit Draft Pembelian', 'record' => $purchase->load('details'),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'materials' => RawMaterial::where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit']),
        ]);
    }

    public function update(Request $request, Purchase $purchase, InventoryTransactionService $service)
    {
        abort_unless($purchase->status === 'draft', 404);
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'], 'reference_number' => ['nullable', 'string', 'max:80', 'unique:purchases,reference_number,'.$purchase->id],
            'purchase_date' => ['required', 'date'], 'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'], 'items.*.raw_material_id' => ['required', 'distinct', 'exists:raw_materials,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'], 'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);
        $header = collect($validated)->only(['supplier_id', 'reference_number', 'purchase_date', 'notes'])->all();
        $lines = collect($validated['items'])->map(fn (array $item): array => [
            'raw_material_id' => $item['raw_material_id'], 'quantity' => $item['quantity'], 'unit_price' => $item['unit_price'],
        ])->all();
        $purchase = $service->savePurchase($purchase, $header, $lines, $request->input('submit_action') === 'confirm');

        return redirect()->route('admin.purchases.show', $purchase)->with('success', $purchase->status === 'confirmed'
            ? 'Pembelian dikonfirmasi dan stok bahan baku diperbarui.' : 'Draft pembelian berhasil diperbarui.');
    }

    public function confirm(Purchase $purchase, InventoryTransactionService $service)
    {
        $service->confirmPurchase($purchase);

        return back()->with('success', 'Pembelian dikonfirmasi dan stok bahan baku diperbarui.');
    }

    public function cancel(Purchase $purchase, InventoryTransactionService $service)
    {
        $service->cancelPurchase($purchase);

        return back()->with('success', 'Pembelian dibatalkan dan pergerakan stok disesuaikan.');
    }
}
