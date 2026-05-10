@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('content')
<div class="admin-page-head">
    <h1>Dashboard</h1>
    <p>Halaman Utama Admin.</p>
</div>

<div class="admin-kpi-grid">
    <div class="card"><strong>Total Produk</strong><h2>{{ $stats['products'] }}</h2></div>
    <div class="card"><strong>Total Kategori</strong><h2>{{ $stats['categories'] }}</h2></div>
    <div class="card"><strong>Total Clicked Products</strong><h2>{{ $stats['clicks'] }}</h2></div>
</div>

<div class="card admin-section-card">
    <h3>Most Clicked Products</h3>
    <div class="admin-click-list">
        @forelse($mostClicked as $item)
            <div class="admin-click-item">
                <span>{{ $item->name }}</span>
                <strong>{{ number_format($item->click_count, 0, ',', '.') }} klik</strong>
            </div>
        @empty
            <p class="text-muted admin-empty-state">Belum ada aktivitas kunjungan produk.</p>
        @endforelse
    </div>
</div>
@endsection
