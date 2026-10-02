@extends('layouts.admin')
@section('title', 'Kategori')

@section('content')
<div class="admin-page-head">
    <h1>Kategori Produk</h1>
    <button class="btn btn-primary" data-modal-open="category-create-modal">Tambah Kategori</button>
</div>

<div class="card">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Nama</th><th>Slug</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse($categories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->slug }}</td>
                    <td><span class="badge {{ $category->is_active ? 'badge-new' : 'badge-best' }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td>
                        <div class="admin-actions">
                            <button class="btn btn-secondary" data-modal-open="edit-category-{{ $category->id }}">Edit</button>
                            <button class="btn btn-outline" data-modal-open="delete-category-{{ $category->id }}">Hapus</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">Belum ada kategori.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:1rem;">{{ $categories->links() }}</div>
</div>

<div class="modal" id="category-create-modal" data-modal>
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-small">
        <div class="modal-head"><h3>Tambah Kategori</h3><button data-modal-close class="btn btn-outline">Tutup</button></div>
        <form method="POST" action="{{ route('admin.categories.store') }}" class="admin-form-grid">
            @csrf
            <div class="form-group form-col-2"><label>Nama</label><input name="name" required></div>
            <div class="form-group form-col-2"><label><input type="checkbox" name="is_active" value="1" checked> Aktif</label></div>
            <div class="form-actions"><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

@foreach($categories as $category)
<div class="modal" id="edit-category-{{ $category->id }}" data-modal>
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-small">
        <div class="modal-head"><h3>Edit Kategori</h3><button data-modal-close class="btn btn-outline">Tutup</button></div>
        <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="admin-form-grid">
            @csrf @method('PUT')
            <div class="form-group form-col-2"><label>Nama</label><input name="name" value="{{ $category->name }}" required></div>
            <p class="form-help form-col-2">Jika kategori dibuat nonaktif, semua produk di dalam kategori ini otomatis ikut nonaktif.</p>
            <div class="form-group form-col-2"><label><input type="checkbox" name="is_active" value="1" @checked($category->is_active)> Aktif</label></div>
            <div class="form-actions"><button class="btn btn-secondary">Update</button></div>
        </form>
    </div>
</div>

<div class="modal" id="delete-category-{{ $category->id }}" data-modal>
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-small">
        <h3>Hapus Kategori</h3>
        <p><strong>Peringatan:</strong> semua produk yang terhubung dengan kategori <strong>{{ $category->name }}</strong> juga akan ikut terhapus.</p>
        <p class="text-muted">Lanjutkan hanya jika data produk pada kategori ini memang sudah tidak dibutuhkan.</p>
        <label style="display:flex;align-items:center;gap:.5rem;margin:1rem 0;">
            <input type="checkbox" data-delete-check="delete-category-confirm-{{ $category->id }}">
            Saya paham semua produk terkait akan ikut dihapus
        </label>
        <div class="form-actions">
            <button data-modal-close class="btn btn-outline">Batal</button>
            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}">
                @csrf @method('DELETE')
                <button class="btn btn-primary" id="delete-category-confirm-{{ $category->id }}" disabled>Ya, Hapus</button>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
