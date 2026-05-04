@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('content')
<h1>Dashboard</h1>
<div class="grid" style="grid-template-columns:repeat(4,minmax(120px,1fr));">
    <div class="card">Produk: {{ $stats['products'] }}</div>
    <div class="card">Scheduled: {{ $stats['scheduled'] }}</div>
    <div class="card">Inquiry: {{ $stats['inquiries'] }}</div>
    <div class="card">Clicks: {{ $stats['clicks'] }}</div>
</div>
<h2>Produk Paling Diklik</h2>
<div class="card">
    @foreach($mostClicked as $item)
        <p>{{ $item->name }} ({{ $item->favorite_clicks }} klik)</p>
    @endforeach
</div>
@endsection
