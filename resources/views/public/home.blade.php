@extends('layouts.app')

@section('title', 'Home - Pelangi Lollycandy')

@section('content')
<section class="hero hero-like-reference">
    <div class="container hero-grid hero-grid-ref">
        <div class="hero-illustration">
            <img src="{{ asset('images/lollipop-banner.png') }}" alt="Lollipop Illustration" class="hero-lollipop">
        </div>
        <div class="hero-copy">
            <h1>PELANGI<br>LOLLYCANDY</h1>
            <p>{{ $hero->subtitle ?? 'Permen colorful premium untuk anak muda & keluarga.' }}</p>
            <div class="hero-actions">
                <a href="{{ route('products.index') }}" class="btn btn-outline-white hero-catalog-btn">Lihat Katalog</a>
            </div>
            <div class="stats">
                <div class="stat">{{ $stats['products'] }} Produk</div>
                <div class="stat">{{ $stats['variants'] }} Varian</div>
            </div>
        </div>
    </div>
    <div class="wave-bottom"></div>
</section>

<section id="about-brand" class="section home-about">
    <div class="container">
        <div class="section-head">
            <h2>About Brand Preview</h2>
            <p>Pelangi Lollycandy menghadirkan permen premium colorful dengan rasa konsisten, visual playful, dan kualitas produksi yang dipercaya keluarga.</p>
        </div>
        <div class="about-grid">
            <article class="card about-card">
                <div class="icon-placeholder"></div>
                <h3>Kualitas Premium</h3>
                <p>Bahan berkualitas, rasa stabil, dan tampilan menarik untuk hadiah maupun snack harian.</p>
            </article>
            <article class="card about-card">
                <div class="icon-placeholder"></div>
                <h3>Family Friendly</h3>
                <p>Varian cocok untuk anak muda dan keluarga, dengan kemasan modern yang mudah dibagikan.</p>
            </article>
            <article class="card about-card">
                <div class="icon-placeholder"></div>
                <h3>Mudah Ditemukan</h3>
                <p>Terhubung ke marketplace utama supaya pembelian lebih cepat dan praktis di seluruh Indonesia.</p>
            </article>
        </div>
    </div>
</section>

<section class="section home-bestseller">
    <div class="container">
        <div class="section-head section-head-inline">
            <h2>Best Seller Products</h2>
            <a class="btn btn-secondary" href="{{ route('products.index') }}">Lihat Semua</a>
        </div>
        <div class="grid products-grid">
            @foreach($featuredProducts as $product)
            <article class="card product-card-refined">
                <img loading="lazy" class="product-image" src="{{ $product->images->first() ? asset('storage/'.$product->images->first()->image_path) : 'https://placehold.co/420x420?text=Candy' }}" alt="{{ $product->name }}">
                <h3>{{ $product->name }}</h3>
                <p class="price-tag">Rp {{ number_format($product->price_from, 0, ',', '.') }}</p>
                <a class="btn btn-outline" href="{{ route('products.show', $product->slug) }}">Lihat Detail</a>
            </article>
            @endforeach
        </div>
    </div>
</section>

<section id="marketplace-hub" class="section home-marketplace">
    <div class="container">
        <div class="section-head">
            <h2>Temukan Kami di Marketplace</h2>
            <p>Klik platform favoritmu untuk melihat katalog Pelangi Lollycandy.</p>
        </div>
        <div class="marketplace-grid">
            @forelse($marketplaces as $marketplace)
                <a class="card marketplace-card" href="{{ $marketplace->url }}" target="_blank" rel="noopener">
                    <strong>{{ $marketplace->label ?? $marketplace->platform }}</strong>
                    <span>Kunjungi</span>
                </a>
            @empty
                <div class="card">Link marketplace belum tersedia.</div>
            @endforelse
        </div>
    </div>
</section>

<section class="section home-testimonials">
    <div class="container">
        <div class="section-head">
            <h2>Testimonial</h2>
        </div>
        <div class="about-grid">
            @forelse($testimonials as $testimonial)
                <article class="card testimonial-card">
                    <p>"{{ $testimonial->quote }}"</p>
                    <strong>{{ $testimonial->name }}</strong>
                    <small>{{ $testimonial->role }}</small>
                </article>
            @empty
                <article class="card testimonial-card">
                    <p>"Permen favorit keluarga, rasanya konsisten dan tampilannya menarik."</p>
                    <strong>Konsumen Pelangi</strong>
                    <small>Jakarta</small>
                </article>
                <article class="card testimonial-card">
                    <p>"Anak-anak suka, kemasannya juga premium untuk hampers."</p>
                    <strong>Reseller</strong>
                    <small>Bandung</small>
                </article>
                <article class="card testimonial-card">
                    <p>"Fast moving product untuk toko oleh-oleh dan gift set."</p>
                    <strong>Mitra Toko</strong>
                    <small>Surabaya</small>
                </article>
            @endforelse
        </div>
    </div>
</section>

<section class="section home-contact-cta">
    <div class="container">
        <div class="card cta-card">
            <h2>Siap Kolaborasi dengan Pelangi Lollycandy?</h2>
            <p>Hubungi tim kami untuk kebutuhan grosir, reseller, event, atau kolaborasi brand.</p>
            <div style="display:flex;gap:.8rem;flex-wrap:wrap;justify-content:center;">
                <a class="btn btn-primary" href="{{ route('contact.index') }}">Hubungi Kami</a>
                <a class="btn btn-secondary" href="#marketplace-hub">Lihat Marketplace</a>
            </div>
        </div>
    </div>
</section>
@endsection
