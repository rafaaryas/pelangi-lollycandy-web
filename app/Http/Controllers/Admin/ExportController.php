<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\MarketplaceLink;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionResult;
use App\Models\Purchase;
use App\Models\RawMaterial;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\RawMaterialStockReport;
use App\Support\TabularExport;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function __invoke(Request $request, string $module, RawMaterialStockReport $stockReport)
    {
        abort_unless(in_array($module, ['dashboard', 'categories', 'products', 'raw-materials', 'suppliers', 'customers', 'purchases', 'productions', 'sales', 'stock', 'stock-history', 'reports', 'marketplace-links'], true), 404);
        $filters = $request->validate([
            'format' => ['required', 'in:xlsx,csv,pdf'],
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:30'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from'],
            'tab' => ['nullable', 'string', 'max:20'], 'mode' => ['nullable', 'in:summary,detail'],
            'month' => ['nullable', 'integer', 'between:1,12'], 'year' => ['nullable', 'integer', 'between:2000,2100'],
            'type' => ['nullable', 'in:products,materials'], 'id' => ['nullable', 'integer', 'min:1'],
        ]);
        $format = $filters['format'];
        try {
            [$title, $headers, $rows, $numeric, $period] = $this->dataset($module, $request, $stockReport);
            $names = [
                'dashboard' => 'dashboard', 'categories' => 'kategori_produk', 'products' => 'produk',
                'raw-materials' => 'bahan_baku', 'suppliers' => 'supplier', 'customers' => 'pelanggan',
                'purchases' => 'pembelian', 'productions' => 'produksi', 'sales' => 'penjualan',
                'stock' => 'stok', 'stock-history' => 'riwayat_stok', 'reports' => 'laporan',
                'marketplace-links' => 'marketplace',
            ];
            $stem = $names[$module];
            if ($module === 'reports') $stem .= '_'.($request->query('tab') ?: 'sales');
            if ($module === 'stock') $stem .= '_'.($request->query('tab') === 'products' ? 'produk' : 'bahan_baku');
            if ($module === 'stock-history') $stem .= '_'.$request->query('type').'-'.$request->query('id');
            $dateTag = $request->filled('from') && $request->filled('to') && substr($request->query('from'), 0, 7) === substr($request->query('to'), 0, 7)
                ? substr($request->query('from'), 0, 7) : now()->format('Y-m-d');
            if ($module === 'dashboard') $dateTag = now()->format('Y-m');
            if ($module === 'reports' && $request->query('tab') === 'stock') $dateTag = sprintf('%04d-%02d', (int) ($request->query('year') ?: now()->year), (int) ($request->query('month') ?: now()->month));
            $filename = $stem.'_'.$dateTag.'.'.$format;
            if ($format === 'csv') {
                return response()->streamDownload(fn () => TabularExport::csv($headers, $rows), $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
            }
            $content = $format === 'xlsx'
                ? TabularExport::xlsx($title, $headers, $rows, $numeric)
                : TabularExport::pdf($title, $period, $headers, $rows, $numeric);
            return response($content, 200, [
                'Content-Type' => $format === 'xlsx' ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'Content-Length' => strlen($content),
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['export' => $exception->getMessage()]);
        }
    }

    private function dataset(string $module, Request $request, RawMaterialStockReport $stockReport): array
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $period = 'Seluruh data sesuai filter aktif';
        if ($module === 'dashboard') {
            $start = now()->startOfMonth(); $end = now();
            $period = $start->format('d-m-Y').' s.d. '.$end->format('d-m-Y');
            $rows = collect([
                ['Produk aktif', Product::where('is_active', true)->count()],
                ['Bahan baku aktif', RawMaterial::where('is_active', true)->count()],
                ['Supplier aktif', Supplier::where('is_active', true)->count()],
                ['Pelanggan aktif', Customer::where('is_active', true)->count()],
                ['Penjualan terkonfirmasi', Sale::where('status', 'confirmed')->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])->count()],
                ['Nilai penjualan (IDR)', Sale::where('status', 'confirmed')->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])->sum('total_amount')],
                ['Produksi terkonfirmasi', Production::where('status', 'confirmed')->whereBetween('production_date', [$start->toDateString(), $end->toDateString()])->count()],
                ['Hasil produksi (pcs)', ProductionResult::whereHas('production', fn ($query) => $query->where('status', 'confirmed')->whereBetween('production_date', [$start->toDateString(), $end->toDateString()]))->sum('quantity_produced')],
            ]);
            return ['Dashboard', ['Metrik', 'Nilai'], $rows, [1], $period];
        }
        if ($module === 'categories') {
            $rows = Category::withCount('products')->when($q !== '', fn ($query) => $query->where('name', 'like', '%'.$q.'%'))
                ->when(in_array($status, ['active', 'inactive'], true), fn ($query) => $query->where('is_active', $status === 'active'))
                ->latest()->lazy(500)->map(fn ($item) => [$item->name, $item->slug, $item->description, $item->products_count, $item->is_active ? 'Aktif' : 'Nonaktif']);
            return ['Kategori Produk', ['Nama', 'Slug', 'Deskripsi', 'Jumlah Produk', 'Status'], $rows, [3], $period];
        }
        if ($module === 'marketplace-links') {
            $rows = MarketplaceLink::query()->orderBy('platform')->lazy(500)->map(fn ($item) => [$item->platform, $item->url, $item->is_active ? 'Aktif' : 'Nonaktif']);
            return ['Marketplace', ['Platform', 'URL', 'Status'], $rows, [], $period];
        }
        if ($module === 'products') {
            $rows = Product::with('category')->when($q !== '', fn ($query) => $query->where('name', 'like', '%'.$q.'%'))
                ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->query('category_id')))
                ->when(in_array($status, ['active', 'inactive'], true), fn ($query) => $query->where('is_active', $status === 'active'))
                ->latest()->lazy(500)->map(fn ($item) => [$item->name, $item->category?->name, (float) $item->price_from, $item->stock_quantity === null ? null : (float) $item->stock_quantity, (float) $item->minimum_stock, $item->badge, $item->is_active ? 'Aktif' : 'Nonaktif', $item->updated_at?->format('Y-m-d H:i')]);
            return ['Produk', ['Nama', 'Kategori', 'Harga (IDR)', 'Stok', 'Stok Minimum', 'Badge', 'Status', 'Diperbarui'], $rows, [2,3,4], $period];
        }
        if (in_array($module, ['raw-materials', 'suppliers', 'customers'], true)) {
            $class = ['raw-materials' => RawMaterial::class, 'suppliers' => Supplier::class, 'customers' => Customer::class][$module];
            $query = $class::query()->when($q !== '', function ($query) use ($q, $module) {
                $query->where(fn ($nested) => $nested->where('name', 'like', '%'.$q.'%')
                    ->when($module !== 'customers', fn ($nested) => $nested->orWhere('code', 'like', '%'.$q.'%')));
            })->when(in_array($status, ['active', 'inactive'], true), fn ($query) => $query->where('is_active', $status === 'active'))->orderBy('name');
            if ($module === 'raw-materials') return ['Bahan Baku', ['Kode', 'Nama', 'Stok', 'Minimum', 'Satuan', 'Status'], $query->lazy(500)->map(fn ($item) => [$item->code, $item->name, (float) $item->current_stock, (float) $item->minimum_stock, $item->unit, $item->is_active ? 'Aktif' : 'Nonaktif']), [2,3], $period];
            $relation = $module === 'suppliers' ? 'purchases' : 'sales';
            $rows = $query->withCount($relation)->lazy(500)->map(fn ($item) => [$item->code ?? '', $item->name, $item->phone, $item->email, $item->{$relation.'_count'}, $item->is_active ? 'Aktif' : 'Nonaktif']);
            return [$module === 'suppliers' ? 'Supplier' : 'Pelanggan', ['Kode', 'Nama', 'Telepon', 'Email', 'Jumlah Transaksi', 'Status'], $rows, [4], $period];
        }
        if (in_array($module, ['purchases', 'productions', 'sales'], true)) {
            $class = ['purchases' => Purchase::class, 'productions' => Production::class, 'sales' => Sale::class][$module];
            $reference = ['purchases' => 'reference_number', 'productions' => 'production_number', 'sales' => 'invoice_number'][$module];
            $date = ['purchases' => 'purchase_date', 'productions' => 'production_date', 'sales' => 'sale_date'][$module];
            $query = $class::query()->when($q !== '', fn ($query) => $query->where($reference, 'like', '%'.$q.'%'))
                ->when(in_array($status, ['draft', 'confirmed', 'cancelled'], true), fn ($query) => $query->where('status', $status))
                ->when($request->filled('from'), fn ($query) => $query->whereDate($date, '>=', $request->query('from')))
                ->when($request->filled('to'), fn ($query) => $query->whereDate($date, '<=', $request->query('to')))
                ->when($module === 'purchases' && $request->filled('supplier_id'), fn ($query) => $query->where('supplier_id', $request->query('supplier_id')))
                ->when($module === 'sales' && $request->filled('customer_id'), fn ($query) => $query->where('customer_id', $request->query('customer_id')))
                ->orderByDesc($date)->orderByDesc('id');
            $period = ($request->query('from') ?: 'Awal').' s.d. '.($request->query('to') ?: 'Sekarang').'; ringkasan per transaksi';
            if ($module === 'productions') return ['Produksi', ['Nomor', 'Tanggal', 'Jumlah Bahan', 'Hasil (pcs)', 'Status'], $query->withCount('materials')->withSum('results', 'quantity_produced')->lazy(500)->map(fn ($item) => [$item->{$reference}, $item->{$date}->toDateString(), $item->materials_count, (float) ($item->results_sum_quantity_produced ?? 0), $item->status]), [2,3], $period];
            $query = $query->with($module === 'purchases' ? 'supplier' : 'customer')->withCount('details');
            $rows = $query->lazy(500)->map(fn ($item) => [$item->{$reference}, $item->{$date}->toDateString(), $module === 'purchases' ? ($item->supplier?->name ?? '') : ($item->customer?->name ?? 'Pelanggan umum'), $item->details_count, (float) $item->total_amount, $item->status]);
            return [$module === 'purchases' ? 'Pembelian' : 'Penjualan', ['Nomor', 'Tanggal', $module === 'purchases' ? 'Supplier' : 'Pelanggan', 'Jumlah Item', 'Total (IDR)', 'Status'], $rows, [3,4], $period];
        }
        if ($module === 'stock') {
            $isProduct = $request->query('tab') === 'products';
            $query = $isProduct ? Product::with('category') : RawMaterial::query();
            $column = $isProduct ? 'stock_quantity' : 'current_stock';
            $query->when($q !== '', fn ($query) => $query->where(fn ($nested) => $nested->where('name', 'like', '%'.$q.'%')->orWhere('id', $q)))
                ->when($status === 'out', fn ($query) => $query->where($column, '<=', 0))
                ->when($status === 'low', fn ($query) => $query->where($column, '>', 0)->where('minimum_stock', '>', 0)->whereColumn($column, '<=', 'minimum_stock'))
                ->when($status === 'unknown' && $isProduct, fn ($query) => $query->whereNull($column))->orderBy('name');
            $rows = $query->lazy(500)->map(fn ($item) => [$isProduct ? 'PR-'.$item->id : $item->code, $item->name, $isProduct ? $item->category?->name : '', $item->{$column} === null ? null : (float) $item->{$column}, (float) $item->minimum_stock, $isProduct ? 'pcs' : $item->unit]);
            return ['Stok '.($isProduct ? 'Produk' : 'Bahan Baku'), ['Kode', 'Nama', 'Kategori', 'Stok', 'Minimum', 'Satuan'], $rows, [3,4], $period];
        }
        if ($module === 'stock-history') {
            abort_unless($request->filled('type') && $request->filled('id'), 422);
            $class = $request->query('type') === 'products' ? Product::class : RawMaterial::class;
            $item = $class::findOrFail($request->query('id'));
            $current = $item instanceof Product ? $item->stock_quantity : $item->current_stock;
            $net = (float) $item->stockMovements()->selectRaw("COALESCE(SUM(CASE WHEN movement_type IN ('purchase_in', 'production_in', 'adjustment_in') THEN quantity ELSE -quantity END), 0) AS net")->value('net');
            $balance = $current === null ? $net : (float) $current;
            $rows = $item->stockMovements()->withSourceDocument()->orderByDesc('movement_date')->orderByDesc('id')->lazy(500)->map(function ($move) use (&$balance) {
                $incoming = in_array($move->movement_type, ['purchase_in', 'production_in', 'adjustment_in'], true) ? (float) $move->quantity : 0;
                $outgoing = $incoming > 0 ? 0 : (float) $move->quantity;
                $rowBalance = $balance;
                $balance -= $incoming - $outgoing;
                return [$move->movement_date->format('Y-m-d H:i'), $move->movement_type, $move->reference, $incoming, $outgoing, $rowBalance, $move->unit];
            });
            return ['Riwayat Stok '.$item->name, ['Tanggal', 'Jenis', 'Referensi', 'Masuk', 'Keluar', 'Saldo', 'Satuan'], $rows, [3,4,5], $period];
        }
        $tab = in_array($request->query('tab'), ['sales', 'purchases', 'productions', 'stock'], true) ? $request->query('tab') : 'sales';
        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();
        if ($tab === 'stock') {
            $month = (int) ($request->query('month') ?: now()->month); $year = (int) ($request->query('year') ?: now()->year);
            $date = CarbonImmutable::create($year, $month, 1); $from = $date->startOfMonth()->toDateString(); $to = $date->endOfMonth()->toDateString();
        }
        $period = $from.' s.d. '.$to.'; transaksi terkonfirmasi';
        if ($tab === 'stock' && $request->query('mode') !== 'detail') {
            $rows = $stockReport->summary($from, $to)->map(fn ($row) => [$row['material']->name, $row['material']->unit, $row['opening'], $row['incoming'], $row['used'], $row['closing']]);
            return ['Laporan Stok', ['Bahan Baku', 'Satuan', 'Stok Awal', 'Masuk', 'Terpakai', 'Stok Akhir'], $rows, [2,3,4,5], $period];
        }
        if ($tab === 'stock') {
            $rows = StockMovement::with(['stockable', 'source'])->where('stockable_type', RawMaterial::class)->whereBetween('movement_date', [$from.' 00:00:00', $to.' 23:59:59'])->orderByDesc('movement_date')->orderByDesc('id')->lazy(500)
                ->map(fn ($item) => [$item->movement_date->format('Y-m-d H:i'), $item->stockable?->name, $item->movement_type, (float) $item->quantity, $item->unit]);
            return ['Detail Stok', ['Tanggal', 'Bahan Baku', 'Jenis', 'Jumlah', 'Satuan'], $rows, [3], $period];
        }
        $class = ['sales' => Sale::class, 'purchases' => Purchase::class, 'productions' => Production::class][$tab];
        $date = ['sales' => 'sale_date', 'purchases' => 'purchase_date', 'productions' => 'production_date'][$tab];
        $reference = ['sales' => 'invoice_number', 'purchases' => 'reference_number', 'productions' => 'production_number'][$tab];
        $query = $class::where('status', 'confirmed')->whereBetween($date, [$from, $to])->orderByDesc($date)->orderByDesc('id');
        if ($tab === 'productions') return ['Laporan Produksi', ['Nomor', 'Tanggal', 'Jumlah Bahan', 'Hasil (pcs)'], $query->withCount('materials')->withSum('results', 'quantity_produced')->lazy(500)->map(fn ($item) => [$item->{$reference}, $item->{$date}->toDateString(), $item->materials_count, (float) ($item->results_sum_quantity_produced ?? 0)]), [2,3], $period];
        $query->with($tab === 'sales' ? 'customer' : 'supplier')->withCount('details');
        return ['Laporan '.($tab === 'sales' ? 'Penjualan' : 'Pembelian'), ['Nomor', 'Tanggal', $tab === 'sales' ? 'Pelanggan' : 'Supplier', 'Jumlah Item', 'Total (IDR)'], $query->lazy(500)->map(fn ($item) => [$item->{$reference}, $item->{$date}->toDateString(), $tab === 'sales' ? ($item->customer?->name ?? 'Pelanggan umum') : ($item->supplier?->name ?? ''), $item->details_count, (float) $item->total_amount]), [3,4], $period];
    }
}
