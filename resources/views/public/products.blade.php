@extends('layouts.app')

@section('title', 'Katalog Produk - Pelangi Lollycandy')

@section('content')
<section class="section catalog-page" aria-labelledby="catalog-title">
    <div class="container">
        <div class="catalog-intro">
            <p class="eyebrow">Katalog Pelangi Lollycandy</p>
            <h1 id="catalog-title">Temukan permen favoritmu.</h1>
            <p>Dari lolipop warna-warni sampai pilihan manis untuk hadiah dan hampers.</p>
        </div>
        <form method="GET" action="{{ route('products.index') }}" data-filter-form class="catalog-filter" role="search">
            <div class="filter-field filter-search"><label for="catalog-search">Cari produk</label><div class="filter-input-wrap"><x-icon name="search" size="19" /><input id="catalog-search" name="q" type="search" value="{{ request('q') }}" placeholder="Cari nama permen…"></div></div>
            <div class="filter-field"><label for="catalog-category">Kategori</label><select id="catalog-category" name="category"><option value="">Semua kategori</option>@foreach($categories as $cat)<option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>{{ $cat->name }}</option>@endforeach</select></div>
            <div class="filter-field"><label for="catalog-sort">Urutkan</label><select id="catalog-sort" name="sort"><option value="latest" @selected($sort === 'latest')>Terbaru</option><option value="cheapest" @selected($sort === 'cheapest')>Harga termurah</option><option value="highest" @selected($sort === 'highest')>Harga tertinggi</option></select></div>
            <button class="btn btn-primary filter-submit" type="submit">Cari</button>
        </form>
        <div class="catalog-feedback" data-catalog-feedback role="status" aria-live="polite" hidden></div>
        <div class="catalog-results-line" aria-live="polite"><p>{{ $products->total() }} produk ditemukan</p>@if(request()->filled('q') || request()->filled('category'))<a href="{{ route('products.index') }}">Bersihkan filter</a>@endif</div>
        <div class="grid products-grid catalog-products-grid" aria-busy="false">@forelse($products as $product)@include('public.partials.product-card', ['product' => $product, 'headingLevel' => 2])@empty<div class="catalog-empty"><x-icon name="search" size="30" /><h2>Produk belum ditemukan</h2><p>Coba kata pencarian atau kategori lainnya.</p><a href="{{ route('products.index') }}">Lihat semua produk</a></div>@endforelse</div>
        <div class="catalog-pagination">{{ $products->links() }}</div>
    </div>
</section>
@endsection
