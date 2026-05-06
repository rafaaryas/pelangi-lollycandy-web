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

        <div class="grid products-grid" style="margin-top:1rem">
            @forelse($products as $product)
            <article class="card">
                <img loading="lazy" class="product-image" src="{{ $product->images->first() ? asset('storage/'.$product->images->first()->image_path) : 'https://placehold.co/420x420?text=Candy' }}" alt="{{ $product->name }}">
                @if($product->badge === 'new') <span class="badge badge-new">Baru</span> @endif
                @if($product->badge === 'best_seller') <span class="badge badge-best">Best Seller</span> @endif
                <h3>{{ $product->name }}</h3>
                <p>{{ $product->description }}</p>
                <p>{{ $product->category->name }}</p>
                <p>Size: {{ $product->variants->pluck('size')->filter()->unique()->join(', ') ?: '-' }}</p>
                <p>Mulai Rp {{ number_format($product->price_from, 0, ',', '.') }}</p>
                <a class="btn btn-outline" href="{{ route('products.show', $product->slug) }}">Detail</a>
            </article>
            @empty
            <div class="card">Produk tidak ditemukan.</div>
            @endforelse
        </div>
        <div style="margin-top:1rem">{{ $products->links() }}</div>
    </div>
</section>
@endsection
