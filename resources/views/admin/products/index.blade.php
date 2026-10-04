@extends('layouts.admin')
@section('title', 'Manajemen Produk')

@section('content')
<div class="module-page-head">
    <div><div class="admin-breadcrumb"><a href="{{ route('admin.dashboard') }}">Dashboard</a><span>/</span><strong>Master Data</strong></div><h1>Produk</h1><p>Kelola katalog, harga, gambar, dan ketersediaan produk.</p></div>
    <button class="btn btn-primary" type="button" data-modal-open="product-create-modal"><x-icon name="plus" />Tambah Produk</button>
</div>

@php($filtersActive = request()->filled('q') || request()->filled('category_id') || request()->filled('status'))
<form method="GET" class="table-toolbar" role="search"><label class="search-field"><x-icon name="search" /><input name="q" value="{{ request('q') }}" placeholder="Cari produk" aria-label="Cari produk"></label><select name="category_id" aria-label="Kategori"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select><select name="status" aria-label="Status"><option value="">Semua status</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option></select><button class="btn btn-secondary" type="submit"><x-icon name="sliders" />{{ $filtersActive ? 'Filter aktif' : 'Filter' }}</button></form>

<div class="admin-table-wrap module-table-wrap">
        <table class="admin-table module-table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Status</th>
                    <th>Diperbarui</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody data-admin-list>
                @forelse($products as $product)
                    @php($productImagePath = $product->images->first()?->storagePath())
                    <tr>
                        <td>
                            <div class="product-row">
                                <img src="{{ $productImagePath ? asset('storage/'.$productImagePath) : asset(\App\Models\ProductImage::PLACEHOLDER) }}" alt="{{ $product->name }}">
                                <div>
                                    <strong>{{ $product->name }}</strong>
                                    <small>{{ $product->category->name ?? '-' }}</small>
                                </div>
                            </div>
                        </td>
                        <td>{{ $product->category->name ?? '-' }}</td>
                        <td>Rp {{ number_format($product->price_from, 0, ',', '.') }}</td>
                        <td>
                            <span class="status-pill {{ $product->is_active ? 'status-confirmed' : 'status-cancelled' }}">
                                {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>{{ $product->updated_at->format('d M Y') }}</td>
                        <td>
                            <div class="admin-actions">
                                <x-icon-link icon="eye" label="Lihat detail {{ $product->name }}" href="{{ route('admin.products.show', $product) }}" />
                                <x-icon-button icon="pencil" label="Edit {{ $product->name }}" data-modal-open="edit-product-{{ $product->id }}" />
                                @if($product->sale_details_count === 0 && $product->production_results_count === 0 && $product->stock_movements_count === 0)
                                    <x-icon-button icon="trash" label="Hapus {{ $product->name }}" variant="danger" data-modal-open="delete-product-{{ $product->id }}" />
                                @else
                                    <span class="text-action-muted" title="Produk dengan riwayat transaksi atau stok tidak dapat dihapus">Ada riwayat</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">Belum ada produk.</td></tr>
                @endforelse
            </tbody>
        </table>
</div>
<div class="module-pagination" data-admin-pagination>{{ $products->links() }}</div>

<div data-admin-modals>
<div class="modal" id="product-create-modal" data-modal data-open-on-error="{{ $errors->any() && old('_form') === 'create' ? 'true' : 'false' }}">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content product-modal-content">
        <div class="modal-head">
            <div><h3>Tambah Produk</h3><p class="modal-subtitle">Tambahkan produk baru ke katalog Pelangi Lollycandy.</p></div>
            <button type="button" data-modal-close class="modal-close" aria-label="Tutup dialog"><x-icon name="close" /></button>
        </div>
        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="admin-form-grid product-form-grid" data-admin-ajax>
            @csrf
            <input type="hidden" name="_form" value="create">
            @include('admin.products.partials.form-fields', ['product' => null, 'categories' => $categories])
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Produk</button>
            </div>
        </form>
    </div>
</div>

@foreach($products as $product)
    <div class="modal" id="edit-product-{{ $product->id }}" data-modal data-open-on-error="{{ $errors->any() && old('_form') === 'edit-'.$product->id ? 'true' : 'false' }}">
        <div class="modal-backdrop" data-modal-close></div>
        <div class="modal-content product-modal-content">
            <div class="modal-head">
                <h3>Edit {{ $product->name }}</h3>
                <button type="button" data-modal-close class="modal-close" aria-label="Tutup dialog"><x-icon name="close" /></button>
            </div>
            <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data" class="admin-form-grid product-form-grid" data-admin-ajax>
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit-{{ $product->id }}">
                @include('admin.products.partials.form-fields', ['product' => $product, 'categories' => $categories])
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" data-modal-close>Batal</button>
                    <button type="submit" class="btn btn-primary">Update Produk</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="delete-product-{{ $product->id }}" data-modal>
        <div class="modal-backdrop" data-modal-close></div>
        <div class="modal-content modal-small">
            <div class="modal-head"><h2>Hapus produk?</h2><button type="button" class="modal-close" data-modal-close aria-label="Tutup dialog"><x-icon name="close" /></button></div>
            <p>Produk <strong>{{ $product->name }}</strong> akan dihapus beserta gambar produknya.</p>
            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="modal-form-actions" data-admin-ajax>
                @csrf @method('DELETE')
                <button type="button" data-modal-close class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-danger"><x-icon name="trash" />Hapus produk</button>
            </form>
        </div>
    </div>
@endforeach
</div>
@endsection
