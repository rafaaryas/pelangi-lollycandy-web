<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body">
<div class="admin-topbanner">
    <img src="{{ asset('images/pelangi-logo.png') }}" alt="Pelangi Logo">
</div>
<div class="admin-shell">
    <aside class="admin-sidebar ">
        <div>
                <div class="admin-brand">
                    <strong>Pelangi Admin</strong>
                    <small>Dashboard Konten</small>
                </div>
                <nav class="admin-nav">
                    <a class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
                    <a class="{{ request()->routeIs('admin.categories.*') ? 'is-active' : '' }}" href="{{ route('admin.categories.index') }}">Kategori</a>
                    <a class="{{ request()->routeIs('admin.products.*') ? 'is-active' : '' }}" href="{{ route('admin.products.index') }}">Produk</a>
                    <a class="{{ request()->routeIs('admin.marketplace-links.*') ? 'is-active' : '' }}" href="{{ route('admin.marketplace-links.index') }}">Marketplace</a>
                </nav>
        </div>
        <div class="admin-bottom-actions">
            <a class="btn btn-outline-white admin-home-link" href="{{ route('home') }}">Home</a>
            <form class="admin-logout" method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="btn btn-outline-white">Logout</button>
            </form>
        </div>
    </aside>
    <main class="admin-main">
        @if(session('success'))<div class="admin-toast" data-toast>{{ session('success') }}</div>@endif
        @if($errors->any())
            <div class="admin-alert" role="alert">
                <strong>Data belum bisa disimpan.</strong>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif
        @yield('content')
    </main>
</div>
</body>
</html>
