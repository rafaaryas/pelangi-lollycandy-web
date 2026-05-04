@extends('layouts.admin')
@section('title', 'Manajemen Produk')

@section('content')
<div class="admin-page-head">
    <h1>Manajemen Produk</h1>
    <button class="btn btn-primary" data-modal-open="product-create-modal">Tambah Produk</button>
</div>

<div class="card">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Varian</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>
                            <div class="product-row">
                                <img src="{{ $product->images->first() ? asset('storage/'.$product->images->first()->image_path) : 'https://placehold.co/80x80?text=IMG' }}" alt="{{ $product->name }}">
                                <div>
                                    <strong>{{ $product->name }}</strong>
                                    <small>{{ $product->sku }}</small>
                                </div>
                            </div>
                        </td>
                        <td>{{ $product->category->name ?? '-' }}</td>
                        <td>Rp {{ number_format($product->price_from, 0, ',', '.') }}</td>
                        <td>{{ $product->variants->count() }}</td>
                        <td>
                            <span class="badge {{ $product->is_active ? 'badge-new' : 'badge-best' }}">
                                {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>
                            <div class="admin-actions">
                                <button class="btn btn-secondary" data-modal-open="edit-product-{{ $product->id }}">Edit</button>
                                <button class="btn btn-outline" data-modal-open="delete-product-{{ $product->id }}">Delete</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">Belum ada produk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:1rem;">{{ $products->links() }}</div>
</div>

<div class="modal" id="product-create-modal" data-modal>
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content">
        <div class="modal-head">
            <h3>Tambah Produk</h3>
            <button data-modal-close class="btn btn-outline">Tutup</button>
        </div>
        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="admin-form-grid">
            @csrf
            @include('admin.products.partials.form-fields', ['product' => null, 'categories' => $categories])
            <div class="form-actions">
                <button class="btn btn-primary">Simpan Produk</button>
            </div>
        </form>
    </div>
</div>

@foreach($products as $product)
    <div class="modal" id="edit-product-{{ $product->id }}" data-modal>
        <div class="modal-backdrop" data-modal-close></div>
        <div class="modal-content">
            <div class="modal-head">
                <h3>Edit {{ $product->name }}</h3>
                <button data-modal-close class="btn btn-outline">Tutup</button>
            </div>
            <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data" class="admin-form-grid">
                @csrf
                @method('PUT')
                @include('admin.products.partials.form-fields', ['product' => $product, 'categories' => $categories])
                <div class="form-actions">
                    <button class="btn btn-secondary">Update Produk</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="delete-product-{{ $product->id }}" data-modal>
        <div class="modal-backdrop" data-modal-close></div>
        <div class="modal-content modal-small">
            <h3>Hapus Produk</h3>
            <p>Apakah kamu yakin ingin menghapus <strong>{{ $product->name }}</strong>?</p>
            <p class="text-muted">Tindakan ini akan menghapus varian dan gambar terkait.</p>
            <label style="display:flex;align-items:center;gap:.5rem;margin:1rem 0;">
                <input type="checkbox" data-delete-check="delete-confirm-{{ $product->id }}">
                Saya yakin ingin menghapus produk ini
            </label>
            <div class="form-actions">
                <button data-modal-close class="btn btn-outline">Batal</button>
                <form method="POST" action="{{ route('admin.products.destroy', $product) }}">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-primary" id="delete-confirm-{{ $product->id }}" disabled>Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection
