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
            <p>Permen colorful premium untuk anak muda & keluarga.</p>
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

{{-- ABOUT US SECTION: edit placeholder cards/images here when brand content is ready. --}}
<section id="about-brand" class="section home-about">
    <div class="container">
        <div class="section-head">
            <h2>About Us</h2>
            <p>Pelangi Lollycandy menghadirkan permen colorful dengan bentuk menarik, kemasan ceria, dan varian yang mudah disukai keluarga.</p>
        </div>
        <div class="about-grid">
            <article class="card about-card about-card-media">
                {{-- Image wrapper keeps any uploaded/replaced image cropped neatly inside the card. --}}
                <div class="about-card-image">
                    <img src="https://placehold.co/640x420?text=Produksi+Permen" alt="Placeholder produksi permen">
                </div>
                <div class="about-card-content">
                    <h3>Kualitas Terjaga</h3>
                    <p>Placeholder: tulis cerita singkat tentang bahan, proses produksi, dan standar kualitas brand di sini.</p>
                </div>
            </article>
            <article class="card about-card about-card-media">
                <div class="about-card-image">
                    <img src="{{ asset('images/ceria.jpg') }}" alt="Placeholder varian permen">
                </div>
                <div class="about-card-content">
                    <h3>Varian Ceria</h3>
                    <p>Placeholder: jelaskan pilihan ukuran, bentuk, dan warna yang membuat produk mudah dipilih pelanggan.</p>
                </div>
            </article>
            <article class="card about-card about-card-media">
                <div class="about-card-image">
                    <img src="https://placehold.co/640x420?text=Kemasan+Produk" alt="Placeholder kemasan produk">
                </div>
                <div class="about-card-content">
                    <h3>Siap Dipasarkan</h3>
                    <p>Placeholder: tambahkan informasi reseller, grosir, event, atau kebutuhan custom produk di sini.</p>
                </div>
            </article>
        </div>
    </div>
</section>

{{-- PRODUCT SECTION: data below comes from Admin > Produk and its variants. --}}
<section class="section home-bestseller">
    <div class="container">
        <div class="section-head section-head-inline">
            <h2>Best Seller Products</h2>
            <a class="btn btn-secondary" href="{{ route('products.index') }}">Lihat Semua</a>
        </div>
        {{-- Product preview cards mirror the catalog cards: compact ecommerce hierarchy with details kept behind the CTA. --}}
        <div class="grid products-grid">
            @foreach($featuredProducts as $product)
            @php($productImagePath = $product->images->first()?->storagePath())
            <article class="card product-card product-card-refined">
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
