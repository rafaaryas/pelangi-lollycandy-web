@extends('layouts.admin')

@section('title', 'Rekap & Laporan | Pelangi Admin')

@section('content')
<header class="module-page-head"><div><div class="admin-breadcrumb"><a href="{{ route('admin.dashboard') }}">Dashboard</a><span>/</span><strong>Laporan</strong></div><h1>Rekap &amp; Laporan</h1><p>Ringkasan transaksi dan pergerakan persediaan pada periode pilihan.</p></div></header>
<nav class="report-tabs" aria-label="Jenis laporan">
    @foreach(['sales' => 'Penjualan', 'purchases' => 'Pembelian', 'productions' => 'Produksi', 'stock' => 'Stok'] as $value => $label)
        <a href="{{ route('admin.reports.index', ['tab' => $value, 'from' => $from, 'to' => $to]) }}" class="{{ $tab === $value ? 'is-active' : '' }}">{{ $label }}</a>
    @endforeach
</nav>
<form method="GET" class="table-toolbar report-toolbar"><input type="hidden" name="tab" value="{{ $tab }}">
    <label class="module-field">Dari tanggal<input type="date" name="from" value="{{ $from }}" required></label>
    <label class="module-field">Sampai tanggal<input type="date" name="to" value="{{ $to }}" required></label>
    <button class="btn btn-secondary" type="submit">Tampilkan</button>
</form>
<section class="report-summary">
    <div><span>{{ $tab === 'stock' ? 'Pergerakan stok' : 'Jumlah transaksi' }}</span><strong>{{ number_format($summary['count'], 0, ',', '.') }}</strong></div>
    @if(in_array($tab, ['sales', 'purchases']))<div><span>Total nilai</span><strong>Rp{{ number_format($summary['amount'], 0, ',', '.') }}</strong></div><div><span>Total kuantitas</span><strong>{{ number_format($summary['quantity'], 3, ',', '.') }}</strong></div>
    @elseif($tab === 'productions')<div><span>Total hasil produksi</span><strong>{{ number_format($summary['quantity'], 3, ',', '.') }} pcs</strong></div>
    @else<div><span>Pergerakan masuk</span><strong>{{ number_format($summary['in'], 0, ',', '.') }}</strong></div><div><span>Pergerakan keluar</span><strong>{{ number_format($summary['out'], 0, ',', '.') }}</strong></div>@endif
</section>
<div class="report-period-label">Periode {{ \Illuminate\Support\Carbon::parse($from)->format('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}</div>
<div class="admin-table-wrap module-table-wrap"><table class="admin-table module-table"><thead>
    @if($tab === 'sales')<tr><th>No. Invoice</th><th>Tanggal</th><th>Pelanggan</th><th>Item</th><th class="numeric-cell">Total</th></tr>
    @elseif($tab === 'purchases')<tr><th>No. Pembelian</th><th>Tanggal</th><th>Supplier</th><th>Item</th><th class="numeric-cell">Total</th></tr>
    @elseif($tab === 'productions')<tr><th>No. Produksi</th><th>Tanggal</th><th>Bahan</th><th>Hasil</th></tr>
    @else<tr><th>Tanggal</th><th>Item</th><th>Jenis</th><th>Referensi</th><th>Masuk</th><th>Keluar</th></tr>@endif
</thead><tbody>
    @forelse($items as $item)
        @if($tab === 'sales')<tr><td><a class="table-primary-link" href="{{ route('admin.sales.show', $item) }}">{{ $item->invoice_number }}</a></td><td>{{ $item->sale_date->format('d M Y') }}</td><td>{{ $item->customer?->name ?? 'Pelanggan umum' }}</td><td>{{ $item->details_count }}</td><td class="numeric-cell">Rp{{ number_format($item->total_amount, 0, ',', '.') }}</td></tr>
        @elseif($tab === 'purchases')<tr><td><a class="table-primary-link" href="{{ route('admin.purchases.show', $item) }}">{{ $item->reference_number }}</a></td><td>{{ $item->purchase_date->format('d M Y') }}</td><td>{{ $item->supplier->name }}</td><td>{{ $item->details_count }}</td><td class="numeric-cell">Rp{{ number_format($item->total_amount, 0, ',', '.') }}</td></tr>
        @elseif($tab === 'productions')<tr><td><a class="table-primary-link" href="{{ route('admin.productions.show', $item) }}">{{ $item->production_number }}</a></td><td>{{ $item->production_date->format('d M Y') }}</td><td>{{ $item->materials->count() }} jenis</td><td>{{ number_format((float) $item->results->sum('quantity_produced'), 3, ',', '.') }} pcs</td></tr>
        @else
            @php($source = $item->source)
            <tr><td>{{ $item->movement_date->format('d M Y, H:i') }}</td><td>{{ $item->stockable?->name ?? 'Item stok' }}</td><td>{{ str_replace('_', ' ', ucfirst($item->movement_type)) }}</td><td>{{ $source?->reference_number ?? $source?->production_number ?? $source?->invoice_number ?? 'Penyesuaian' }}</td><td>{{ in_array($item->movement_type, ['purchase_in', 'production_in', 'adjustment_in'], true) ? number_format($item->quantity, 3, ',', '.').' '.($item->unit ?: 'pcs') : '—' }}</td><td>{{ in_array($item->movement_type, ['production_out', 'sale_out', 'adjustment_out'], true) ? number_format($item->quantity, 3, ',', '.').' '.($item->unit ?: 'pcs') : '—' }}</td></tr>
        @endif
    @empty
        <tr><td colspan="6"><div class="table-empty"><strong>Belum ada data pada periode ini.</strong><span>Transaksi yang dikonfirmasi akan tercantum di laporan.</span></div></td></tr>
    @endforelse
</tbody></table></div>
<div class="module-pagination">{{ $items->links() }}</div>
@endsection
