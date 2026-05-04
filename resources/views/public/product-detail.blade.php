@extends('layouts.app')

@section('title', $product->name . ' - Pelangi Lollycandy')

@section('content')
<section class="section">
    <div class="container" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="card">
            <img class="product-image" src="{{ $product->images->first() ? asset('storage/'.$product->images->first()->image_path) : 'https://placehold.co/420x420?text=Candy' }}" alt="{{ $product->name }}">
        </div>
        <div class="card">
            <h1>{{ $product->name }}</h1>
            <p>{{ $product->description }}</p>
            <p>Mulai dari <strong>Rp {{ number_format($product->price_from, 0, ',', '.') }}</strong></p>
            <div style="display:flex;gap:.7rem;flex-wrap:wrap">
                <a class="btn btn-primary" href="{{ $product->shopee_url ?: '#' }}">Shopee</a>
                <a class="btn btn-outline" href="#">WhatsApp</a>
                <a class="btn btn-outline" href="#">Instagram</a>
            </div>
        </div>
    </div>
</section>
@endsection
