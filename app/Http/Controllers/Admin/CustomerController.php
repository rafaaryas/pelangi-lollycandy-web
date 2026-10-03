<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $items = Customer::query()->withCount('sales')
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->q.'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->status === 'active'))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.master-data.index', [
            'title' => 'Pelanggan', 'description' => 'Simpan kontak pelanggan dan lihat jumlah transaksi mereka.',
            'route' => 'admin.customers', 'detailRoute' => 'admin.customers.show', 'items' => $items,
            'columns' => ['name' => 'Nama', 'phone' => 'Telepon', 'email' => 'Email', 'sales_count' => 'Transaksi', 'is_active' => 'Status'],
            'fields' => [
                ['name' => 'name', 'label' => 'Nama pelanggan', 'required' => true],
                ['name' => 'phone', 'label' => 'Nomor telepon', 'type' => 'tel'],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                ['name' => 'address', 'label' => 'Alamat', 'type' => 'textarea'],
                ['name' => 'notes', 'label' => 'Catatan', 'type' => 'textarea'],
                ['name' => 'is_active', 'label' => 'Pelanggan aktif', 'type' => 'checkbox'],
            ],
            'canDelete' => fn (Customer $item): bool => $item->sales_count === 0,
        ]);
    }

    public function show(Customer $customer)
    {
        $customer->loadCount('sales');
        $rows = $customer->sales()->latest('sale_date')->limit(20)->get()
            ->map(fn ($sale): array => [
                'reference' => $sale->invoice_number, 'date' => $sale->sale_date,
                'description' => ucfirst($sale->status), 'quantity' => 'Rp'.number_format($sale->total_amount, 0, ',', '.'),
                'url' => route('admin.sales.show', $sale),
            ]);

        return view('admin.master-data.show', [
            'title' => 'Pelanggan', 'item' => $customer,
            'details' => ['Telepon' => $customer->phone ?: '—', 'Email' => $customer->email ?: '—',
                'Alamat' => $customer->address ?: '—', 'Status' => $customer->is_active ? 'Aktif' : 'Nonaktif', 'Catatan' => $customer->notes ?: '—'],
            'summary' => [['label' => 'Jumlah transaksi', 'value' => $customer->sales_count]],
            'rows' => $rows, 'rowHeading' => 'Riwayat penjualan', 'emptyMessage' => 'Belum ada transaksi untuk pelanggan ini.',
            'backRoute' => 'admin.customers.index',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'], 'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:190'], 'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'], 'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        Customer::create($data);

        return back()->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'], 'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:190'], 'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'], 'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $customer->update($data);

        return back()->with('success', 'Pelanggan berhasil diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->sales()->exists()) {
            return back()->withErrors(['delete' => 'Pelanggan dengan riwayat penjualan tidak dapat dihapus. Nonaktifkan pelanggan sebagai gantinya.']);
        }
        $customer->delete();

        return back()->with('success', 'Pelanggan berhasil dihapus.');
    }
}
