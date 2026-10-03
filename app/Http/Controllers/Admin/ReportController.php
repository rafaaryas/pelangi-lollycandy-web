<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Production;
use App\Models\ProductionResult;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $tab = in_array($request->query('tab'), ['sales', 'purchases', 'productions', 'stock'], true) ? $request->query('tab') : 'sales';
        $from = isset($validated['from']) ? date('Y-m-d', strtotime($validated['from'])) : now()->startOfMonth()->toDateString();
        $to = isset($validated['to']) ? date('Y-m-d', strtotime($validated['to'])) : now()->toDateString();
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
            $items = StockMovement::with(['stockable', 'source'])->whereBetween('movement_date', [$from.' 00:00:00', $to.' 23:59:59'])->latest('movement_date')->paginate(25)->withQueryString();
            $summary['count'] = $items->total();
            $summary['in'] = StockMovement::whereBetween('movement_date', [$from.' 00:00:00', $to.' 23:59:59'])->whereIn('movement_type', ['purchase_in', 'production_in', 'adjustment_in'])->count();
            $summary['out'] = StockMovement::whereBetween('movement_date', [$from.' 00:00:00', $to.' 23:59:59'])->whereIn('movement_type', ['production_out', 'sale_out', 'adjustment_out'])->count();
        }

        return view('admin.reports.index', compact('tab', 'from', 'to', 'items', 'summary'));
    }
}
