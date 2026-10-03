<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RawMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RawMaterialController extends Controller
{
    public function index(Request $request)
    {
        $items = RawMaterial::query()
            ->withCount(['purchaseDetails', 'productionMaterials', 'stockMovements'])
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', '%'.$request->q.'%')->orWhere('code', 'like', '%'.$request->q.'%')))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->status === 'active'))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.master-data.index', [
            'title' => 'Bahan Baku',
            'description' => 'Kelola bahan yang digunakan dalam proses produksi.',
            'route' => 'admin.raw-materials',
            'detailRoute' => 'admin.raw-materials.show',
            'items' => $items,
            'columns' => ['code' => 'Kode', 'name' => 'Bahan Baku', 'current_stock' => 'Stok', 'minimum_stock' => 'Minimum', 'unit' => 'Satuan', 'is_active' => 'Status'],
            'fields' => [
                ['name' => 'code', 'label' => 'Kode bahan', 'required' => true],
                ['name' => 'name', 'label' => 'Nama bahan baku', 'required' => true],
                ['name' => 'unit', 'label' => 'Satuan', 'required' => true, 'placeholder' => 'kg, liter, pcs'],
                ['name' => 'minimum_stock', 'label' => 'Batas minimum', 'type' => 'number', 'step' => '0.001', 'required' => true],
                ['name' => 'description', 'label' => 'Catatan', 'type' => 'textarea'],
                ['name' => 'is_active', 'label' => 'Bahan aktif', 'type' => 'checkbox'],
            ],
            'canDelete' => fn (RawMaterial $item): bool => $item->purchase_details_count === 0 && $item->production_materials_count === 0 && $item->stock_movements_count === 0,
        ]);
    }

    public function show(RawMaterial $rawMaterial)
    {
        $rawMaterial->loadCount(['purchaseDetails', 'productionMaterials']);
        $rows = $rawMaterial->stockMovements()->with('source')->latest('movement_date')->limit(20)->get()
            ->map(fn ($movement): array => [
                'reference' => $movement->source?->reference_number ?? $movement->source?->production_number ?? $movement->source?->invoice_number ?? 'Penyesuaian stok',
                'date' => $movement->movement_date,
                'description' => str_replace('_', ' ', ucfirst($movement->movement_type)),
                'quantity' => number_format((float) $movement->quantity, 3, ',', '.').' '.($movement->unit ?: $rawMaterial->unit),
                'url' => route('admin.stock.history', ['materials', $rawMaterial->id]),
            ]);

        return view('admin.master-data.show', [
            'title' => 'Bahan Baku', 'item' => $rawMaterial,
            'details' => ['Kode' => $rawMaterial->code, 'Satuan' => $rawMaterial->unit,
                'Stok saat ini' => number_format((float) $rawMaterial->current_stock, 3, ',', '.').' '.$rawMaterial->unit,
                'Batas minimum' => number_format((float) $rawMaterial->minimum_stock, 3, ',', '.').' '.$rawMaterial->unit,
                'Status' => $rawMaterial->is_active ? 'Aktif' : 'Nonaktif', 'Catatan' => $rawMaterial->description ?: '—'],
            'summary' => [['label' => 'Rincian pembelian', 'value' => $rawMaterial->purchase_details_count], ['label' => 'Pemakaian produksi', 'value' => $rawMaterial->production_materials_count]],
            'rows' => $rows, 'rowHeading' => 'Histori stok', 'emptyMessage' => 'Belum ada pergerakan stok untuk bahan ini.',
            'backRoute' => 'admin.raw-materials.index',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'unique:raw_materials,code'],
            'name' => ['required', 'string', 'max:190'],
            'unit' => ['required', 'string', 'max:40'],
            'minimum_stock' => ['required', 'numeric', 'min:0'],
            'opening_stock' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            $material = RawMaterial::create($data + ['current_stock' => 0, 'is_active' => $request->boolean('is_active')]);
            $opening = (float) ($data['opening_stock'] ?? 0);
            if ($opening > 0) {
                $material->update(['current_stock' => $opening]);
                $material->stockMovements()->create([
                    'movement_type' => 'adjustment_in',
                    'quantity' => $opening,
                    'unit' => $material->unit,
                    'movement_date' => now(),
                    'notes' => 'Saldo awal bahan baku',
                ]);
            }
        });

        return back()->with('success', 'Bahan baku berhasil ditambahkan.');
    }

    public function update(Request $request, RawMaterial $rawMaterial)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('raw_materials', 'code')->ignore($rawMaterial->id)],
            'name' => ['required', 'string', 'max:190'],
            'unit' => ['required', 'string', 'max:40'],
            'minimum_stock' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        if ($data['unit'] !== $rawMaterial->unit && $rawMaterial->stockMovements()->exists()) {
            return back()->withErrors(['unit' => 'Satuan tidak dapat diubah setelah bahan memiliki histori stok. Buat bahan baru jika satuannya berbeda.']);
        }
        $data['is_active'] = $request->boolean('is_active');
        $rawMaterial->update($data);

        return back()->with('success', 'Bahan baku berhasil diperbarui.');
    }

    public function destroy(RawMaterial $rawMaterial)
    {
        if ($rawMaterial->purchaseDetails()->exists() || $rawMaterial->productionMaterials()->exists() || $rawMaterial->stockMovements()->exists()) {
            return back()->withErrors(['delete' => 'Bahan baku yang memiliki riwayat transaksi tidak dapat dihapus. Nonaktifkan bahan ini sebagai gantinya.']);
        }
        $rawMaterial->delete();

        return back()->with('success', 'Bahan baku berhasil dihapus.');
    }
}
