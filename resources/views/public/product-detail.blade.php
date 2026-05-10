@extends('layouts.app')

@section('title', $product->name . ' - Pelangi Lollycandy')

@section('content')
@php
    $productImagePath = $product->images->first()?->storagePath();
    $whatsAppMessage = 'Halo, saya tertarik dengan produk '.$product->name;
    $whatsAppUrl = 'https://wa.me/6285184005430?text='.urlencode($whatsAppMessage);
@endphp
<section class="section">
    <div class="container product-detail-grid">
        <div class="card product-detail-media-card">
            <img class="product-detail-image" src="{{ $productImagePath ? asset('storage/'.$productImagePath) : asset(\App\Models\ProductImage::PLACEHOLDER) }}" alt="{{ $product->name }}">
        </div>
        <div class="card product-detail-card">
            <h1>{{ $product->name }}</h1>
            <p>{{ $product->description }}</p>
            <p class="product-detail-price">Mulai dari <strong>Rp {{ number_format($product->price_from, 0, ',', '.') }}</strong></p>
            <div class="product-detail-actions">
                <a class="btn btn-primary" href="{{ $product->shopee_url ?: '#' }}" target="_blank" rel="noopener">Shopee</a>
                <a class="btn btn-whatsapp" href="{{ $whatsAppUrl }}" target="_blank" rel="noopener">WhatsApp</a>
            </div>
        </div>
    </div>
</section>
@endsection
