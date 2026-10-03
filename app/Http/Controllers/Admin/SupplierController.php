<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $items = Supplier::query()->withCount('purchases')
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', '%'.$request->q.'%')->orWhere('code', 'like', '%'.$request->q.'%')))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->status === 'active'))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.master-data.index', [
            'title' => 'Supplier', 'description' => 'Kelola pemasok bahan baku dan informasi kontaknya.',
            'route' => 'admin.suppliers', 'detailRoute' => 'admin.suppliers.show', 'items' => $items,
            'columns' => ['code' => 'Kode', 'name' => 'Supplier', 'phone' => 'Telepon', 'email' => 'Email', 'purchases_count' => 'Pembelian', 'is_active' => 'Status'],
            'fields' => [
                ['name' => 'code', 'label' => 'Kode supplier', 'required' => true],
                ['name' => 'name', 'label' => 'Nama supplier', 'required' => true],
                ['name' => 'phone', 'label' => 'Nomor telepon', 'type' => 'tel'],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                ['name' => 'address', 'label' => 'Alamat', 'type' => 'textarea'],
                ['name' => 'notes', 'label' => 'Catatan', 'type' => 'textarea'],
                ['name' => 'is_active', 'label' => 'Supplier aktif', 'type' => 'checkbox'],
            ],
            'canDelete' => fn (Supplier $item): bool => $item->purchases_count === 0,
        ]);
    }

    public function show(Supplier $supplier)
    {
        $supplier->loadCount('purchases');
        $rows = $supplier->purchases()->latest('purchase_date')->limit(20)->get()
            ->map(fn ($purchase): array => [
                'reference' => $purchase->reference_number, 'date' => $purchase->purchase_date,
                'description' => ucfirst($purchase->status), 'quantity' => 'Rp'.number_format($purchase->total_amount, 0, ',', '.'),
                'url' => route('admin.purchases.show', $purchase),
            ]);

        return view('admin.master-data.show', [
            'title' => 'Supplier', 'item' => $supplier,
            'details' => ['Kode' => $supplier->code, 'Telepon' => $supplier->phone ?: '—', 'Email' => $supplier->email ?: '—',
                'Alamat' => $supplier->address ?: '—', 'Status' => $supplier->is_active ? 'Aktif' : 'Nonaktif', 'Catatan' => $supplier->notes ?: '—'],
            'summary' => [['label' => 'Jumlah pembelian', 'value' => $supplier->purchases_count]],
            'rows' => $rows, 'rowHeading' => 'Riwayat pembelian', 'emptyMessage' => 'Belum ada transaksi pembelian dengan supplier ini.',
            'backRoute' => 'admin.suppliers.index',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'unique:suppliers,code'], 'name' => ['required', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'], 'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string'], 'notes' => ['nullable', 'string'], 'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        Supplier::create($data);

        return back()->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('suppliers', 'code')->ignore($supplier->id)], 'name' => ['required', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'], 'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string'], 'notes' => ['nullable', 'string'], 'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $supplier->update($data);

        return back()->with('success', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->purchases()->exists()) {
            return back()->withErrors(['delete' => 'Supplier yang memiliki riwayat pembelian tidak dapat dihapus. Nonaktifkan supplier sebagai gantinya.']);
        }
        $supplier->delete();

        return back()->with('success', 'Supplier berhasil dihapus.');
    }
}
