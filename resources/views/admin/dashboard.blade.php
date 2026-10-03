@extends('layouts.admin')

@section('title', 'Dashboard Operasional')

@section('content')
<div class="admin-page-head business-page-head">
    <div>
        <div class="admin-breadcrumb"><span>Pelangi Admin</span><span aria-hidden="true">/</span><strong>Ringkasan</strong></div>
        <h1>Dashboard</h1>
        <p>Ringkasan kondisi operasional Pelangi Lollycandy.</p>
    </div>
    <div class="period-chip"><span class="period-dot"></span>{{ $periodLabel }}</div>
</div>

<section class="admin-kpi-grid business-kpi-grid" aria-label="Ringkasan usaha">
    <article class="card business-kpi-card">
        <span class="kpi-label">Total Produk</span>
        <strong class="kpi-value">{{ number_format($stats['products'], 0, ',', '.') }}</strong>
        <span class="kpi-note">Produk aktif</span>
    </article>
    <article class="card business-kpi-card">
        <span class="kpi-label">Bahan Baku</span>
        <strong class="kpi-value">{{ number_format($stats['rawMaterials'], 0, ',', '.') }}</strong>
        <span class="kpi-note">Bahan aktif</span>
    </article>
    <article class="card business-kpi-card">
        <span class="kpi-label">Supplier</span>
        <strong class="kpi-value">{{ number_format($stats['suppliers'], 0, ',', '.') }}</strong>
        <span class="kpi-note">Supplier aktif</span>
    </article>
    <article class="card business-kpi-card">
        <span class="kpi-label">Pelanggan</span>
        <strong class="kpi-value">{{ number_format($stats['customers'], 0, ',', '.') }}</strong>
        <span class="kpi-note">Tercatat</span>
    </article>
    <article class="card business-kpi-card business-kpi-primary">
        <span class="kpi-label">Penjualan Bulan Ini</span>
        <strong class="kpi-value kpi-currency">Rp{{ number_format($stats['salesAmount'], 0, ',', '.') }}</strong>
        <span class="kpi-note">{{ number_format($stats['salesCount'], 0, ',', '.') }} transaksi terkonfirmasi</span>
    </article>
    <article class="card business-kpi-card">
        <span class="kpi-label">Produksi Bulan Ini</span>
        <strong class="kpi-value">{{ number_format($stats['productionQuantity'], 0, ',', '.') }}</strong>
        <span class="kpi-note">{{ number_format($stats['productionCount'], 0, ',', '.') }} batch terkonfirmasi</span>
    </article>
</section>

<section class="dashboard-chart-grid" aria-label="Tren operasional enam bulan">
    <article class="card dashboard-panel chart-panel">
        <div class="panel-heading">
            <div><h2>Penjualan 6 Bulan Terakhir</h2><p>Nilai transaksi yang telah dikonfirmasi</p></div>
            <span class="panel-mark panel-mark-pink" aria-hidden="true"></span>
        </div>
        @if($months->sum('sales') > 0)
            <div class="mini-chart" role="img" aria-label="Grafik nilai penjualan enam bulan terakhir">
                <div class="chart-grid-lines"><i></i><i></i><i></i></div>
                <div class="chart-columns">
                    @foreach($months as $month)
                        <div class="chart-column">
                            <div class="chart-bar-wrap">
                                <span class="chart-bar chart-bar-sales" style="--bar-height: {{ ($month['sales'] / $salesMax) * 100 }}%" title="Rp{{ number_format($month['sales'], 0, ',', '.') }}"></span>
                            </div>
                            <span class="chart-month">{{ $month['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="chart-footnote"><span class="legend-dot legend-sales"></span> Penjualan</div>
        @else
            <div class="dashboard-empty"><span class="empty-symbol">—</span><p>Belum ada transaksi penjualan pada periode ini.</p></div>
        @endif
    </article>

    <article class="card dashboard-panel chart-panel">
        <div class="panel-heading">
            <div><h2>Produksi 6 Bulan Terakhir</h2><p>Jumlah produk dari batch terkonfirmasi</p></div>
            <span class="panel-mark panel-mark-blue" aria-hidden="true"></span>
        </div>
        @if($months->sum('production') > 0)
            <div class="mini-chart" role="img" aria-label="Grafik jumlah produksi enam bulan terakhir">
                <div class="chart-grid-lines"><i></i><i></i><i></i></div>
                <div class="chart-columns">
                    @foreach($months as $month)
                        <div class="chart-column">
                            <div class="chart-bar-wrap">
                                <span class="chart-bar chart-bar-production" style="--bar-height: {{ ($month['production'] / $productionMax) * 100 }}%" title="{{ number_format($month['production'], 0, ',', '.') }} produk"></span>
                            </div>
                            <span class="chart-month">{{ $month['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="chart-footnote"><span class="legend-dot legend-production"></span> Hasil produksi</div>
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
                    <div class="attention-row">
                        <span class="attention-status {{ $item['status'] === 'Habis' ? 'is-out' : 'is-low' }}" aria-hidden="true"></span>
                        <div class="attention-name"><strong>{{ $item['name'] }}</strong><small>{{ $item['kind'] }}</small></div>
                        <span class="attention-quantity">{{ number_format($item['quantity'], 3, ',', '.') }} {{ $item['unit'] }}</span>
                        <span class="status-pill {{ $item['status'] === 'Habis' ? 'status-danger' : 'status-warning' }}">{{ $item['status'] }}</span>
                    </div>
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
            <div><h2>Aktivitas Terbaru</h2><p>Pembelian, produksi, dan penjualan</p></div>
            <span class="activity-mark" aria-hidden="true">•••</span>
        </div>
        @if($activities->isNotEmpty())
            <div class="activity-list">
                @foreach($activities as $activity)
                    <div class="activity-row">
                        <div class="activity-detail">
                            <div><strong>{{ $activity['type'] }}</strong><span class="status-pill status-{{ $activity['status'] }}">{{ match($activity['status']) { 'confirmed' => 'Terkonfirmasi', 'cancelled' => 'Dibatalkan', default => 'Draft' } }}</span></div>
                            <span><a href="{{ $activity['url'] }}">{{ $activity['reference'] }}</a> · {{ $activity['description'] }}</span>
                            <time datetime="{{ $activity['date']->toIso8601String() }}">{{ $activity['date']->format('d M Y, H:i') }}</time>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="dashboard-empty dashboard-empty-compact"><span class="empty-symbol">—</span><p>Belum ada aktivitas transaksi.</p></div>
        @endif
    </article>
</section>
@endsection
