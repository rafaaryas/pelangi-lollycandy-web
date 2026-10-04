<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Pelangi Lollycandy')</title>
    @include('partials.favicons')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-page {{ request()->routeIs('home') ? 'home-page' : '' }} {{ request()->routeIs('contact.*') ? 'contact-page' : '' }}">
    <div class="route-progress" data-route-progress aria-hidden="true"><span></span></div>
    <header class="navbar" data-navbar>
        <div class="container navbar-inner">
            <a href="{{ route('home') }}" class="brand-mark" aria-label="Pelangi Lollycandy, halaman utama">
                <img src="{{ asset('images/pelangi-logo.png') }}" alt="Pelangi Lollycandy">
            </a>
            <button class="btn nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="site-menu" aria-label="Buka menu navigasi" hidden>
                <x-icon name="menu" size="20" />
                <span>Menu</span>
            </button>
            <nav id="site-menu" data-nav-menu class="nav-menu" aria-label="Navigasi utama">
                <a class="nav-link {{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}" data-scroll-home @if(request()->routeIs('home')) aria-current="page" @endif><x-icon name="house" size="16" /><span>HOME</span></a>
                <a class="nav-link" href="{{ route('home') }}#about" data-about-link><x-icon name="users" size="16" /><span>ABOUT US</span></a>
                <a class="nav-link {{ request()->routeIs('products.*') ? 'is-active' : '' }}" href="{{ route('products.index') }}" @if(request()->routeIs('products.*')) aria-current="page" @endif><x-icon name="package" size="16" /><span>PRODUCTS</span></a>
                <a class="nav-link" href="{{ route('home') }}#marketplace-hub"><x-icon name="store" size="16" /><span>MARKETPLACE</span></a>
                <a class="nav-link {{ request()->routeIs('contact.*') ? 'is-active' : '' }}" href="{{ route('contact.index') }}" @if(request()->routeIs('contact.*')) aria-current="page" @endif><x-icon name="mail" size="16" /><span>CONTACT US</span></a>
            </nav>
        </div>
    </header>
    @if(session('success'))
        <div class="container" data-toast><div class="card">{{ session('success') }}</div></div>
    @endif
    <main id="main-content">
        @yield('content')
    </main>
    <a class="floating-wa" href="https://wa.me/6285184005430" target="_blank" rel="noopener" aria-label="Chat via WhatsApp" data-tooltip="Chat via WhatsApp">
        <svg viewBox="0 0 32 32" aria-hidden="true" focusable="false">
            <path d="M16.04 3.2c-7.05 0-12.78 5.66-12.78 12.64 0 2.4.69 4.73 1.99 6.75l-1.39 5.21 5.37-1.36a12.9 12.9 0 0 0 6.81 1.95c7.05 0 12.78-5.67 12.78-12.65S23.09 3.2 16.04 3.2Zm0 22.99c-2.22 0-4.28-.65-6.03-1.78l-.43-.27-3.19.81.82-3.08-.29-.46a10.2 10.2 0 0 1-1.48-5.57c0-5.77 4.76-10.46 10.6-10.46s10.6 4.69 10.6 10.46-4.75 10.35-10.6 10.35Zm5.84-7.75c-.32-.16-1.9-.93-2.19-1.04-.29-.1-.5-.16-.72.16-.21.31-.82 1.04-1.01 1.25-.19.21-.37.24-.69.08-.32-.16-1.36-.49-2.59-1.58-.96-.84-1.61-1.89-1.8-2.2-.19-.32-.02-.49.14-.64.15-.15.32-.37.48-.55.16-.19.21-.32.32-.53.11-.21.05-.4-.03-.56-.08-.16-.72-1.71-.98-2.35-.26-.61-.52-.53-.72-.54h-.61c-.21 0-.56.08-.85.4-.29.32-1.12 1.09-1.12 2.65s1.15 3.08 1.31 3.29c.16.21 2.26 3.42 5.47 4.79.77.33 1.36.52 1.83.67.77.24 1.47.21 2.02.13.62-.09 1.9-.77 2.17-1.51.27-.75.27-1.39.19-1.52-.08-.14-.29-.22-.61-.38Z"/>
        </svg>
    </a>
    <footer class="footer">
        <div class="container footer-grid">
            <div class="footer-brand">
                <a href="{{ route('home') }}" aria-label="Pelangi Lollycandy, halaman utama"><img src="{{ asset('images/pelangi-logo.png') }}" alt="Pelangi Lollycandy" width="200" height="65" loading="lazy"></a>
                <p>Permen penuh warna untuk momen yang lebih manis.</p>
            </div>
            <div class="footer-links"><h2>Navigasi</h2><a href="{{ route('home') }}">Home</a><a href="{{ route('home') }}#about">About Us</a><a href="{{ route('products.index') }}">Products</a><a href="{{ route('home') }}#marketplace-hub">Marketplace</a><a href="{{ route('contact.index') }}">Contact</a></div>
            <div class="footer-links"><h2>Belanja &amp; tanya</h2>@if($footerShopeeUrl)<a href="{{ $footerShopeeUrl }}" target="_blank" rel="noopener"><img src="{{ asset('images/marketplace-icons/shopee.svg') }}" alt="" width="18" height="18"> Shopee <x-icon name="external" size="14" /></a>@endif<a href="https://wa.me/6285184005430" target="_blank" rel="noopener">WhatsApp <x-icon name="external" size="14" /></a></div>
        </div>
        <div class="container footer-bottom">&copy; {{ now()->year }} Pelangi Lollycandy</div>
    </footer>
</body>
</html>
