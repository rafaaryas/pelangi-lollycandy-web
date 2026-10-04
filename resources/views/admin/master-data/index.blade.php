@extends('layouts.admin')

@section('title', $title.' | Pelangi Admin')

@section('content')
<header class="module-page-head">
    <div><div class="admin-breadcrumb"><a href="{{ route('admin.dashboard') }}">Dashboard</a><span>/</span><strong>Master Data</strong></div><h1>{{ $title }}</h1><p>{{ $description }}</p></div>
    <button class="btn btn-primary" type="button" data-modal-open="master-create-modal"><x-icon name="plus" />Tambah {{ $title }}</button>
</header>

@php($filtersActive = request()->filled('q') || request()->filled('status'))
<form method="GET" class="table-toolbar" role="search">
    <label class="search-field"><x-icon name="search" /><input name="q" value="{{ request('q') }}" placeholder="Cari {{ strtolower($title) }}" aria-label="Cari {{ strtolower($title) }}"></label>
    <select name="status" aria-label="Filter status"><option value="">Semua status</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option></select>
    <button class="btn btn-secondary" type="submit"><x-icon name="sliders" />{{ $filtersActive ? 'Filter aktif' : 'Filter' }}</button>
    @if(request()->hasAny(['q', 'status']))<a class="toolbar-clear" href="{{ route($route.'.index') }}">Bersihkan</a>@endif
</form>

<div class="table-meta"><span>{{ $items->total() }} data</span></div>
<div class="admin-table-wrap module-table-wrap">
    <table class="admin-table module-table">
        <thead><tr>@foreach($columns as $key => $label)<th>{{ $label }}</th>@endforeach<th class="table-action-heading">Aksi</th></tr></thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                @foreach($columns as $key => $label)
                    <td>
                        @if($key === 'is_active')
                            <span class="state-text {{ $item->is_active ? 'state-active' : 'state-inactive' }}"><i></i>{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        @elseif(in_array($key, ['current_stock', 'minimum_stock'], true))
                            {{ \App\Support\Quantity::format($item->{$key}) }}
                        @elseif(str_ends_with($key, '_count'))
                            {{ number_format($item->{$key}, 0, ',', '.') }}
                        @else
                            {{ $item->{$key} ?: '—' }}
                        @endif
                    </td>
                @endforeach
                <td><div class="table-actions">
                    <x-icon-link icon="eye" label="Lihat detail {{ $item->name }}" href="{{ route($detailRoute, $item) }}" />
                    <x-icon-button icon="pencil" label="Edit {{ $item->name }}" data-modal-open="master-edit-{{ $item->id }}" />
                    @if($canDelete($item))
                        <x-icon-button icon="trash" label="Hapus {{ $item->name }}" variant="danger" data-modal-open="master-delete-{{ $item->id }}" />
                    @else
                        <span class="text-action-muted" title="Data yang memiliki riwayat terkait tidak dapat dihapus">{{ match ($title) {
                                'Supplier' => number_format($item->purchases_count, 0, ',', '.').' pembelian terkait',
                                'Pelanggan' => number_format($item->sales_count, 0, ',', '.').' penjualan terkait',
                                'Bahan Baku' => collect([
                                    $item->purchase_details_count.' pembelian',
                                    $item->production_materials_count.' pemakaian produksi',
                                    $item->stock_movements_count.' pergerakan stok',
                                ])->filter(fn ($value) => !str_starts_with($value, '0 '))->implode(' · '),
                                default => 'Memiliki riwayat terkait',
                            } }}</span>
                    @endif
                </div></td>
            </tr>
        @empty
            <tr><td colspan="{{ count($columns) + 1 }}"><div class="table-empty"><strong>Belum ada {{ strtolower($title) }}.</strong><span>Tambahkan data pertama untuk mulai mengelolanya.</span></div></td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="module-pagination">{{ $items->links() }}</div>

@foreach($items as $item)
<div class="modal" id="master-edit-{{ $item->id }}" data-modal data-open-on-error="{{ $errors->any() && old('_form') === 'edit-'.$item->id ? 'true' : 'false' }}">
    <div class="modal-backdrop" data-modal-close></div>
    <section class="modal-content modal-small">
        <div class="modal-head"><h2>Edit {{ $title }}</h2><button class="modal-close" type="button" data-modal-close aria-label="Tutup"><x-icon name="close" /></button></div>
        <form method="POST" action="{{ route($route.'.update', $item) }}" class="module-form-grid" data-loading-form>
            @csrf @method('PUT')
            <input type="hidden" name="_form" value="edit-{{ $item->id }}">
            @foreach($fields as $field)
                @php($value = $item->{$field['name']} ?? '')
                @if(($field['type'] ?? '') === 'checkbox')
                    <label class="checkbox-field"><input type="checkbox" name="{{ $field['name'] }}" value="1" @checked($value)><span>{{ $field['label'] }}</span></label>
                @elseif(($field['type'] ?? '') === 'textarea')
                    <label class="module-field module-field-wide">{{ $field['label'] }}<textarea name="{{ $field['name'] }}" rows="2" @if($errors->has($field['name'])) aria-invalid="true" aria-describedby="error-{{ $field['name'] }}" @endif>{{ old($field['name'], $value) }}</textarea><x-field-error :name="$field['name']" /></label>
                @else
                    <label class="module-field"><span>{{ $field['label'] }}@if($field['required'] ?? false) <span class="required-marker" aria-hidden="true">*</span>@endif</span><input name="{{ $field['name'] }}" type="{{ $field['type'] ?? 'text' }}" value="{{ old($field['name'], $value) }}" @if(isset($field['step'])) step="{{ $field['step'] }}" min="0" @endif @required($field['required'] ?? false) @if($errors->has($field['name'])) aria-invalid="true" aria-describedby="error-{{ $field['name'] }}" @endif><x-field-error :name="$field['name']" /></label>
                @endif
            @endforeach
            <div class="modal-form-actions"><button type="button" class="btn btn-secondary" data-modal-close>Tutup</button><button class="btn btn-primary" type="submit">Simpan perubahan</button></div>
        </form>
    </section>
</div>
@endforeach

@foreach($items as $item)
    @if($canDelete($item))
    <div class="modal" id="master-delete-{{ $item->id }}" data-modal>
        <div class="modal-backdrop" data-modal-close></div>
        <section class="modal-content modal-small confirm-dialog-content">
            <div class="modal-head"><h2>Hapus {{ strtolower($title) }}?</h2><button type="button" class="modal-close" data-modal-close aria-label="Tutup dialog"><x-icon name="close" /></button></div>
            <p><strong>{{ $item->name }}</strong> akan dihapus. Tindakan ini tidak dapat dibatalkan.</p>
            <form method="POST" action="{{ route($route.'.destroy', $item) }}" class="modal-form-actions" data-loading-form>
                @csrf @method('DELETE')
                <button type="button" data-modal-close class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-danger"><x-icon name="trash" />Hapus</button>
            </form>
        </section>
    </div>
    @endif
@endforeach

<div class="modal" id="master-create-modal" data-modal data-open-on-error="{{ $errors->any() && old('_form') === 'create' ? 'true' : 'false' }}">
    <div class="modal-backdrop" data-modal-close></div>
    <section class="modal-content modal-small">
        <div class="modal-head"><h2>Tambah {{ $title }}</h2><button class="modal-close" type="button" data-modal-close aria-label="Tutup"><x-icon name="close" /></button></div>
        <form method="POST" action="{{ route($route.'.store') }}" class="module-form-grid" data-loading-form>
            @csrf
            <input type="hidden" name="_form" value="create">
            @foreach($fields as $field)
                @if(($field['type'] ?? '') === 'checkbox')
                    <label class="checkbox-field"><input type="checkbox" name="{{ $field['name'] }}" value="1" checked><span>{{ $field['label'] }}</span></label>
                @elseif(($field['type'] ?? '') === 'textarea')
                    <label class="module-field module-field-wide">{{ $field['label'] }}<textarea name="{{ $field['name'] }}" rows="2" @if($errors->has($field['name'])) aria-invalid="true" aria-describedby="error-{{ $field['name'] }}" @endif>{{ old($field['name']) }}</textarea><x-field-error :name="$field['name']" /></label>
                @else
                    <label class="module-field"><span>{{ $field['label'] }}@if($field['required'] ?? false) <span class="required-marker" aria-hidden="true">*</span>@endif</span><input name="{{ $field['name'] }}" type="{{ $field['type'] ?? 'text' }}" value="{{ old($field['name']) }}" placeholder="{{ $field['placeholder'] ?? '' }}" @if(isset($field['step'])) step="{{ $field['step'] }}" min="0" @endif @required($field['required'] ?? false) @if($errors->has($field['name'])) aria-invalid="true" aria-describedby="error-{{ $field['name'] }}" @endif><x-field-error :name="$field['name']" /></label>
                @endif
            @endforeach
            @if($title === 'Bahan Baku')
                <label class="module-field">Saldo awal stok<input name="opening_stock" type="number" min="0" step="0.001" value="0"><small>Saldo awal dicatat sebagai histori penyesuaian stok.</small></label>
            @endif
            <div class="modal-form-actions"><button type="button" class="btn btn-secondary" data-modal-close>Batal</button><button class="btn btn-primary" type="submit">Simpan {{ $title }}</button></div>
        </form>
    </section>
</div>
@endsection
