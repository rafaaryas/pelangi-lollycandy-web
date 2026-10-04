<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Production;
use App\Models\ProductionResult;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Services\RawMaterialStockReport;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request, RawMaterialStockReport $stockReport)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
        ]);
        $tab = in_array($request->query('tab'), ['sales', 'purchases', 'productions', 'stock'], true) ? $request->query('tab') : 'sales';
        $month = (int) ($validated['month'] ?? now()->month);
        $year = (int) ($validated['year'] ?? now()->year);
        $stockMonth = CarbonImmutable::create($year, $month, 1);
        $from = $tab === 'stock' ? $stockMonth->startOfMonth()->toDateString() : (isset($validated['from']) ? date('Y-m-d', strtotime($validated['from'])) : now()->startOfMonth()->toDateString());
        $to = $tab === 'stock' ? $stockMonth->endOfMonth()->toDateString() : (isset($validated['to']) ? date('Y-m-d', strtotime($validated['to'])) : now()->toDateString());
        $mode = $request->query('mode') === 'detail' ? 'detail' : 'summary';
        $stockRows = collect();
        $items = collect();
        $summary = ['count' => 0, 'amount' => 0, 'quantity' => 0, 'in' => 0, 'out' => 0];

        if ($tab === 'sales') {
            $items = Sale::with('customer')->withCount('details')->whereBetween('sale_date', [$from, $to])->where('status', 'confirmed')->latest('sale_date')->paginate(20)->withQueryString();
            $summary['count'] = $items->total();
            $summary['amount'] = Sale::whereBetween('sale_date', [$from, $to])->where('status', 'confirmed')->sum('total_amount');
            $summary['quantity'] = SaleDetail::query()->join('sales', 'sales.id', '=', 'sale_details.sale_id')
                ->whereBetween('sales.sale_date', [$from, $to])->where('sales.status', 'confirmed')->sum('sale_details.quantity');
        } elseif ($tab === 'purchases') {
            $items = Purchase::with('supplier')->withCount('details')->whereBetween('purchase_date', [$from, $to])->where('status', 'confirmed')->latest('purchase_date')->paginate(20)->withQueryString();
            $summary['count'] = $items->total();
            $summary['amount'] = Purchase::whereBetween('purchase_date', [$from, $to])->where('status', 'confirmed')->sum('total_amount');
            $summary['quantity'] = PurchaseDetail::query()->join('purchases', 'purchases.id', '=', 'purchase_details.purchase_id')
                ->whereBetween('purchases.purchase_date', [$from, $to])->where('purchases.status', 'confirmed')->sum('purchase_details.quantity');
        } elseif ($tab === 'productions') {
            $items = Production::with(['materials.rawMaterial', 'results.product'])->whereBetween('production_date', [$from, $to])->where('status', 'confirmed')->latest('production_date')->paginate(20)->withQueryString();
            $summary['count'] = $items->total();
            $summary['quantity'] = ProductionResult::query()->join('productions', 'productions.id', '=', 'production_results.production_id')
                ->whereBetween('productions.production_date', [$from, $to])->where('productions.status', 'confirmed')->sum('production_results.quantity_produced');
        } else {
            $stockRows = $stockReport->summary($from, $to);
            $summary['count'] = $stockRows->count();
            $summary['in'] = $stockRows->filter(fn (array $row) => $row['incoming'] > 0)->count();
            $summary['out'] = $stockRows->filter(fn (array $row) => $row['used'] > 0)->count();
            if ($mode === 'detail') $items = $stockReport->details($from, $to);
        }

        return view('admin.reports.index', compact('tab', 'from', 'to', 'month', 'year', 'mode', 'stockRows', 'items', 'summary'));
    }
}
