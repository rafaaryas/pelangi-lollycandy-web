<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), ['products', 'materials'], true) ? $request->query('tab') : 'materials';
        $isProducts = $tab === 'products';
        $query = $isProducts ? Product::query()->with('category') : RawMaterial::query();
        $items = $query->when($request->filled('q'), fn ($query) => $query->where(fn ($q) => $q
            ->where('name', 'like', '%'.$request->q.'%')->orWhere('id', $request->q)))
            ->when($request->filled('status'), function ($query) use ($request, $isProducts): void {
                if ($request->status === 'out') {
                    $query->where($isProducts ? 'stock_quantity' : 'current_stock', '<=', 0);
                } elseif ($request->status === 'low') {
                    $stock = $isProducts ? 'stock_quantity' : 'current_stock';
                    $query->where($stock, '>', 0)->where('minimum_stock', '>', 0)->whereColumn($stock, '<=', 'minimum_stock');
                }
            })
            ->when($isProducts, fn ($query) => $query->whereNotNull('stock_quantity'))
            ->orderBy($isProducts ? 'name' : 'name')->paginate(15)->withQueryString();

        $productQuery = Product::where('is_active', true)->whereNotNull('stock_quantity');
        $materialQuery = RawMaterial::where('is_active', true);
        $lowProducts = (clone $productQuery)->where('stock_quantity', '>', 0)->where('minimum_stock', '>', 0)->whereColumn('stock_quantity', '<=', 'minimum_stock')->count();
        $outProducts = (clone $productQuery)->where('stock_quantity', '<=', 0)->count();
        $lowMaterials = (clone $materialQuery)->where('current_stock', '>', 0)->where('minimum_stock', '>', 0)->whereColumn('current_stock', '<=', 'minimum_stock')->count();
        $outMaterials = (clone $materialQuery)->where('current_stock', '<=', 0)->count();
        $totalTracked = Product::where('is_active', true)->whereNotNull('stock_quantity')->count()
            + RawMaterial::where('is_active', true)->count();
        $untrackedProducts = Product::where('is_active', true)->whereNull('stock_quantity')->count();
        $materialCount = RawMaterial::where('is_active', true)->count();
        $productCount = Product::where('is_active', true)->whereNotNull('stock_quantity')->count();

        $materialOptions = RawMaterial::where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit']);

        return view('admin.stock.index', compact('items', 'tab', 'lowProducts', 'outProducts', 'lowMaterials', 'outMaterials', 'totalTracked', 'untrackedProducts', 'materialCount', 'productCount', 'materialOptions'));
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'stock_type' => ['required', 'in:product,material'], 'stock_id' => ['required', 'integer'],
            'direction' => ['required', 'in:in,out'], 'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($data): void {
            $modelClass = $data['stock_type'] === 'product' ? Product::class : RawMaterial::class;
            $stock = $modelClass::query()->lockForUpdate()->findOrFail($data['stock_id']);
            $column = $data['stock_type'] === 'product' ? 'stock_quantity' : 'current_stock';
            $current = $stock->{$column};
            if ($current === null && $data['direction'] === 'out') {
                throw ValidationException::withMessages(['quantity' => 'Stok produk belum dicatat. Masukkan saldo awal terlebih dahulu.']);
            }
            if ($data['direction'] === 'out' && (float) $current < (float) $data['quantity']) {
                throw ValidationException::withMessages(['quantity' => 'Jumlah keluar melebihi stok yang tersedia.']);
            }
            if ($current === null) {
                $stock->setAttribute($column, 0);
                $stock->save();
            }
            $data['direction'] === 'in'
                ? $stock->increment($column, $data['quantity'])
                : $stock->decrement($column, $data['quantity']);
            $stock->stockMovements()->create([
                'movement_type' => $data['direction'] === 'in' ? 'adjustment_in' : 'adjustment_out',
                'quantity' => $data['quantity'],
                'unit' => $data['stock_type'] === 'material' ? $stock->unit : 'pcs',
                'movement_date' => now(),
                'notes' => $data['notes'] ?: 'Penyesuaian stok manual',
            ]);
        });

        return back()->with('success', 'Penyesuaian stok dan histori berhasil disimpan.');
    }

    public function addMaterial(Request $request)
    {
        $data = $request->validate([
            'raw_material_id' => ['required', 'exists:raw_materials,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'movement_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($data): void {
            $material = RawMaterial::query()->lockForUpdate()->findOrFail($data['raw_material_id']);
            $material->increment('current_stock', $data['quantity']);
            $material->stockMovements()->create([
                'movement_type' => 'adjustment_in',
                'quantity' => $data['quantity'],
                'unit' => $material->unit,
                'movement_date' => $data['movement_date'].' 12:00:00',
                'notes' => $data['notes'] ?: 'Penambahan stok bahan baku',
            ]);
        });

        return redirect()->route('admin.stock.index', ['tab' => 'materials'])->with('success', 'Stok bahan baku ditambahkan dan dicatat di laporan.');
    }

    public function history(string $type, int $id)
    {
        abort_unless(in_array($type, ['products', 'materials'], true), 404);
        $modelClass = $type === 'products' ? Product::class : RawMaterial::class;
        $item = $modelClass::findOrFail($id);
        $movements = $item->stockMovements()->with('source')->orderBy('movement_date')->orderBy('id')->get();
        $balance = 0;
        $history = $movements->map(function (StockMovement $movement) use (&$balance): array {
            $incoming = in_array($movement->movement_type, ['purchase_in', 'production_in', 'adjustment_in'], true) ? (float) $movement->quantity : 0;
            $outgoing = in_array($movement->movement_type, ['production_out', 'sale_out', 'adjustment_out'], true) ? (float) $movement->quantity : 0;
            $balance += $incoming - $outgoing;

            return ['movement' => $movement, 'incoming' => $incoming, 'outgoing' => $outgoing, 'balance' => $balance];
        })->reverse()->values();

        return view('admin.stock.history', [
            'item' => $item, 'type' => $type, 'history' => $history,
            'currentStock' => $type === 'products' ? $item->stock_quantity : $item->current_stock,
            'minimumStock' => $item->minimum_stock,
            'unit' => $type === 'products' ? 'pcs' : $item->unit,
        ]);
    }
}
