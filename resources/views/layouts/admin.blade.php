<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin')</title>
    @include('partials.favicons')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body">
<div class="admin-topbanner">
    <img src="{{ asset('images/pelangi-logo.png') }}" alt="Pelangi Logo">
    <span class="admin-topbar-caption">Sistem Informasi Manajemen</span>
    <span class="admin-topbar-user">{{ auth()->user()?->name ?? 'Administrator' }}</span>
    <button class="btn admin-menu-toggle" type="button" data-admin-nav-toggle aria-expanded="false" aria-controls="admin-sidebar">
        Menu
    </button>
</div>
<div class="admin-shell">
    <aside class="admin-sidebar" id="admin-sidebar" data-admin-sidebar>
        <div>
            <nav class="admin-nav" aria-label="Navigasi admin">
                <a class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}"><x-icon name="dashboard" />Dashboard</a>
                <div class="admin-nav-group">
                    <p>MASTER DATA</p>
                    <a class="admin-nav-link {{ request()->routeIs('admin.categories.*') ? 'is-active' : '' }}" href="{{ route('admin.categories.index') }}"><x-icon name="tags" />Kategori Produk</a>
                    <a class="admin-nav-link {{ request()->routeIs('admin.products.*') ? 'is-active' : '' }}" href="{{ route('admin.products.index') }}"><x-icon name="package" />Produk</a>
                    <a class="admin-nav-link {{ request()->routeIs('admin.raw-materials.*') ? 'is-active' : '' }}" href="{{ route('admin.raw-materials.index') }}"><x-icon name="boxes" />Bahan Baku</a>
                    <a class="admin-nav-link {{ request()->routeIs('admin.suppliers.*') ? 'is-active' : '' }}" href="{{ route('admin.suppliers.index') }}"><x-icon name="truck" />Supplier</a>
                    <a class="admin-nav-link {{ request()->routeIs('admin.customers.*') ? 'is-active' : '' }}" href="{{ route('admin.customers.index') }}"><x-icon name="users" />Pelanggan</a>
                </div>
                <div class="admin-nav-group">
                    <p>TRANSAKSI</p>
                    <a class="admin-nav-link {{ request()->routeIs('admin.purchases.*') ? 'is-active' : '' }}" href="{{ route('admin.purchases.index') }}"><x-icon name="cart" />Pembelian</a>
                    <a class="admin-nav-link {{ request()->routeIs('admin.productions.*') ? 'is-active' : '' }}" href="{{ route('admin.productions.index') }}"><x-icon name="factory" />Produksi</a>
                    <a class="admin-nav-link {{ request()->routeIs('admin.sales.*') ? 'is-active' : '' }}" href="{{ route('admin.sales.index') }}"><x-icon name="receipt" />Penjualan</a>
                </div>
                <div class="admin-nav-group">
                    <p>PERSEDIAAN</p>
                    <a class="admin-nav-link {{ request()->routeIs('admin.stock.*') ? 'is-active' : '' }}" href="{{ route('admin.stock.index') }}"><x-icon name="warehouse" />Stok</a>
                </div>
                <div class="admin-nav-group">
                    <p>LAPORAN</p>
                    <a class="admin-nav-link {{ request()->routeIs('admin.reports.*') ? 'is-active' : '' }}" href="{{ route('admin.reports.index') }}"><x-icon name="chart" />Rekap &amp; Laporan</a>
                </div>
                <div class="admin-nav-group">
                    <p>LAINNYA</p>
                    <a class="admin-nav-link {{ request()->routeIs('admin.marketplace-links.*') ? 'is-active' : '' }}" href="{{ route('admin.marketplace-links.index') }}"><x-icon name="store" />Marketplace</a>
                    <a class="admin-nav-link" href="{{ route('home') }}"><x-icon name="house" />Lihat Website</a>
                </div>
            </nav>
        </div>
        <div class="admin-bottom-actions">
            <form class="admin-logout" method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="btn admin-logout-button"><x-icon name="logout" />Keluar</button>
            </form>
        </div>
    </aside>
    <main class="admin-main">
        @if(session('success'))<div class="admin-toast" data-toast role="status" aria-live="polite">{{ session('success') }}<button type="button" class="toast-dismiss" data-toast-dismiss aria-label="Tutup notifikasi"><x-icon name="close" size="16" /></button></div>@endif
        @if($errors->any())
            <div class="admin-alert" role="alert">
                <strong>Data belum bisa disimpan.</strong>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif
        @yield('content')
    </main>
</div>
<div class="modal" data-modal data-confirm-dialog aria-hidden="true">
    <div class="modal-backdrop" data-confirm-cancel></div>
    <section class="modal-content confirm-dialog" role="alertdialog" aria-modal="true" aria-labelledby="confirm-title" aria-describedby="confirm-message" tabindex="-1">
        <div class="confirm-dialog-heading"><span class="confirm-dialog-icon"><x-icon name="alert" size="18" /></span><h2 id="confirm-title">Konfirmasi tindakan</h2></div>
        <p id="confirm-message">Lanjutkan tindakan ini?</p>
        <div class="modal-form-actions"><button type="button" class="btn btn-secondary" data-confirm-cancel>Batal</button><button type="button" class="btn btn-danger" data-confirm-accept>Konfirmasi</button></div>
    </section>
</div>
</body>
</html>
