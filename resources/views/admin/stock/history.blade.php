@extends('layouts.admin')

@section('title', 'Histori Stok | Pelangi Admin')

@section('content')
<header class="module-page-head detail-page-head"><div><div class="admin-breadcrumb"><a href="{{ route('admin.stock.index', ['tab' => $type]) }}"><x-icon name="arrow-left" size="15" /> Stok</a><span>/</span><strong>Histori</strong></div><h1>{{ $item->name }}</h1><p>Riwayat perubahan saldo item ini.</p></div><button class="btn btn-secondary" type="button" data-modal-open="stock-adjust-modal"><x-icon name="sliders" />Sesuaikan stok</button></header>
<section class="document-summary stock-detail-summary">
    <div><span>Stok saat ini</span><strong>{{ $currentStock === null ? 'Belum dicatat' : number_format((float) $currentStock, 3, ',', '.').' '.$unit }}</strong></div>
    <div><span>Stok minimum</span><strong>{{ number_format((float) $minimumStock, 3, ',', '.') }} {{ $unit }}</strong></div>
    <div><span>Status</span><strong>{{ $currentStock === null ? 'Belum dicatat' : ((float) $currentStock <= 0 ? 'Habis' : ((float) $minimumStock > 0 && (float) $currentStock <= (float) $minimumStock ? 'Rendah' : 'Aman')) }}</strong></div>
</section>
<section class="document-section"><div class="document-section-heading"><div><h2>Pergerakan stok</h2><p>Saldo berjalan dihitung dari awal pencatatan.</p></div></div>
    <div class="admin-table-wrap module-table-wrap"><table class="admin-table module-table"><thead><tr><th>Tanggal</th><th>Jenis</th><th>Referensi</th><th class="numeric-cell">Masuk</th><th class="numeric-cell">Keluar</th><th class="numeric-cell">Saldo</th></tr></thead><tbody>
        @forelse($history as $row)
            @php($source = $row['movement']->source)
            @php($reference = $source?->reference_number ?? $source?->production_number ?? $source?->invoice_number ?? 'Penyesuaian stok')
            <tr><td>{{ $row['movement']->movement_date->format('d M Y, H:i') }}</td><td>{{ str_replace('_', ' ', ucfirst($row['movement']->movement_type)) }}</td><td>{{ $reference }}</td><td class="numeric-cell">{{ $row['incoming'] > 0 ? number_format($row['incoming'], 3, ',', '.') : '—' }}</td><td class="numeric-cell">{{ $row['outgoing'] > 0 ? number_format($row['outgoing'], 3, ',', '.') : '—' }}</td><td class="numeric-cell">{{ number_format($row['balance'], 3, ',', '.') }} {{ $unit }}</td></tr>
        @empty
            <tr><td colspan="6"><div class="table-empty"><strong>Belum ada histori stok.</strong><span>Perubahan saldo akan tercatat di sini.</span></div></td></tr>
        @endforelse
    </tbody></table></div>
</section>
<div class="modal" id="stock-adjust-modal" data-modal><div class="modal-backdrop" data-modal-close></div><section class="modal-content modal-small">
    <div class="modal-head"><h2>Sesuaikan stok</h2><button type="button" class="modal-close" data-modal-close aria-label="Tutup"><x-icon name="close" /></button></div>
    <form method="POST" action="{{ route('admin.stock.adjust') }}" class="module-form-grid" data-loading-form>@csrf
        <input type="hidden" name="stock_type" value="{{ $type === 'products' ? 'product' : 'material' }}"><input type="hidden" name="stock_id" value="{{ $item->id }}">
        <label class="module-field">Arah<select name="direction"><option value="in">Stok masuk</option><option value="out">Stok keluar</option></select></label>
        <label class="module-field">Jumlah ({{ $unit }})<input type="number" name="quantity" min="0.001" step="0.001" required></label>
        <label class="module-field module-field-wide">Alasan<textarea name="notes" rows="2" placeholder="Contoh: hasil stok opname"></textarea></label>
        <div class="modal-form-actions"><button type="button" class="btn btn-secondary" data-modal-close>Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
    </form>
</section></div>
@endsection
