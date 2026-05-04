<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <strong>Pelangi Admin</strong>
            <small>Dashboard Konten</small>
        </div>
        <nav class="admin-nav">
            <a class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
            <a class="{{ request()->routeIs('admin.products.*') ? 'is-active' : '' }}" href="{{ route('admin.products.index') }}">Produk</a>
            <a class="{{ request()->routeIs('admin.categories.*') ? 'is-active' : '' }}" href="{{ route('admin.categories.index') }}">Kategori</a>
            <a class="{{ request()->routeIs('admin.marketplace-links.*') ? 'is-active' : '' }}" href="{{ route('admin.marketplace-links.index') }}">Marketplace</a>
            <a class="{{ request()->routeIs('admin.testimonials.*') ? 'is-active' : '' }}" href="{{ route('admin.testimonials.index') }}">Testimonial</a>
            <a class="{{ request()->routeIs('admin.inquiries.*') ? 'is-active' : '' }}" href="{{ route('admin.inquiries.index') }}">Inquiry</a>
        </nav>
        <form method="POST" action="{{ route('admin.logout') }}" style="margin-top:auto;">
            @csrf
            <button class="btn btn-outline-white" style="width:100%;">Logout</button>
        </form>
    </aside>
    <main class="admin-main">
        @if(session('success'))<div class="card" data-toast>{{ session('success') }}</div>@endif
        @yield('content')
    </main>
</div>
</body>
</html>
