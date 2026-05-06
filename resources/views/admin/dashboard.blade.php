@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('content')
<div class="admin-page-head">
    <h1>Dashboard</h1>
    <p>Ringkasan cepat performa katalog Pelangi Lollycandy.</p>
</div>

<div class="admin-kpi-grid">
    <div class="card"><strong>Total Produk</strong><h2>{{ $stats['products'] }}</h2></div>
    <div class="card"><strong>Total Kategori</strong><h2>{{ $stats['categories'] }}</h2></div>
    <div class="card"><strong>Total Clicked Products</strong><h2>{{ $stats['clicks'] }}</h2></div>
</div>

<div class="card admin-section-card">
    <h3>Most Clicked Products</h3>
    @forelse($mostClicked as $item)
        <p>{{ $item->name }} <span class="text-muted">({{ $item->favorite_clicks }} klik)</span></p>
    @empty
        <p class="text-muted">Belum ada data klik produk.</p>
    @endforelse
</div>
@endsection
