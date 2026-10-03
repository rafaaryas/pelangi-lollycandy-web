@extends('layouts.admin')

@section('title', 'Stok | Pelangi Admin')

@section('content')
<header class="module-page-head"><div><div class="admin-breadcrumb"><a href="{{ route('admin.dashboard') }}">Dashboard</a><span>/</span><strong>Persediaan</strong></div><h1>Stok</h1><p>Pantau ketersediaan bahan baku dan produk jadi.</p></div></header>
<section class="stock-summary-row">
    <div><span>Total item tercatat</span><strong>{{ $totalTracked }}</strong></div>
    <div><span>Stok rendah</span><strong class="stock-low-number">{{ $lowProducts + $lowMaterials }}</strong></div>
    <div><span>Stok habis</span><strong class="stock-out-number">{{ $outProducts + $outMaterials }}</strong></div>
</section>
<nav class="stock-tabs" aria-label="Jenis stok">
    <a class="{{ $tab === 'materials' ? 'is-active' : '' }}" href="{{ route('admin.stock.index', ['tab' => 'materials']) }}"><x-icon name="boxes" />Bahan Baku <span>{{ $materialCount }}</span></a>
    <a class="{{ $tab === 'products' ? 'is-active' : '' }}" href="{{ route('admin.stock.index', ['tab' => 'products']) }}"><x-icon name="package" />Produk <span>{{ $productCount }}</span></a>
</nav>
<form method="GET" class="table-toolbar">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <label class="search-field"><x-icon name="search" /><input name="q" value="{{ request('q') }}" placeholder="Cari item stok" aria-label="Cari item stok"></label>
    <select name="status" aria-label="Filter status stok"><option value="">Semua status</option><option value="low" @selected(request('status') === 'low')>Stok rendah</option><option value="out" @selected(request('status') === 'out')>Habis</option></select>
    <button class="btn btn-secondary" type="submit"><x-icon name="sliders" />{{ request()->filled('q') || request()->filled('status') ? 'Filter aktif' : 'Filter' }}</button>
</form>
<div class="admin-table-wrap module-table-wrap">
    <table class="admin-table module-table"><thead><tr><th>Kode</th><th>Nama</th>@if($tab === 'products')<th>Kategori</th>@endif<th class="numeric-cell">Stok</th><th class="numeric-cell">Minimum</th><th>Satuan</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        @forelse($items as $item)
            @php($quantity = (float) ($tab === 'products' ? $item->stock_quantity : $item->current_stock))
            @php($minimum = (float) $item->minimum_stock)
            @php($status = $quantity <= 0 ? 'Habis' : ($minimum > 0 && $quantity <= $minimum ? 'Rendah' : 'Aman'))
            <tr>
                <td>{{ $tab === 'products' ? 'PR-'.$item->id : $item->code }}</td><td><strong>{{ $item->name }}</strong></td>
                @if($tab === 'products')<td>{{ $item->category->name }}</td>@endif
                <td class="numeric-cell">{{ number_format($quantity, 3, ',', '.') }}</td><td class="numeric-cell">{{ number_format($minimum, 3, ',', '.') }}</td><td>{{ $tab === 'products' ? 'pcs' : $item->unit }}</td>
                <td><span class="stock-state stock-{{ strtolower($status) }}">{{ $status }}</span></td>
                <td><div class="table-actions"><x-icon-link icon="eye" label="Lihat histori {{ $item->name }}" href="{{ route('admin.stock.history', [$tab, $item->id]) }}" /><button class="text-action" type="button" data-modal-open="adjust-stock-{{ $tab }}-{{ $item->id }}"><x-icon name="sliders" size="16" />Sesuaikan</button></div></td>
            </tr>
        @empty
            <tr><td colspan="8"><div class="table-empty"><strong>Belum ada item stok.</strong><span>Data bahan baku dan saldo awal produk akan muncul di sini.</span></div></td></tr>
        @endforelse
    </tbody></table>
</div>
@foreach($items as $item)
<div class="modal" id="adjust-stock-{{ $tab }}-{{ $item->id }}" data-modal><div class="modal-backdrop" data-modal-close></div><section class="modal-content modal-small">
    <div class="modal-head"><h2>Sesuaikan stok · {{ $item->name }}</h2><button type="button" class="modal-close" data-modal-close aria-label="Tutup"><x-icon name="close" /></button></div>
    <form method="POST" action="{{ route('admin.stock.adjust') }}" class="module-form-grid" data-loading-form>@csrf
        <input type="hidden" name="stock_type" value="{{ $tab === 'products' ? 'product' : 'material' }}"><input type="hidden" name="stock_id" value="{{ $item->id }}">
        <label class="module-field">Arah penyesuaian<select name="direction"><option value="in">Stok masuk</option><option value="out">Stok keluar</option></select></label>
        <label class="module-field">Jumlah ({{ $tab === 'products' ? 'pcs' : $item->unit }})<input type="number" name="quantity" min="0.001" step="0.001" required></label>
        <label class="module-field module-field-wide">Alasan<textarea name="notes" rows="2" placeholder="Contoh: hasil stok opname"></textarea></label>
        <div class="modal-form-actions"><button type="button" class="btn btn-secondary" data-modal-close>Batal</button><button type="submit" class="btn btn-primary">Simpan penyesuaian</button></div>
    </form>
</section></div>
@endforeach
<div class="module-pagination">{{ $items->links() }}</div>
@if($tab === 'products' && $untrackedProducts > 0)<p class="inline-empty">Stok {{ $untrackedProducts }} produk belum dicatat. Produk dengan stok belum diketahui disembunyikan sampai saldo awal dimasukkan.</p>@endif
@endsection
