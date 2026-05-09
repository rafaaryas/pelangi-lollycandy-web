@extends('layouts.app')

@section('title', 'Catalog - Pelangi Lollycandy')

@section('content')
<section class="section">
    <div class="container">
        <h1>Katalog Produk</h1>
        <form method="GET" data-filter-form class="card" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.8rem;">
            <input name="q" value="{{ request('q') }}" placeholder="Cari produk...">
            <select name="category">
                <option value="">Semua kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
            <input name="flavor" value="{{ request('flavor') }}" placeholder="Rasa">
            <select name="sort">
                <option value="latest">Terbaru</option>
                <option value="cheapest" @selected(request('sort')==='cheapest')>Termurah</option>
                <option value="popular" @selected(request('sort')==='popular')>Populer</option>
            </select>
        </form>

        {{-- Product cards stay compact: image, name, price, and CTA only. Longer details remain on the detail page. --}}
        <div class="grid products-grid" style="margin-top:1rem">
            @forelse($products as $product)
            @php($productImagePath = $product->images->first()?->storagePath())
            <article class="card product-card">
                <div class="product-media">
                    <img loading="lazy" class="product-image" src="{{ $productImagePath ? asset('storage/'.$productImagePath) : asset(\App\Models\ProductImage::PLACEHOLDER) }}" alt="{{ $product->name }}">
                    @if($product->badge === 'new') <span class="product-badge">NEW</span> @endif
                    @if($product->badge === 'best_seller') <span class="product-badge">BEST SELLER</span> @endif
                </div>
                <div class="product-card-body">
                    <h3>{{ $product->name }}</h3>
                    <p class="product-price">Rp {{ number_format($product->price_from, 0, ',', '.') }}</p>
                    <a class="btn product-cta" href="{{ route('products.show', $product->slug) }}">Lihat Produk</a>
                </div>
            </article>
            @empty
            <div class="card">Produk tidak ditemukan.</div>
            @endforelse
        </div>
        <div style="margin-top:1rem">{{ $products->links() }}</div>
    </div>
</section>
@endsection
