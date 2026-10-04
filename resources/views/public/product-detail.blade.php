@extends('layouts.app')

@section('title', $product->name . ' - Pelangi Lollycandy')

@section('content')
@php
    $whatsAppMessage = 'Halo, saya tertarik dengan produk '.$product->name;
    $whatsAppUrl = $product->whatsapp_url ?: 'https://wa.me/6285184005430?text='.urlencode($whatsAppMessage);
    $gallery = $product->images->filter(fn ($image) => $image->storagePath())->values();
@endphp
<section class="section product-page" aria-labelledby="product-title">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><x-icon name="chevron-right" size="15" /><a href="{{ route('products.index') }}">Produk</a><x-icon name="chevron-right" size="15" /><span aria-current="page">{{ $product->name }}</span></nav>
        <div class="product-detail-grid">
            <div class="product-gallery">
                @php($mainImageUrl = $gallery->isNotEmpty() ? asset('storage/'.$gallery->first()->storagePath()) : asset(\App\Models\ProductImage::PLACEHOLDER))
                <div class="product-gallery-main product-detail-media" data-gallery-media>
                    <img class="product-gallery-backdrop" data-gallery-backdrop src="{{ $mainImageUrl }}" alt="" aria-hidden="true" width="900" height="900">
                    <img class="product-gallery-foreground" data-gallery-main src="{{ $mainImageUrl }}" alt="{{ $product->name }}" width="900" height="900" fetchpriority="high">
                </div>
                @if($gallery->count() > 1)<div class="product-thumbnails" aria-label="Foto produk">@foreach($gallery as $image)<button type="button" class="product-thumbnail {{ $loop->first ? 'is-active' : '' }}" data-gallery-thumb data-image="{{ asset('storage/'.$image->storagePath()) }}" data-alt="{{ $product->name }} — foto {{ $loop->iteration }}" aria-label="Tampilkan foto {{ $loop->iteration }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"><img src="{{ asset('storage/'.$image->storagePath()) }}" alt="" loading="lazy" width="96" height="96"></button>@endforeach</div>@endif
            </div>
            <div class="product-detail-info">
                <p class="eyebrow">{{ $product->category?->name ?? 'Pelangi Lollycandy' }}</p>
                <h1 id="product-title">{{ $product->name }}</h1>
                @if($product->description)<p class="product-description">{{ $product->description }}</p>@endif
                <div class="product-detail-price"><span>Harga mulai dari</span><strong>Rp {{ number_format($product->price_from, 0, ',', '.') }}</strong></div>
                <div class="product-detail-actions">
                    @if($product->shopee_url)<a class="btn btn-shopee" href="{{ $product->shopee_url }}" target="_blank" rel="noopener"><img src="{{ asset('images/marketplace-icons/shopee.svg') }}" alt="" width="20" height="20">Beli di Shopee <x-icon name="external" size="16" /></a>@endif
                    <a class="btn btn-whatsapp" href="{{ $whatsAppUrl }}" target="_blank" rel="noopener"><img src="{{ asset('images/marketplace-icons/whatsapp.svg') }}" alt="" width="20" height="20">Pesan via WhatsApp <x-icon name="arrow-right" size="16" /></a>
                </div>
                <p class="product-help">Ada pertanyaan tentang produk ini? Kami siap bantu lewat WhatsApp.</p>
            </div>
        </div>
    </div>
</section>
@if($relatedProducts->isNotEmpty())<section class="section related-products" aria-labelledby="related-title"><div class="container"><div class="section-head section-head-inline"><div><h2 id="related-title">Produk lainnya yang mungkin kamu suka</h2><p>Jelajahi pilihan lain dari Pelangi Lollycandy.</p></div><a class="text-link" href="{{ route('products.index') }}">Lihat katalog <x-icon name="arrow-right" size="17" /></a></div><div class="grid products-grid">@foreach($relatedProducts as $product)@include('public.partials.product-card', ['product' => $product])@endforeach</div></div></section>@endif
@endsection
