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
        <div class="container navbar-inner" style="justify-content:space-between;">
            <a href="{{ route('home') }}" class="brand-mark" style="margin-right: auto;">
                <img src="https://i.ibb.co.com/My9JrCNT/loggghh.png" alt="Pelangi Lollycandy" style="height: 65px; width: auto;">
            </a>
            <nav data-nav-menu class="nav-menu">
                <a class="btn nav-secondary {{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}">HOME</a>
                <a class="btn nav-secondary" href="{{ route('home') }}#about-brand">ABOUT US</a>
                <a class="btn nav-secondary {{ request()->routeIs('products.*') ? 'is-active' : '' }}" href="{{ route('products.index') }}">PRODUCTS</a>
                <a class="btn nav-secondary {{ request()->routeIs('contact.*') ? 'is-active' : '' }}" href="{{ route('contact.index') }}">CONTACT US</a>
                <a class="btn nav-secondary" href="{{ route('home') }}#marketplace-hub">MARKETPLACE</a>
            </nav>
        </div>
    </header>
    @if(session('success'))
        <div class="container" data-toast><div class="card">{{ session('success') }}</div></div>
    @endif
    @yield('content')
    <a class="floating-wa btn btn-primary" href="#">WhatsApp</a>
    <footer class="footer">© {{ now()->year }} Pelangi Lollycandy. All rights reserved.</footer>
</body>
</html>
