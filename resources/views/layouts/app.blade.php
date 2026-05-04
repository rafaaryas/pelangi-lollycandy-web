<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Pelangi Lollycandy')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header class="navbar" data-navbar>
        <div class="container navbar-inner">
            <a href="{{ route('home') }}" class="brand-mark" aria-label="Pelangi Lollycandy home">
                <img src="{{ asset('images/pelangi-logo.png') }}" alt="Pelangi Lollycandy">
            </a>
            <nav data-nav-menu class="nav-menu">
                <a class="nav-pill {{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}">HOME</a>
                <a class="nav-pill" href="{{ route('home') }}#about-brand">ABOUT US</a>
                <a class="nav-pill {{ request()->routeIs('products.*') ? 'is-active' : '' }}" href="{{ route('products.index') }}">PRODUCTS</a>
                <a class="nav-pill {{ request()->routeIs('contact.*') ? 'is-active' : '' }}" href="{{ route('contact.index') }}">CONTACT US</a>
                <a class="nav-pill" href="{{ route('home') }}#marketplace-hub">MARKETPLACE</a>
            </nav>
            <div class="nav-right">
                <button data-nav-toggle class="btn btn-outline-white nav-toggle">Menu</button>
            </div>
        </div>
    </header>
    @if(session('success'))
        <div class="container" data-toast><div class="card">{{ session('success') }}</div></div>
    @endif
    @yield('content')
    <a class="floating-wa btn btn-primary" href="#">WhatsApp</a>
    <footer class="footer">&copy; {{ now()->year }} Pelangi Lollycandy. All rights reserved.</footer>
</body>
</html>
