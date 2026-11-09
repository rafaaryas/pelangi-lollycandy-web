@extends('layouts.admin')
@section('title', 'Marketplace Links')

@section('content')
<div class="admin-page-head">
    <h1>Marketplace Links</h1>
    <button class="btn btn-primary" type="button" data-modal-open="marketplace-create-modal"><x-icon name="plus" />Tambah Link</button>
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
                    <td><span class="state-text {{ $link->is_active ? 'state-active' : 'state-inactive' }}"><i></i>{{ $link->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td>
                        <div class="admin-actions">
                            <x-icon-button icon="pencil" label="Edit {{ $link->platform }}" data-modal-open="edit-link-{{ $link->id }}" />
                            <x-icon-button icon="trash" label="Hapus {{ $link->platform }}" variant="danger" data-modal-open="delete-link-{{ $link->id }}" />
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

<div class="modal" id="marketplace-create-modal" data-modal data-open-on-error="{{ $errors->any() && old('_form') === 'create' ? 'true' : 'false' }}">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-small">
        <div class="modal-head"><h3>Tambah Marketplace Link</h3><button data-modal-close class="btn btn-outline">Tutup</button></div>
        <form method="POST" action="{{ route('admin.marketplace-links.store') }}" class="admin-form-grid" data-loading-form>
            @csrf
            <input type="hidden" name="_form" value="create">
            <div class="form-group form-col-2"><label for="marketplace-create-platform">Platform</label><input id="marketplace-create-platform" name="platform" value="{{ old('_form') === 'create' ? old('platform') : '' }}" placeholder="Shopee, WhatsApp, Tokopedia" required @if($errors->has('platform')) aria-invalid="true" aria-describedby="error-platform" @endif><x-field-error name="platform" /></div>
            <div class="form-group form-col-2"><label for="marketplace-create-url">URL</label><input id="marketplace-create-url" type="url" name="url" value="{{ old('_form') === 'create' ? old('url') : '' }}" placeholder="https://..." required @if($errors->has('url')) aria-invalid="true" aria-describedby="error-url" @endif><x-field-error name="url" /></div>
            <div class="form-group form-col-2"><label><input type="checkbox" name="is_active" value="1" checked> Aktif</label></div>
            <div class="form-actions"><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

@foreach($links as $link)
<div class="modal" id="edit-link-{{ $link->id }}" data-modal data-open-on-error="{{ $errors->any() && old('_form') === 'edit-'.$link->id ? 'true' : 'false' }}">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-small">
        <div class="modal-head"><h3>Edit Marketplace Link</h3><button data-modal-close class="btn btn-outline">Tutup</button></div>
        <form method="POST" action="{{ route('admin.marketplace-links.update', $link) }}" class="admin-form-grid" data-loading-form>
            @csrf @method('PUT')
            <input type="hidden" name="_form" value="edit-{{ $link->id }}">
            <div class="form-group form-col-2"><label for="marketplace-edit-platform-{{ $link->id }}">Platform</label><input id="marketplace-edit-platform-{{ $link->id }}" name="platform" value="{{ old('_form') === 'edit-'.$link->id ? old('platform', $link->platform) : $link->platform }}" required @if($errors->has('platform') && old('_form') === 'edit-'.$link->id) aria-invalid="true" aria-describedby="error-platform" @endif>@if(old('_form') === 'edit-'.$link->id)<x-field-error name="platform" />@endif</div>
            <div class="form-group form-col-2"><label for="marketplace-edit-url-{{ $link->id }}">URL</label><input id="marketplace-edit-url-{{ $link->id }}" type="url" name="url" value="{{ old('_form') === 'edit-'.$link->id ? old('url', $link->url) : $link->url }}" required @if($errors->has('url') && old('_form') === 'edit-'.$link->id) aria-invalid="true" aria-describedby="error-url" @endif>@if(old('_form') === 'edit-'.$link->id)<x-field-error name="url" />@endif</div>
            <div class="form-group form-col-2"><label><input type="checkbox" name="is_active" value="1" @checked($link->is_active)> Aktif</label></div>
            <div class="form-actions"><button class="btn btn-secondary">Update</button></div>
        </form>
    </div>
</div>
@endforeach
@foreach($links as $link)
<div class="modal" id="delete-link-{{ $link->id }}" data-modal>
    <div class="modal-backdrop" data-modal-close></div>
    <section class="modal-content modal-small confirm-dialog-content">
        <div class="modal-head"><h2>Hapus tautan?</h2><button type="button" class="modal-close" data-modal-close aria-label="Tutup dialog"><x-icon name="close" /></button></div>
        <p>Tautan marketplace <strong>{{ $link->platform }}</strong> akan dihapus.</p>
        <form method="POST" action="{{ route('admin.marketplace-links.destroy', $link) }}" class="modal-form-actions" data-loading-form>
            @csrf @method('DELETE')
            <button type="button" data-modal-close class="btn btn-secondary">Batal</button>
            <button type="submit" class="btn btn-danger"><x-icon name="trash" />Hapus tautan</button>
        </form>
    </section>
</div>
@endforeach
@endsection
