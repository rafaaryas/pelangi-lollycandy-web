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
        <h3>Admin Panel</h3>
        <a href="{{ route('admin.dashboard') }}">Dashboard</a><br>
        <a href="{{ route('admin.products.index') }}">Produk</a><br>
        <a href="{{ route('admin.categories.index') }}">Kategori</a><br>
        <a href="{{ route('admin.inquiries.index') }}">Inquiry</a><br>
        <a href="{{ route('admin.settings.index') }}">SEO</a><br>
    </aside>
    <main class="admin-main">
        @if(session('success'))<div class="card" data-toast>{{ session('success') }}</div>@endif
        @yield('content')
    </main>
</div>
</body>
</html>
