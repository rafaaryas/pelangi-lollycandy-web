@extends('layouts.admin')
@section('title', 'Marketplace Links')

@section('content')
<div class="admin-page-head">
    <h1>Marketplace Links</h1>
    <button class="btn btn-primary" data-modal-open="marketplace-create-modal">Tambah Link</button>
</div>

<div class="card">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Platform</th><th>URL</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse($links as $link)
                <tr>
                    <td>
                        <span class="admin-marketplace-name">
                            <img src="{{ asset($link->iconPath()) }}" alt="" aria-hidden="true">
                            <span>{{ $link->platform }}</span>
                        </span>
                    </td>
                    <td><a href="{{ $link->url }}" target="_blank" rel="noopener">{{ $link->url }}</a></td>
                    <td><span class="badge {{ $link->is_active ? 'badge-new' : 'badge-best' }}">{{ $link->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td>
                        <div class="admin-actions">
                            <button class="btn btn-secondary" data-modal-open="edit-link-{{ $link->id }}">Edit</button>
                            <form method="POST" action="{{ route('admin.marketplace-links.destroy', $link) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">Belum ada marketplace link.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:1rem;">{{ $links->links() }}</div>
</div>

<div class="modal" id="marketplace-create-modal" data-modal>
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-small">
        <div class="modal-head"><h3>Tambah Marketplace Link</h3><button data-modal-close class="btn btn-outline">Tutup</button></div>
        <form method="POST" action="{{ route('admin.marketplace-links.store') }}" class="admin-form-grid">
            @csrf
            <div class="form-group form-col-2"><label>Platform</label><input name="platform" placeholder="Shopee, WhatsApp, Tokopedia" required></div>
            <div class="form-group form-col-2"><label>URL</label><input type="url" name="url" placeholder="https://..." required></div>
            <div class="form-group form-col-2"><label><input type="checkbox" name="is_active" value="1" checked> Aktif</label></div>
            <div class="form-actions"><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

@foreach($links as $link)
<div class="modal" id="edit-link-{{ $link->id }}" data-modal>
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-small">
        <div class="modal-head"><h3>Edit Marketplace Link</h3><button data-modal-close class="btn btn-outline">Tutup</button></div>
        <form method="POST" action="{{ route('admin.marketplace-links.update', $link) }}" class="admin-form-grid">
            @csrf @method('PUT')
            <div class="form-group form-col-2"><label>Platform</label><input name="platform" value="{{ $link->platform }}" required></div>
            <div class="form-group form-col-2"><label>URL</label><input type="url" name="url" value="{{ $link->url }}" required></div>
            <div class="form-group form-col-2"><label><input type="checkbox" name="is_active" value="1" @checked($link->is_active)> Aktif</label></div>
            <div class="form-actions"><button class="btn btn-secondary">Update</button></div>
        </form>
    </div>
</div>
@endforeach
@endsection
