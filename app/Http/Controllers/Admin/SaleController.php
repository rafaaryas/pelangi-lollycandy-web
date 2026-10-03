<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\StockMovement;
use App\Services\InventoryTransactionService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $items = Sale::with('customer')->withCount('details')
            ->when($request->filled('q'), fn ($query) => $query->where('invoice_number', 'like', '%'.$request->q.'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('customer_id'), fn ($query) => $query->where('customer_id', $request->customer_id))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('sale_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('sale_date', '<=', $request->to))
            ->latest('sale_date')->latest('id')->paginate(15)->withQueryString();

        return view('admin.transactions.index', [
            'kind' => 'sales', 'title' => 'Penjualan', 'description' => 'Catat penjualan produk dan pelanggan.',
            'items' => $items, 'customers' => Customer::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        return $this->form(null, 'Penjualan Baru');
    }

    private function form(?Sale $sale, string $title)
    {
        return view('admin.transactions.form', [
            'kind' => 'sales', 'title' => $title, 'record' => $sale,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'price_from', 'stock_quantity']),
        ]);
    }

    public function store(Request $request, InventoryTransactionService $service)
    {
        $validated = $this->validateSale($request);
        $sale = $service->saveSale(null, collect($validated)->only(['customer_id', 'invoice_number', 'sale_date', 'notes'])->all(), $validated['items'], $request->input('submit_action') === 'confirm');

        return redirect()->route('admin.sales.show', $sale)->with('success', $sale->status === 'confirmed'
            ? 'Penjualan dikonfirmasi dan stok produk diperbarui.' : 'Draft penjualan berhasil disimpan.');
    }

    public function show(Sale $sale)
    {
        $sale->load(['customer', 'details.product']);
        $movements = StockMovement::where('source_type', SaleDetail::class)
            ->whereIn('source_id', $sale->details->pluck('id'))->with('stockable')->latest('movement_date')->get();

        return view('admin.transactions.show', ['kind' => 'sales', 'title' => 'Detail Penjualan', 'record' => $sale, 'movements' => $movements]);
    }

    public function edit(Sale $sale)
    {
        abort_unless($sale->status === 'draft', 404);

        return $this->form($sale->load('details'), 'Edit Draft Penjualan');
    }

    public function update(Request $request, Sale $sale, InventoryTransactionService $service)
    {
        abort_unless($sale->status === 'draft', 404);
        $validated = $this->validateSale($request, $sale);
        $sale = $service->saveSale($sale, collect($validated)->only(['customer_id', 'invoice_number', 'sale_date', 'notes'])->all(), $validated['items'], $request->input('submit_action') === 'confirm');

        return redirect()->route('admin.sales.show', $sale)->with('success', $sale->status === 'confirmed'
            ? 'Penjualan dikonfirmasi dan stok produk diperbarui.' : 'Draft penjualan berhasil diperbarui.');
    }

    public function confirm(Sale $sale, InventoryTransactionService $service)
    {
        $service->confirmSale($sale);

        return back()->with('success', 'Penjualan dikonfirmasi dan stok produk diperbarui.');
    }

    public function cancel(Sale $sale, InventoryTransactionService $service)
    {
        $service->cancelSale($sale);

        return back()->with('success', 'Penjualan dibatalkan dan stok produk dikembalikan.');
    }

    private function validateSale(Request $request, ?Sale $sale = null): array
    {
        return $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'invoice_number' => ['nullable', 'string', 'max:80', 'unique:sales,invoice_number'.($sale ? ','.$sale->id : '')],
            'sale_date' => ['required', 'date'], 'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'], 'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'], 'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);
    }
}
