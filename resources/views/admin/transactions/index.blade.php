@extends('layouts.admin')

@section('title', $title.' | Pelangi Admin')

@section('content')
<header class="module-page-head">
    <div><div class="admin-breadcrumb"><a href="{{ route('admin.dashboard') }}">Dashboard</a><span>/</span><strong>Transaksi</strong></div><h1>{{ $title }}</h1><p>{{ $description }}</p></div>
    <a class="btn btn-primary" href="{{ route('admin.'.$kind.'.create') }}"><x-icon name="plus" />Buat {{ $title }}</a>
</header>
@php($filtersActive = request()->filled('q') || request()->filled('from') || request()->filled('to') || request()->filled('status') || request()->filled('supplier_id') || request()->filled('customer_id'))
<form method="GET" class="table-toolbar transaction-toolbar">
    <label class="search-field"><x-icon name="search" /><input name="q" value="{{ request('q') }}" placeholder="Cari nomor {{ strtolower($title) }}" aria-label="Cari nomor transaksi"></label>
    <input type="date" name="from" value="{{ request('from') }}" aria-label="Tanggal mulai">
    <input type="date" name="to" value="{{ request('to') }}" aria-label="Tanggal akhir">
    <select name="status" aria-label="Status"><option value="">Semua status</option>@foreach(['draft' => 'Draft', 'confirmed' => 'Terkonfirmasi', 'cancelled' => 'Dibatalkan'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select>
    @if(isset($suppliers))<select name="supplier_id" aria-label="Supplier"><option value="">Semua supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string) request('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>@endforeach</select>@endif
    @if(isset($customers))<select name="customer_id" aria-label="Pelanggan"><option value="">Semua pelanggan</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected((string) request('customer_id') === (string) $customer->id)>{{ $customer->name }}</option>@endforeach</select>@endif
    <button class="btn btn-secondary" type="submit"><x-icon name="sliders" />{{ $filtersActive ? 'Filter aktif' : 'Filter' }}</button>
</form>
<div class="table-meta"><span>{{ $items->total() }} transaksi</span><span>Status confirmed memengaruhi saldo stok.</span></div>
<div class="admin-table-wrap module-table-wrap">
    <table class="admin-table module-table">
        <thead><tr>
            @if($kind === 'purchases')<th>No. Pembelian</th><th>Tanggal</th><th>Supplier</th><th>Item</th><th class="numeric-cell">Total</th>
            @elseif($kind === 'productions')<th>No. Produksi</th><th>Tanggal</th><th>Bahan Digunakan</th><th>Hasil Produksi</th><th>Status</th>
            @else<th>No. Invoice</th><th>Tanggal</th><th>Pelanggan</th><th>Item</th><th class="numeric-cell">Total</th>@endif
            @if($kind !== 'productions')<th>Status</th>@endif<th>Aksi</th>
        </tr></thead>
        <tbody>
        @forelse($items as $item)
            @php($rowReference = $kind === 'purchases' ? $item->reference_number : ($kind === 'productions' ? $item->production_number : $item->invoice_number))
            <tr>
                @if($kind === 'purchases')
                    <td><a class="table-primary-link" href="{{ route('admin.purchases.show', $item) }}">{{ $item->reference_number }}</a></td><td>{{ $item->purchase_date->format('d M Y') }}</td><td>{{ $item->supplier->name }}</td><td>{{ $item->details_count }} bahan</td><td class="numeric-cell">Rp{{ number_format($item->total_amount, 0, ',', '.') }}</td>
                @elseif($kind === 'productions')
                    <td><a class="table-primary-link" href="{{ route('admin.productions.show', $item) }}">{{ $item->production_number }}</a></td><td>{{ $item->production_date->format('d M Y') }}</td><td>{{ $item->materials->count() }} bahan</td><td>{{ number_format((float) $item->results->sum('quantity_produced'), 3, ',', '.') }} pcs</td>
                @else
                    <td><a class="table-primary-link" href="{{ route('admin.sales.show', $item) }}">{{ $item->invoice_number }}</a></td><td>{{ $item->sale_date->format('d M Y') }}</td><td>{{ $item->customer?->name ?? 'Pelanggan umum' }}</td><td>{{ $item->details_count }} produk</td><td class="numeric-cell">Rp{{ number_format($item->total_amount, 0, ',', '.') }}</td>
                @endif
                @if($kind === 'productions')<td>@endif
                @if($kind !== 'productions')<td>@endif
                    <span class="transaction-status status-{{ $item->status }}">{{ match($item->status) { 'confirmed' => 'Terkonfirmasi', 'cancelled' => 'Dibatalkan', default => 'Draft' } }}</span>
                </td>
                <td><div class="table-actions"><x-icon-link icon="eye" label="Lihat detail {{ $rowReference }}" href="{{ route('admin.'.$kind.'.show', $item) }}" />
                    @if($item->status === 'draft')<x-icon-link icon="pencil" label="Edit draft" href="{{ route('admin.'.$kind.'.edit', $item) }}" />
                    <form method="POST" action="{{ route('admin.'.$kind.'.confirm', $item) }}" data-loading-form>@csrf<button class="text-action text-confirm" type="submit"><x-icon name="check" size="16" />Konfirmasi</button></form>@endif
                    @if($item->status !== 'cancelled')<form method="POST" action="{{ route('admin.'.$kind.'.cancel', $item) }}" data-confirm-title="Batalkan transaksi?" data-confirm-message="Transaksi {{ $kind === 'purchases' ? $item->reference_number : ($kind === 'productions' ? $item->production_number : $item->invoice_number) }} akan dibatalkan. Stok akan disesuaikan jika transaksi ini sudah dikonfirmasi." data-confirm-label="Batalkan" data-loading-form>@csrf<x-icon-button icon="close" label="Batalkan transaksi" variant="danger" type="submit" /></form>@endif
                </div></td>
            </tr>
        @empty
            <tr><td colspan="7"><div class="table-empty"><strong>Belum ada {{ strtolower($title) }}.</strong><span>Buat transaksi pertama untuk mulai mencatatnya.</span></div></td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="module-pagination">{{ $items->links() }}</div>
@endsection
