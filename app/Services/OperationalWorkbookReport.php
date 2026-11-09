<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionResult;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\RawMaterial;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Supplier;
use App\Support\BusinessWorkbook;

final class OperationalWorkbookReport
{
    public function build(string $from, string $to): string
    {
        $sales = Sale::where('status', 'confirmed')->whereBetween('sale_date', [$from, $to]);
        $purchases = Purchase::where('status', 'confirmed')->whereBetween('purchase_date', [$from, $to]);
        $productions = Production::where('status', 'confirmed')->whereBetween('production_date', [$from, $to]);

        $sections = [
            'MASTER DATA' => [
                'Total produk' => [Product::count(), 'number'],
                'Total kategori' => [Category::count(), 'number'],
                'Total supplier' => [Supplier::count(), 'number'],
                'Total pelanggan' => [Customer::count(), 'number'],
                'Total bahan baku' => [RawMaterial::count(), 'number'],
            ],
            'PENJUALAN DAN PEMBELIAN TERKONFIRMASI' => [
                'Jumlah penjualan' => [(clone $sales)->count(), 'number'],
                'Total penjualan' => [(clone $sales)->sum('total_amount'), 'money'],
                'Jumlah pembelian' => [(clone $purchases)->count(), 'number'],
                'Total pembelian bahan baku' => [(clone $purchases)->sum('total_amount'), 'money'],
                'Jumlah produksi' => [(clone $productions)->count(), 'number'],
                'Hasil produksi (pcs)' => [ProductionResult::whereHas('production', fn ($query) => $query->where('status', 'confirmed')->whereBetween('production_date', [$from, $to]))->sum('quantity_produced'), 'number'],
            ],
            'PERSEDIAAN SAAT LAPORAN DIBUAT' => [
                'Produk dengan stok tercatat' => [Product::whereNotNull('stock_quantity')->count(), 'number'],
                'Total stok produk tercatat (pcs)' => [Product::whereNotNull('stock_quantity')->sum('stock_quantity'), 'number'],
                'Produk stok rendah / habis' => [Product::whereNotNull('stock_quantity')->whereColumn('stock_quantity', '<=', 'minimum_stock')->count(), 'number'],
                'Bahan baku stok rendah / habis' => [RawMaterial::whereColumn('current_stock', '<=', 'minimum_stock')->count(), 'number'],
            ],
        ];

        $datasets = [
            [
                'name' => 'Produk', 'headers' => ['Produk', 'Kategori', 'Harga mulai', 'Stok', 'Stok minimum', 'Status'],
                'types' => ['text', 'text', 'money', 'number', 'number', 'text'],
                'rows' => Product::with('category')->orderBy('id')->lazyById(500)->map(fn ($item) => [$item->name, $item->category?->name, $item->price_from, $item->stock_quantity, $item->minimum_stock, $item->is_active ? 'Aktif' : 'Nonaktif']),
            ],
            [
                'name' => 'Kategori', 'headers' => ['Nama', 'Slug', 'Jumlah produk', 'Status'],
                'types' => ['text', 'text', 'number', 'text'],
                'rows' => Category::withCount('products')->orderBy('id')->lazyById(500)->map(fn ($item) => [$item->name, $item->slug, $item->products_count, $item->is_active ? 'Aktif' : 'Nonaktif']),
            ],
            [
                'name' => 'Bahan Baku', 'headers' => ['Kode', 'Nama', 'Satuan', 'Stok', 'Stok minimum', 'Status'],
                'types' => ['text', 'text', 'text', 'number', 'number', 'text'],
                'rows' => RawMaterial::orderBy('id')->lazyById(500)->map(fn ($item) => [$item->code, $item->name, $item->unit, $item->current_stock, $item->minimum_stock, $item->is_active ? 'Aktif' : 'Nonaktif']),
            ],
            [
                'name' => 'Supplier', 'headers' => ['Kode', 'Nama', 'Pembelian valid (periode)', 'Status'],
                'types' => ['text', 'text', 'number', 'text'],
                'rows' => Supplier::withCount(['purchases' => fn ($query) => $query->where('status', 'confirmed')->whereBetween('purchase_date', [$from, $to])])->orderBy('id')->lazyById(500)->map(fn ($item) => [$item->code, $item->name, $item->purchases_count, $item->is_active ? 'Aktif' : 'Nonaktif']),
            ],
            [
                'name' => 'Pelanggan', 'headers' => ['Nama', 'Penjualan valid (periode)', 'Status'],
                'types' => ['text', 'number', 'text'],
                'rows' => Customer::withCount(['sales' => fn ($query) => $query->where('status', 'confirmed')->whereBetween('sale_date', [$from, $to])])->orderBy('id')->lazyById(500)->map(fn ($item) => [$item->name, $item->sales_count, $item->is_active ? 'Aktif' : 'Nonaktif']),
            ],
            [
                'name' => 'Penjualan', 'headers' => ['Invoice', 'Tanggal', 'Pelanggan', 'Jumlah item', 'Total penjualan'],
                'types' => ['text', 'date', 'text', 'number', 'money'], 'total' => 4,
                'rows' => (clone $sales)->with('customer')->withCount('details')->lazyById(500)->map(fn ($item) => [$item->invoice_number, $item->sale_date->toDateString(), $item->customer?->name ?? 'Pelanggan umum', $item->details_count, $item->total_amount]),
            ],
            [
                'name' => 'Item Penjualan', 'headers' => ['Invoice', 'Tanggal', 'Produk', 'Kuantitas', 'Harga satuan', 'Subtotal'],
                'types' => ['text', 'date', 'text', 'number', 'money', 'money'],
                'rows' => SaleDetail::with(['sale', 'product'])->whereHas('sale', fn ($query) => $query->where('status', 'confirmed')->whereBetween('sale_date', [$from, $to]))->lazyById(500)->map(fn ($item) => [$item->sale->invoice_number, $item->sale->sale_date->toDateString(), $item->product?->name ?? 'Produk dihapus', $item->quantity, $item->unit_price, $item->subtotal]),
            ],
            [
                'name' => 'Pembelian', 'headers' => ['Referensi', 'Tanggal', 'Supplier', 'Jumlah item', 'Total pembelian'],
                'types' => ['text', 'date', 'text', 'number', 'money'], 'total' => 4,
                'rows' => (clone $purchases)->with('supplier')->withCount('details')->lazyById(500)->map(fn ($item) => [$item->reference_number, $item->purchase_date->toDateString(), $item->supplier?->name ?? 'Supplier dihapus', $item->details_count, $item->total_amount]),
            ],
            [
                'name' => 'Item Pembelian', 'headers' => ['Referensi', 'Tanggal', 'Bahan baku', 'Kuantitas', 'Satuan', 'Harga satuan', 'Subtotal'],
                'types' => ['text', 'date', 'text', 'number', 'text', 'money', 'money'],
                'rows' => PurchaseDetail::with(['purchase', 'rawMaterial'])->whereHas('purchase', fn ($query) => $query->where('status', 'confirmed')->whereBetween('purchase_date', [$from, $to]))->lazyById(500)->map(fn ($item) => [$item->purchase->reference_number, $item->purchase->purchase_date->toDateString(), $item->rawMaterial?->name ?? 'Bahan dihapus', $item->quantity, $item->unit ?? $item->rawMaterial?->unit, $item->unit_price, $item->subtotal]),
            ],
            [
                'name' => 'Produksi', 'headers' => ['Nomor produksi', 'Tanggal', 'Jenis bahan', 'Hasil (pcs)'],
                'types' => ['text', 'date', 'number', 'number'],
                'rows' => (clone $productions)->withCount('materials')->withSum('results', 'quantity_produced')->lazyById(500)->map(fn ($item) => [$item->production_number, $item->production_date->toDateString(), $item->materials_count, $item->results_sum_quantity_produced ?? 0]),
            ],
            [
                'name' => 'Hasil Produksi', 'headers' => ['Nomor produksi', 'Tanggal', 'Produk', 'Jumlah (pcs)'],
                'types' => ['text', 'date', 'text', 'number'],
                'rows' => ProductionResult::with(['production', 'product'])->whereHas('production', fn ($query) => $query->where('status', 'confirmed')->whereBetween('production_date', [$from, $to]))->lazyById(500)->map(fn ($item) => [$item->production->production_number, $item->production->production_date->toDateString(), $item->product?->name ?? 'Produk dihapus', $item->quantity_produced]),
            ],
        ];

        return BusinessWorkbook::render($from, $to, $sections, $datasets);
    }
}
