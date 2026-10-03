@extends('layouts.admin')

@section('title', $title.' | Pelangi Admin')

@section('content')
@php
    $reference = ['purchases' => $record->reference_number, 'productions' => $record->production_number, 'sales' => $record->invoice_number][$kind];
    $date = ['purchases' => $record->purchase_date, 'productions' => $record->production_date, 'sales' => $record->sale_date][$kind];
    $dateLabel = ['purchases' => 'Tanggal pembelian', 'productions' => 'Tanggal produksi', 'sales' => 'Tanggal penjualan'][$kind];
@endphp
<header class="module-page-head detail-page-head">
    <div><div class="admin-breadcrumb"><a href="{{ route('admin.'.$kind.'.index') }}">{{ ucfirst($kind) }}</a><span>/</span><strong>{{ $reference }}</strong></div><h1>{{ $reference }}</h1><p>{{ $title }}</p></div>
    <span class="transaction-status status-{{ $record->status }}">{{ match($record->status) { 'confirmed' => 'Terkonfirmasi', 'cancelled' => 'Dibatalkan', default => 'Draft' } }}</span>
</header>
<section class="document-summary">
    @if($kind === 'purchases')<div><span>Supplier</span><strong>{{ $record->supplier->name }}</strong></div>
    @elseif($kind === 'sales')<div><span>Pelanggan</span><strong>{{ $record->customer?->name ?? 'Pelanggan umum' }}</strong></div>
    @endif
    <div><span>{{ $dateLabel }}</span><strong>{{ $date->format('d F Y') }}</strong></div>
    <div><span>Status</span><strong>{{ ucfirst($record->status) }}</strong></div>
    @if($kind !== 'productions')<div><span>Total</span><strong>Rp{{ number_format($record->total_amount, 0, ',', '.') }}</strong></div>@endif
</section>

@if($kind === 'productions')
    <section class="document-section"><h2>Bahan baku digunakan</h2><div class="admin-table-wrap module-table-wrap"><table class="admin-table module-table"><thead><tr><th>Bahan</th><th>Jumlah</th><th>Satuan</th></tr></thead><tbody>
        @forelse($record->materials as $line)<tr><td>{{ $line->rawMaterial->name }}</td><td>{{ number_format($line->quantity_used, 3, ',', '.') }}</td><td>{{ $line->unit ?: $line->rawMaterial->unit }}</td></tr>@empty<tr><td colspan="3">Tidak ada bahan baku.</td></tr>@endforelse
    </tbody></table></div></section>
    <section class="document-section"><h2>Hasil produksi</h2><div class="admin-table-wrap module-table-wrap"><table class="admin-table module-table"><thead><tr><th>Produk</th><th>Jumlah dihasilkan</th></tr></thead><tbody>
        @forelse($record->results as $line)<tr><td>{{ $line->product->name }}</td><td>{{ number_format($line->quantity_produced, 3, ',', '.') }} pcs</td></tr>@empty<tr><td colspan="2">Belum ada hasil.</td></tr>@endforelse
    </tbody></table></div></section>
@else
    <section class="document-section"><h2>{{ $kind === 'purchases' ? 'Rincian pembelian' : 'Rincian penjualan' }}</h2><div class="admin-table-wrap module-table-wrap"><table class="admin-table module-table"><thead><tr><th>{{ $kind === 'purchases' ? 'Bahan baku' : 'Produk' }}</th><th class="numeric-cell">Jumlah</th><th>{{ $kind === 'purchases' ? 'Satuan' : 'Harga satuan' }}</th><th class="numeric-cell">Subtotal</th></tr></thead><tbody>
        @foreach($record->details as $line)<tr>
            <td>{{ $kind === 'purchases' ? $line->rawMaterial->name : $line->product->name }}</td>
            <td class="numeric-cell">{{ number_format($line->quantity, 3, ',', '.') }}</td>
            <td>{{ $kind === 'purchases' ? ($line->unit ?: $line->rawMaterial->unit) : 'Rp'.number_format($line->unit_price, 0, ',', '.') }}</td>
            <td class="numeric-cell">Rp{{ number_format($line->subtotal, 0, ',', '.') }}</td>
        </tr>@endforeach
        <tr class="document-total-row"><td colspan="3">Total</td><td class="numeric-cell">Rp{{ number_format($record->total_amount, 0, ',', '.') }}</td></tr>
    </tbody></table></div></section>
@endif

@if($record->notes)<section class="document-section document-notes"><h2>Catatan</h2><p>{{ $record->notes }}</p></section>@endif
<section class="document-section"><div class="document-section-heading"><div><h2>Histori perubahan stok</h2><p>Pergerakan stok yang tercatat untuk transaksi ini.</p></div></div>
    @if($movements->isNotEmpty())
        <div class="admin-table-wrap module-table-wrap"><table class="admin-table module-table"><thead><tr><th>Waktu</th><th>Item</th><th>Jenis</th><th class="numeric-cell">Jumlah</th><th>Catatan</th></tr></thead><tbody>
            @foreach($movements as $movement)<tr><td>{{ $movement->movement_date->format('d M Y, H:i') }}</td><td>{{ $movement->stockable?->name ?? 'Item stok' }}</td><td>{{ str_replace('_', ' ', ucfirst($movement->movement_type)) }}</td><td class="numeric-cell">{{ number_format($movement->quantity, 3, ',', '.') }}</td><td>{{ $movement->notes ?: '—' }}</td></tr>@endforeach
        </tbody></table></div>
    @else
        <p class="inline-empty">Belum ada perubahan stok dari transaksi ini.</p>
    @endif
</section>

<footer class="transaction-form-footer">
    <a class="text-action" href="{{ route('admin.'.$kind.'.index') }}">Kembali ke daftar</a>
    <div>
        @if($record->status === 'draft')<a class="btn btn-secondary" href="{{ route('admin.'.$kind.'.edit', $record) }}"><x-icon name="pencil" />Edit Draft</a><form method="POST" action="{{ route('admin.'.$kind.'.confirm', $record) }}" data-loading-form>@csrf<button class="btn btn-primary" type="submit"><x-icon name="check" />Konfirmasi</button></form>@endif
        @if($record->status !== 'cancelled')<form method="POST" action="{{ route('admin.'.$kind.'.cancel', $record) }}" data-confirm-title="Batalkan transaksi?" data-confirm-message="Transaksi {{ $reference }} akan dibatalkan. Stok akan disesuaikan jika transaksi ini sudah dikonfirmasi." data-confirm-label="Batalkan" data-loading-form>@csrf<button class="btn btn-danger-outline" type="submit"><x-icon name="close" />Batalkan</button></form>@endif
    </div>
</footer>
@endsection
