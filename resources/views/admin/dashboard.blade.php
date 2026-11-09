@extends('layouts.admin')

@section('title', 'Dashboard Operasional')

@section('content')
<div class="admin-page-head business-page-head">
    <div>
        <div class="admin-breadcrumb"><span>Pelangi Admin</span><span aria-hidden="true">/</span><strong>Ringkasan</strong></div>
        <h1>Dashboard</h1>
    </div>
    <form class="dashboard-period" method="GET" action="{{ route('admin.dashboard') }}">
        <label for="dashboard-month"><x-icon name="calendar" size="17" /> Periode transaksi</label>
        <div><input id="dashboard-month" type="month" name="month" value="{{ $selectedMonth }}" max="{{ $currentMonth }}" required><button class="btn btn-secondary" type="submit">Terapkan</button></div>
    </form>
</div>

@php
    $kpis = [
        ['products', 'Total Produk', 'package', 'products', 'Produk aktif', 'Lihat produk'],
        ['rawMaterials', 'Bahan Baku', 'boxes', 'raw-materials', 'Bahan aktif', 'Lihat bahan baku'],
        ['suppliers', 'Supplier', 'truck', 'suppliers', 'Supplier aktif', 'Lihat supplier'],
        ['customers', 'Pelanggan', 'users', 'customers', 'Pelanggan aktif', 'Lihat pelanggan'],
        ['salesAmount', $selectedMonth === $currentMonth ? 'Penjualan Bulan Ini' : 'Penjualan Periode Ini', 'receipt', 'sales', number_format($stats['salesCount'], 0, ',', '.').' transaksi terkonfirmasi', 'Lihat penjualan'],
        ['productionQuantity', $selectedMonth === $currentMonth ? 'Produksi Bulan Ini' : 'Produksi Periode Ini', 'factory', 'productions', number_format($stats['productionCount'], 0, ',', '.').' batch terkonfirmasi · pcs', 'Lihat produksi'],
    ];
@endphp
<section class="admin-kpi-grid business-kpi-grid" aria-label="Ringkasan usaha">
    @foreach($kpis as [$key, $label, $icon, $resource, $note, $action])
        <a class="card business-kpi-card semantic-{{ $resource }}" href="{{ route('admin.'.$resource.'.index', in_array($resource, ['sales', 'productions']) ? $periodFilters : ['status' => 'active']) }}">
            <span class="kpi-top"><span class="semantic-icon"><x-icon :name="$icon" size="19" /></span><x-icon name="arrow-up-right" size="16" /></span>
            <span class="kpi-label">{{ $label }}</span>
            <strong class="kpi-value {{ $resource === 'sales' ? 'kpi-currency' : '' }}">{{ $resource === 'sales' ? 'Rp' : '' }}{{ number_format($stats[$key], 0, ',', '.') }}</strong>
            <span class="kpi-note">{{ $note }}</span>
            <span class="kpi-action">{{ $action }}</span>
        </a>
    @endforeach
</section>

<section class="dashboard-chart-grid" aria-label="Tren operasional enam bulan">
    <article class="card dashboard-panel chart-panel">
        <div class="panel-heading">
            <div><h2>Penjualan 6 Bulan Terakhir</h2></div>
            <a class="text-action" href="{{ route('admin.sales.index', $periodFilters) }}">Lihat Detail <x-icon name="arrow-up-right" size="14" /></a>
        </div>
        @if($months->sum('sales') > 0)
            <div class="chart-scale"><span>Rp0</span><span>Maks. Rp{{ number_format($salesMax, 0, ',', '.') }}</span></div>
            <div class="mini-chart" role="group" aria-label="Grafik nilai penjualan enam bulan terakhir. Fokus pada batang untuk melihat rincian tiap bulan.">
                <div class="chart-grid-lines"><i></i><i></i><i></i></div>
                <div class="chart-columns">
                    @foreach($months as $month)
                        <div class="chart-column">
                            <div class="chart-bar-wrap">
                                <a href="{{ route('admin.sales.index', $month['filters']) }}" class="chart-bar chart-bar-sales" style="--bar-height: {{ ($month['sales'] / $salesMax) * 100 }}%" data-chart-bar data-chart-period="{{ $month['period'] }}" data-chart-label="Total Penjualan" data-chart-value="Rp{{ number_format($month['sales'], 0, ',', '.') }}" data-chart-count="{{ number_format($month['salesCount'], 0, ',', '.') }} transaksi" aria-label="{{ $month['period'] }}: penjualan Rp{{ number_format($month['sales'], 0, ',', '.') }}, {{ number_format($month['salesCount'], 0, ',', '.') }} transaksi"></a>
                            </div>
                            <a class="chart-month" href="{{ route('admin.sales.index', $month['filters']) }}" aria-label="Lihat penjualan {{ $month['period'] }}">{{ $month['label'] }}</a>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="chart-footnote"><span class="legend-dot legend-sales"></span> Penjualan · pilih bulan untuk rincian</div>
        @else
            <div class="dashboard-empty"><span class="empty-symbol">—</span><p>Belum ada transaksi penjualan pada periode ini.</p></div>
        @endif
    </article>

    <article class="card dashboard-panel chart-panel">
        <div class="panel-heading">
            <div><h2>Produksi 6 Bulan Terakhir</h2></div>
            <a class="text-action" href="{{ route('admin.productions.index', $periodFilters) }}">Lihat Detail <x-icon name="arrow-up-right" size="14" /></a>
        </div>
        @if($months->sum('production') > 0)
            <div class="chart-scale"><span>0 pcs</span><span>Maks. {{ number_format($productionMax, 0, ',', '.') }} pcs</span></div>
            <div class="mini-chart" role="group" aria-label="Grafik jumlah produksi enam bulan terakhir. Fokus pada batang untuk melihat rincian tiap bulan.">
                <div class="chart-grid-lines"><i></i><i></i><i></i></div>
                <div class="chart-columns">
                    @foreach($months as $month)
                        <div class="chart-column">
                            <div class="chart-bar-wrap">
                                <a href="{{ route('admin.productions.index', $month['filters']) }}" class="chart-bar chart-bar-production" style="--bar-height: {{ ($month['production'] / $productionMax) * 100 }}%" data-chart-bar data-chart-period="{{ $month['period'] }}" data-chart-label="Hasil Produksi" data-chart-value="{{ number_format($month['production'], 0, ',', '.') }} produk" data-chart-count="{{ number_format($month['productionCount'], 0, ',', '.') }} batch" aria-label="{{ $month['period'] }}: produksi {{ number_format($month['production'], 0, ',', '.') }} produk dari {{ number_format($month['productionCount'], 0, ',', '.') }} batch"></a>
                            </div>
                            <a class="chart-month" href="{{ route('admin.productions.index', $month['filters']) }}" aria-label="Lihat produksi {{ $month['period'] }}">{{ $month['label'] }}</a>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="chart-footnote"><span class="legend-dot legend-production"></span> Hasil produksi (pcs) · pilih bulan untuk rincian</div>
        @else
            <div class="dashboard-empty"><span class="empty-symbol">—</span><p>Belum ada data produksi pada periode ini.</p></div>
        @endif
    </article>
</section>

<section class="dashboard-lower-grid">
    <article class="card dashboard-panel">
        <div class="panel-heading">
            <div><h2>Stok Perlu Diperhatikan</h2><p>Bahan dan produk yang mencapai batas minimum</p></div>
            <div class="panel-heading-actions"><span class="panel-count">{{ $attentionStock->count() }}</span><a class="text-action" href="{{ route('admin.stock.index') }}">Semua stok</a></div>
        </div>
        @if($attentionStock->isNotEmpty())
            <div class="attention-list">
                @foreach($attentionStock as $item)
                    <a class="attention-row" href="{{ $item['url'] }}">
                        <span class="stock-icon {{ $item['status'] === 'Habis' ? 'is-out' : 'is-low' }}"><x-icon :name="$item['icon']" size="18" /></span>
                        <div class="attention-name"><strong>{{ $item['name'] }}</strong><small>{{ $item['kind'] }}</small></div>
                        <span class="attention-quantity">{{ number_format($item['quantity'], 3, ',', '.') }} {{ $item['unit'] }}</span>
                        <span class="status-pill {{ $item['status'] === 'Habis' ? 'status-danger' : 'status-warning' }}">{{ $item['status'] }}</span>
                    </a>
                @endforeach
            </div>
        @else
            <div class="dashboard-empty dashboard-empty-compact">
                <span class="{{ $untrackedProductStock > 0 ? 'empty-symbol' : 'empty-check' }}">{{ $untrackedProductStock > 0 ? '—' : '✓' }}</span>
                @if($untrackedProductStock > 0)
                    <p>Belum ada stok yang melewati batas. Stok {{ number_format($untrackedProductStock, 0, ',', '.') }} produk belum dicatat.</p>
                @else
                    <p>Tidak ada stok yang perlu diperhatikan.</p>
                @endif
            </div>
        @endif
    </article>

    <article class="card dashboard-panel">
        <div class="panel-heading">
            <div><h2>Aktivitas Terbaru</h2><p>5 transaksi terbaru · semua periode</p></div>
            <x-icon name="receipt" size="18" />
        </div>
        @if($activities->isNotEmpty())
            <div class="activity-list">
                @foreach($activities as $activity)
                    <a class="activity-row" href="{{ $activity['url'] }}">
                        <span class="semantic-icon semantic-{{ match($activity['type']) { 'Penjualan' => 'sales', 'Produksi' => 'productions', default => 'raw-materials' } }}"><x-icon :name="match($activity['type']) { 'Penjualan' => 'receipt', 'Produksi' => 'factory', default => 'cart' }" size="18" /></span>
                        <div class="activity-detail">
                            <div><strong>{{ $activity['type'] }}</strong><span class="status-pill status-{{ $activity['status'] }}">{{ match($activity['status']) { 'confirmed' => 'Terkonfirmasi', 'cancelled' => 'Dibatalkan', default => 'Draft' } }}</span></div>
                            <span>{{ $activity['reference'] }} · {{ $activity['description'] }}</span>
                            <time datetime="{{ $activity['date']->toIso8601String() }}">{{ $activity['date']->locale('id')->translatedFormat('d M Y') }}</time>
                        </div><x-icon name="chevron-right" size="16" />
                    </a>
                @endforeach
            </div>
        @else
            <div class="dashboard-empty dashboard-empty-compact"><span class="empty-symbol">—</span><p>Belum ada aktivitas transaksi.</p></div>
        @endif
        <details class="activity-destinations">
            <summary>Lihat Semua Aktivitas <x-icon name="chevron-down" size="15" /></summary>
            <nav aria-label="Riwayat transaksi">
                <a href="{{ route('admin.sales.index') }}"><x-icon name="receipt" size="16" /> Penjualan</a>
                <a href="{{ route('admin.purchases.index') }}"><x-icon name="cart" size="16" /> Pembelian</a>
                <a href="{{ route('admin.productions.index') }}"><x-icon name="factory" size="16" /> Produksi</a>
            </nav>
        </details>
    </article>
</section>
@endsection
