@extends('layouts.admin')

@section('title', 'Rekap & Laporan | Pelangi Admin')

@section('content')
<header class="module-page-head"><div><div class="admin-breadcrumb"><a href="{{ route('admin.dashboard') }}">Dashboard</a><span>/</span><strong>Laporan</strong></div><h1>Rekap &amp; Laporan</h1><p>Ringkasan dan rincian transaksi pada periode pilihan.</p></div></header>
<nav class="report-tabs" aria-label="Jenis laporan">
    @foreach(['sales' => 'Penjualan', 'purchases' => 'Pembelian', 'productions' => 'Produksi', 'stock' => 'Stok'] as $value => $label)
        <a href="{{ route('admin.reports.index', $value === 'stock' ? ['tab' => $value, 'month' => $month, 'year' => $year] : ['tab' => $value, 'from' => $from, 'to' => $to]) }}" class="{{ $tab === $value ? 'is-active' : '' }}">{{ $label }}</a>
    @endforeach
</nav>
<form method="GET" class="table-toolbar report-toolbar"><input type="hidden" name="tab" value="{{ $tab }}">
    @if($tab === 'stock')
    <input type="hidden" name="mode" value="{{ $mode }}">
    <label class="module-field">Bulan<select name="month">@foreach(range(1, 12) as $number)<option value="{{ $number }}" @selected($month === $number)>{{ \Carbon\CarbonImmutable::create(2026, $number, 1)->locale('id')->translatedFormat('F') }}</option>@endforeach</select></label>
    <label class="module-field">Tahun<input type="number" name="year" value="{{ $year }}" min="2000" max="2100" required></label>
    @else
    <label class="module-field">Dari tanggal<input type="date" name="from" value="{{ $from }}" required></label>
    <label class="module-field">Sampai tanggal<input type="date" name="to" value="{{ $to }}" required></label>
    @endif
    <button class="btn btn-secondary" type="submit">Tampilkan</button>
</form>
@if($tab === 'stock')
<nav class="report-mode-tabs" aria-label="Tampilan stok">
    <a class="{{ $mode === 'summary' ? 'is-active' : '' }}" href="{{ route('admin.reports.index', ['tab' => 'stock', 'mode' => 'summary', 'month' => $month, 'year' => $year]) }}">Rekap Stok Bahan Baku</a>
    <a class="{{ $mode === 'detail' ? 'is-active' : '' }}" href="{{ route('admin.reports.index', ['tab' => 'stock', 'mode' => 'detail', 'month' => $month, 'year' => $year]) }}">Laporan Detail</a>
</nav>
@endif
<section class="report-summary">
    <div><span>{{ $tab === 'stock' ? 'Bahan baku' : 'Jumlah transaksi' }}</span><strong>{{ number_format($summary['count'], 0, ',', '.') }}</strong></div>
    @if(in_array($tab, ['sales', 'purchases']))<div><span>Total nilai</span><strong>Rp{{ number_format($summary['amount'], 0, ',', '.') }}</strong></div><div><span>Total kuantitas</span><strong>{{ \App\Support\Quantity::format($summary['quantity']) }}</strong></div>
    @elseif($tab === 'productions')<div><span>Total hasil produksi</span><strong>{{ \App\Support\Quantity::format($summary['quantity']) }} pcs</strong></div>
    @else<div><span>Bahan bertambah</span><strong>{{ number_format($summary['in'], 0, ',', '.') }}</strong></div><div><span>Bahan berkurang</span><strong>{{ number_format($summary['out'], 0, ',', '.') }}</strong></div>@endif
</section>
<div class="report-period-label">Periode {{ \Illuminate\Support\Carbon::parse($from)->format('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}</div>
@if($tab === 'stock' && $mode === 'summary')
<div class="admin-table-wrap module-table-wrap"><table class="admin-table module-table"><thead><tr><th>Bahan Baku</th><th class="numeric-cell">Stok Awal</th><th class="numeric-cell">Stok Masuk</th><th class="numeric-cell">Stok Terpakai</th><th class="numeric-cell">Stok Akhir</th></tr></thead><tbody>
    @forelse($stockRows as $row)<tr><td><strong>{{ $row['material']->name }}</strong><small class="stock-unit">{{ $row['material']->unit }}</small></td><td class="numeric-cell">{{ \App\Support\Quantity::format($row['opening']) }}</td><td class="numeric-cell">{{ \App\Support\Quantity::format($row['incoming']) }}</td><td class="numeric-cell">{{ \App\Support\Quantity::format($row['used']) }}</td><td class="numeric-cell"><strong>{{ \App\Support\Quantity::format($row['closing']) }}</strong></td></tr>
    @empty<tr><td colspan="5"><div class="table-empty"><strong>Belum ada bahan baku.</strong></div></td></tr>@endforelse
</tbody></table></div>
<p class="report-stock-note">Stok terpakai mencakup pemakaian produksi dan koreksi stok keluar. Stok akhir = stok awal + stok masuk − stok terpakai.</p>
@else
<div class="admin-table-wrap module-table-wrap"><table class="admin-table module-table"><thead>
    @if($tab === 'sales')<tr><th>No. Invoice</th><th>Tanggal</th><th>Pelanggan</th><th>Item</th><th class="numeric-cell">Total</th></tr>
    @elseif($tab === 'purchases')<tr><th>No. Pembelian</th><th>Tanggal</th><th>Supplier</th><th>Item</th><th class="numeric-cell">Total</th></tr>
    @elseif($tab === 'productions')<tr><th>No. Produksi</th><th>Tanggal</th><th>Bahan</th><th>Hasil</th></tr>
    @else<tr><th>Tanggal</th><th>Bahan Baku</th><th>Jenis</th><th class="numeric-cell">Jumlah</th><th>Referensi</th></tr>@endif
</thead><tbody>
    @forelse($items as $item)
        @if($tab === 'sales')<tr><td><a class="table-primary-link" href="{{ route('admin.sales.show', $item) }}">{{ $item->invoice_number }}</a></td><td>{{ $item->sale_date->format('d M Y') }}</td><td>{{ $item->customer?->name ?? 'Pelanggan umum' }}</td><td>{{ $item->details_count }}</td><td class="numeric-cell">Rp{{ number_format($item->total_amount, 0, ',', '.') }}</td></tr>
        @elseif($tab === 'purchases')<tr><td><a class="table-primary-link" href="{{ route('admin.purchases.show', $item) }}">{{ $item->reference_number }}</a></td><td>{{ $item->purchase_date->format('d M Y') }}</td><td>{{ $item->supplier->name }}</td><td>{{ $item->details_count }}</td><td class="numeric-cell">Rp{{ number_format($item->total_amount, 0, ',', '.') }}</td></tr>
        @elseif($tab === 'productions')<tr><td><a class="table-primary-link" href="{{ route('admin.productions.show', $item) }}">{{ $item->production_number }}</a></td><td>{{ $item->production_date->format('d M Y') }}</td><td>{{ $item->materials->count() }} jenis</td><td>{{ \App\Support\Quantity::format($item->results->sum('quantity_produced')) }} pcs</td></tr>
        @else
            @php($source = $item->source)
            @php($incoming = in_array($item->movement_type, ['purchase_in', 'adjustment_in'], true))
            @php($reference = $source?->purchase?->reference_number ?? $source?->production?->production_number ?? $item->notes ?? 'Penambahan stok')
            <tr><td>{{ $item->movement_date->format('d M Y, H:i') }}</td><td>{{ $item->stockable?->name ?? 'Bahan baku' }}</td><td>{{ match($item->movement_type) { 'purchase_in' => 'Pembelian', 'production_out' => 'Produksi', 'adjustment_in' => 'Stok masuk', default => 'Koreksi stok keluar' } }}</td><td class="numeric-cell">{{ $incoming ? '+' : '−' }}{{ \App\Support\Quantity::format($item->quantity) }} {{ $item->unit }}</td><td>{{ $reference }}</td></tr>
        @endif
    @empty
        <tr><td colspan="{{ $tab === 'stock' ? 5 : 6 }}"><div class="table-empty"><strong>Belum ada data pada periode ini.</strong><span>Transaksi yang dikonfirmasi akan tercantum di laporan.</span></div></td></tr>
    @endforelse
</tbody></table></div>
<div class="module-pagination">{{ $items->links() }}</div>
@endif
@endsection
