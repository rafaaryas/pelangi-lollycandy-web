@extends('layouts.app')

@section('title', 'Home - Pelangi Lollycandy')

@section('content')
<section id="home-hero" class="hero hero-like-reference" aria-labelledby="home-title">
    <div class="container hero-grid hero-grid-ref">
        <div class="hero-illustration">
            <img src="{{ asset('images/pelangi-hero-candy.png') }}" alt="Colorful rainbow lollipops and candy" class="hero-lollipop" width="3168" height="1344" fetchpriority="high">
        </div>
        <div class="hero-copy">
            <h1 id="home-title">PELANGI<br>LOLLYCANDY</h1>
            <div class="hero-actions">
                <a href="{{ route('products.index') }}" class="btn btn-primary hero-catalog-btn"><span>Lihat Katalog</span><x-icon name="arrow-right" size="17" /></a>
            </div>
        </div>
    </div>
    <div class="wave-bottom"></div>
</section>

<section id="about" class="section home-about">
    <div class="container">
        <div class="section-head">
            <h2>About Us</h2>
            <p>Permen penuh warna dengan bentuk menarik dan pilihan yang cocok untuk camilan, hadiah, maupun acara spesial.</p>
        </div>
        <div class="about-grid">
            <article class="card about-card about-card-media">
                {{-- Image wrapper keeps any uploaded/replaced image cropped neatly inside the card. --}}
                <div class="about-card-image">
                    <img src="{{ asset('images/kualitas.jpg') }}" alt="Proses pembuatan permen Pelangi Lollycandy" loading="lazy" width="560" height="350">
                </div>
                <div class="about-card-content">
                    <h3>Kualitas Terjaga</h3>
                    <p>Pelangi Lollycandy dibuat dari bahan berkualitas dengan proses produksi yang higienis untuk menjaga rasa, warna, dan kualitas permen tetap konsisten.</p>
                </div>
            </article>
            <article class="card about-card about-card-media">
                <div class="about-card-image">
                    <img src="{{ asset('images/ceria.jpg') }}" alt="Beragam warna dan bentuk permen" loading="lazy" width="560" height="350">
                </div>
                <div class="about-card-content">
                    <h3>Varian Ceria</h3>
                    <p>Tersedia dalam berbagai bentuk, warna, dan rasa menarik untuk dinikmati sendiri atau dibagikan di acara spesial dan hampers.</p>
                </div>
            </article>
            <article class="card about-card about-card-media">
                <div class="about-card-image">
                    <img src="{{ asset('images/pasar.jpg') }}" alt="Pilihan permen siap dipasarkan" loading="lazy" width="560" height="350">
                </div>
                <div class="about-card-content">
                    <h3>Siap Dipasarkan</h3>
                    <p>Mendukung pembelian retail, grosir, hingga kebutuhan acara dengan pilihan kemasan yang siap dijual kembali atau dijadikan hadiah.</p>
                </div>
            </article>
        </div>
    </div>
</section>

<section id="featured-products" class="section home-bestseller">
    <div class="container">
        <div class="section-head section-head-inline">
            <div><h2>Pilihan Manis Kami</h2><p>Kenalan dengan beberapa produk dari Pelangi Lollycandy.</p></div>
            <a class="text-link" href="{{ route('products.index') }}">Lihat Semua <x-icon name="arrow-right" size="17" /></a>
        </div>
        <div class="grid products-grid">
            @foreach($featuredProducts as $product)
                @include('public.partials.product-card', ['product' => $product])
            @endforeach
        </div>
    </div>
</section>

<section id="marketplace-hub" class="section home-marketplace">
    <div class="container">
        <div class="section-head">
            <h2>Temukan Kami di Marketplace</h2>
            <p>Pilih cara paling nyaman untuk belanja atau menghubungi kami.</p>
        </div>
        <div class="marketplace-grid">
            @forelse($marketplaces as $marketplace)
                <a class="marketplace-card" href="{{ $marketplace->url }}" target="_blank" rel="noopener">
                    <span class="marketplace-icon-wrap">
                        <img src="{{ asset($marketplace->iconPath()) }}" alt="" aria-hidden="true" width="28" height="28">
                    </span>
                    <span class="marketplace-copy"><strong>{{ $marketplace->platform }}</strong><small>{{ str_contains(strtolower($marketplace->platform), 'whatsapp') ? 'Tanya produk atau pesan langsung' : 'Lihat produk di '.$marketplace->platform }}</small></span>
                    <span class="marketplace-visit"><x-icon name="arrow-right" size="18" /></span>
                </a>
            @empty
                <div class="card marketplace-empty">Link marketplace belum tersedia.</div>
            @endforelse
        </div>
    </div>
</section>

<section id="contact-cta" class="section home-contact-cta">
    <div class="container">
        <div class="cta-card">
            <div><h2>Punya rencana manis bersama kami?</h2>
            <p>Untuk grosir, reseller, hampers, event, atau kolaborasi, ceritakan kebutuhanmu kepada tim Pelangi Lollycandy.</p></div>
            <div class="cta-actions">
                <a class="btn btn-primary" href="{{ route('contact.index') }}">Hubungi Kami <x-icon name="arrow-right" size="17" /></a>
            </div>
        </div>
    </div>
</section>
@endsection
