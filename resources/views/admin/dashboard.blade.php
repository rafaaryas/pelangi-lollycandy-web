@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('content')
<div class="admin-page-head">
    <h1>Dashboard</h1>
    <p>Ringkasan cepat performa katalog Pelangi Lollycandy.</p>
</div>

<div class="admin-kpi-grid">
    <div class="card"><strong>Total Produk</strong><h2>{{ $stats['products'] }}</h2></div>
    <div class="card"><strong>Total Inquiry</strong><h2>{{ $stats['inquiries'] }}</h2></div>
</div>

<div class="card" style="margin-top:1rem;">
    <h3>Most Clicked Products</h3>
    @forelse($mostClicked as $item)
        <p>{{ $item->name }} <span class="text-muted">({{ $item->favorite_clicks }} klik)</span></p>
    @empty
        <p class="text-muted">Belum ada data klik produk.</p>
    @endforelse
</div>
@endsection
