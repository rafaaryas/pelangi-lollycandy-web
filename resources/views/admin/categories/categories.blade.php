@extends('layouts.admin')
@section('title', 'Kategori')

@section('content')
<div class="module-page-head">
    <div><div class="admin-breadcrumb"><a href="{{ route('admin.dashboard') }}">Dashboard</a><span>/</span><strong>Master Data</strong></div><h1>Kategori Produk</h1><p>Atur pengelompokan produk pada katalog Pelangi.</p></div>
    <button class="btn btn-primary" type="button" data-modal-open="category-create-modal"><x-icon name="plus" />Tambah Kategori</button>
</div>

@php($filtersActive = request()->filled('q') || request()->filled('status'))
<form method="GET" class="table-toolbar" role="search"><label class="search-field"><x-icon name="search" /><input name="q" value="{{ request('q') }}" placeholder="Cari kategori" aria-label="Cari kategori"></label><select name="status" aria-label="Status"><option value="">Semua status</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option></select><button class="btn btn-secondary" type="submit"><x-icon name="sliders" />{{ $filtersActive ? 'Filter aktif' : 'Filter' }}</button></form>

<div class="card">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Nama</th><th>Slug</th><th>Jumlah Produk</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse($categories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->slug }}</td>
                    <td>{{ number_format($category->products_count, 0, ',', '.') }} produk</td>
                    <td><span class="state-text {{ $category->is_active ? 'state-active' : 'state-inactive' }}"><i></i>{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td>
                        <div class="admin-actions">
                            <x-icon-button icon="pencil" label="Edit kategori {{ $category->name }}" data-modal-open="edit-category-{{ $category->id }}" />
                            @if($category->products_count === 0)<x-icon-button icon="trash" label="Hapus kategori {{ $category->name }}" variant="danger" data-modal-open="delete-category-{{ $category->id }}" />@else<span class="text-action-muted" title="Kategori dengan produk tidak dapat dihapus">{{ $category->products_count }} produk terhubung</span>@endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">Belum ada kategori.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:1rem;">{{ $categories->links() }}</div>
</div>

<div class="modal" id="category-create-modal" data-modal data-open-on-error="{{ $errors->any() && old('_form') === 'create' ? 'true' : 'false' }}">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-small">
        <div class="modal-head"><h3>Tambah Kategori</h3><button data-modal-close class="btn btn-outline">Tutup</button></div>
        <form method="POST" action="{{ route('admin.categories.store') }}" class="admin-form-grid" data-loading-form>
            @csrf
            <input type="hidden" name="_form" value="create">
            <div class="form-group form-col-2"><label for="category-create-name">Nama kategori <span aria-hidden="true">*</span></label><input id="category-create-name" name="name" value="{{ old('name') }}" required autocomplete="off" @if($errors->has('name')) aria-invalid="true" aria-describedby="error-name" @endif><x-field-error name="name" /></div>
            <div class="form-group form-col-2"><label><input type="checkbox" name="is_active" value="1" checked> Aktif</label></div>
            <div class="form-actions"><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

@foreach($categories as $category)
<div class="modal" id="edit-category-{{ $category->id }}" data-modal data-open-on-error="{{ $errors->any() && old('_form') === 'edit-'.$category->id ? 'true' : 'false' }}">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-small">
        <div class="modal-head"><h3>Edit Kategori</h3><button data-modal-close class="btn btn-outline">Tutup</button></div>
        <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="admin-form-grid" data-loading-form>
            @csrf @method('PUT')
            <input type="hidden" name="_form" value="edit-{{ $category->id }}">
            <div class="form-group form-col-2"><label for="category-edit-name-{{ $category->id }}">Nama kategori <span aria-hidden="true">*</span></label><input id="category-edit-name-{{ $category->id }}" name="name" value="{{ old('name', $category->name) }}" required @if($errors->has('name')) aria-invalid="true" aria-describedby="error-name" @endif><x-field-error name="name" /></div>
            <p class="form-help form-col-2">Jika kategori dibuat nonaktif, semua produk di dalam kategori ini otomatis ikut nonaktif.</p>
            <div class="form-group form-col-2"><label><input type="checkbox" name="is_active" value="1" @checked($category->is_active)> Aktif</label></div>
            <div class="form-actions"><button class="btn btn-secondary">Update</button></div>
        </form>
    </div>
</div>

@if($category->products_count === 0)
<div class="modal" id="delete-category-{{ $category->id }}" data-modal>
        <div class="modal-backdrop" data-modal-close></div>
        <section class="modal-content modal-small confirm-dialog-content">
            <div class="modal-head"><h2>Hapus kategori?</h2><button type="button" class="modal-close" data-modal-close aria-label="Tutup dialog"><x-icon name="close" /></button></div>
            <p>Kategori <strong>{{ $category->name }}</strong> akan dihapus. Kategori ini belum memiliki produk.</p>
            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" data-loading-form class="modal-form-actions">
                @csrf @method('DELETE')
                <button type="button" data-modal-close class="btn btn-secondary">Batal</button>
                <button class="btn btn-danger" type="submit"><x-icon name="trash" />Hapus kategori</button>
            </form>
        </section>
</div>
@endif
@endforeach
@endsection
