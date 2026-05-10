@extends('layouts.app')

@section('title', 'Catalog - Pelangi Lollycandy')

@section('content')
<section class="section">
    <div class="container">
        <h1>Katalog Produk</h1>
        <form method="GET" data-filter-form class="card catalog-filter">
            <input name="q" value="{{ request('q') }}" placeholder="Cari produk...">
            <select name="category">
                <option value="">Semua kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
            <select name="sort">
                <option value="latest" @selected($sort === 'latest')>Terbaru</option>
                <option value="cheapest" @selected($sort === 'cheapest')>Harga termurah</option>
                <option value="highest" @selected($sort === 'highest')>Harga tertinggi</option>
                <option value="popular" @selected($sort === 'popular')>Populer</option>
            </select>
        </form>

        <div class="grid products-grid catalog-products-grid">
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
