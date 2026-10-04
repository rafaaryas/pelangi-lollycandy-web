<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionResult;
use App\Models\Purchase;
use App\Models\RawMaterial;
use App\Models\Sale;
use App\Models\Supplier;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        $now = CarbonImmutable::now();
        $monthStart = $now->startOfMonth();
        $rangeStart = $monthStart->subMonths(5);
        $rangeEnd = $now->endOfMonth();

        $sales = Sale::query()
            ->where('status', 'confirmed')
            ->whereBetween('sale_date', [$monthStart->toDateString(), $now->toDateString()]);
        $productions = Production::query()
            ->where('status', 'confirmed')
            ->whereBetween('production_date', [$monthStart->toDateString(), $now->toDateString()]);

        $salesByMonth = Sale::query()
            ->where('status', 'confirmed')
            ->whereBetween('sale_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->get(['sale_date', 'total_amount'])
            ->groupBy(fn (Sale $sale): string => $sale->sale_date->format('Y-m'));

        $productionResults = ProductionResult::query()
            ->whereHas('production', fn ($query) => $query
                ->where('status', 'confirmed')
                ->whereBetween('production_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()]))
            ->with('production:id,production_date,status')
            ->get(['id', 'production_id', 'quantity_produced']);
        $productionByMonth = $productionResults
            ->groupBy(fn (ProductionResult $result): string => $result->production->production_date->format('Y-m'));

        $months = collect(range(0, 5))->map(function (int $offset) use ($rangeStart, $salesByMonth, $productionByMonth): array {
            $month = $rangeStart->addMonths($offset);
            $key = $month->format('Y-m');

            return [
                'key' => $key,
                'label' => $month->locale('id')->translatedFormat('M'),
                'sales' => $salesByMonth->get($key, collect())->sum(fn (Sale $sale): float => (float) $sale->total_amount),
                'production' => $productionByMonth->get($key, collect())->sum(fn (ProductionResult $result): float => (float) $result->quantity_produced),
            ];
        });

        $attentionStock = $this->stockAlerts();
        $untrackedProductStock = Product::where('is_active', true)->whereNull('stock_quantity')->count();

        return view('admin.dashboard', [
            'stats' => [
                'products' => Product::where('is_active', true)->count(),
                'rawMaterials' => RawMaterial::where('is_active', true)->count(),
                'suppliers' => Supplier::where('is_active', true)->count(),
                'customers' => Customer::where('is_active', true)->count(),
                'salesAmount' => (float) $sales->sum('total_amount'),
                'salesCount' => $sales->count(),
                'productionCount' => (clone $productions)->count(),
                'productionQuantity' => (float) ProductionResult::whereHas('production', fn ($query) => $query
                    ->where('status', 'confirmed')
                    ->whereBetween('production_date', [$monthStart->toDateString(), $now->toDateString()]))
                    ->sum('quantity_produced'),
            ],
            'months' => $months,
            'salesMax' => max(1, (float) $months->max('sales')),
            'productionMax' => max(1, (float) $months->max('production')),
            'attentionStock' => $attentionStock,
            'untrackedProductStock' => $untrackedProductStock,
            'activities' => $this->recentActivities(),
            'periodLabel' => $monthStart->locale('id')->translatedFormat('F Y'),
        ]);
    }

    private function stockAlerts(): Collection
    {
        $rawMaterials = RawMaterial::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('current_stock', '<=', 0)
                    ->orWhere(function ($query): void {
                        $query->where('minimum_stock', '>', 0)
                            ->whereColumn('current_stock', '<=', 'minimum_stock');
                    });
            })
            ->orderBy('current_stock')
            ->limit(6)
            ->get()
            ->map(fn (RawMaterial $item): array => [
                'name' => $item->name,
                'quantity' => (float) $item->current_stock,
                'unit' => $item->unit,
                'kind' => 'Bahan baku',
                'status' => (float) $item->current_stock <= 0 ? 'Habis' : 'Rendah',
            ]);

        $products = Product::query()
            ->where('is_active', true)
            ->whereNotNull('stock_quantity')
            ->where(function ($query): void {
                $query->where('stock_quantity', '<=', 0)
                    ->orWhere(function ($query): void {
                        $query->where('minimum_stock', '>', 0)
                            ->whereColumn('stock_quantity', '<=', 'minimum_stock');
                    });
            })
            ->orderBy('stock_quantity')
            ->limit(6)
            ->get(['id', 'name', 'stock_quantity'])
            ->map(fn (Product $item): array => [
                'name' => $item->name,
                'quantity' => (float) $item->stock_quantity,
                'unit' => 'pcs',
                'kind' => 'Produk',
                'status' => (float) $item->stock_quantity <= 0 ? 'Habis' : 'Rendah',
            ]);

        return $rawMaterials->concat($products)
            ->sortBy(fn (array $item): int => $item['status'] === 'Habis' ? 0 : 1)
            ->take(8)
            ->values();
    }

    private function recentActivities(): Collection
    {
        $purchases = Purchase::with('supplier:id,name')->latest('purchase_date')->take(5)->get()
            ->map(fn (Purchase $item): array => [
                'type' => 'Pembelian', 'reference' => $item->reference_number,
                'description' => $item->supplier?->name ?? 'Supplier dihapus',
                'status' => $item->status, 'date' => $item->purchase_date, 'url' => route('admin.purchases.show', $item),
            ]);
        $productions = Production::with('results.product:id,name')->latest('production_date')->take(5)->get()
            ->map(fn (Production $item): array => [
                'type' => 'Produksi', 'reference' => $item->production_number,
                'description' => $item->results->pluck('product.name')->filter()->join(', ') ?: 'Produksi',
                'status' => $item->status, 'date' => $item->production_date, 'url' => route('admin.productions.show', $item),
            ]);
        $sales = Sale::with('customer:id,name')->latest('sale_date')->take(5)->get()
            ->map(fn (Sale $item): array => [
                'type' => 'Penjualan', 'reference' => $item->invoice_number,
                'description' => $item->customer?->name ?? 'Pelanggan umum',
                'status' => $item->status, 'date' => $item->sale_date, 'url' => route('admin.sales.show', $item),
            ]);

        return $purchases->concat($productions)->concat($sales)
            ->sortByDesc('date')
            ->take(12)
            ->values();
    }
}
