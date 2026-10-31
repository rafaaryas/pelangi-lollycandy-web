<!DOCTYPE html>
<html lang="id" class="admin-app">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin')</title>
    <meta name="robots" content="noindex, nofollow">
    @include('partials.favicons')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body">
<div class="route-progress" data-route-progress aria-hidden="true"><span></span></div>
<header class="admin-topbar">
    <button class="admin-topbar-toggle" type="button" data-admin-nav-toggle aria-label="Perkecil navigasi" aria-expanded="true" aria-controls="admin-sidebar" title="Tampilkan atau sembunyikan navigasi"><x-icon name="menu" size="22" /></button>
    <span class="admin-topbar-context">@yield('title', 'Dashboard')</span>
    <a class="admin-topbar-website" href="{{ route('home') }}"><x-icon name="house" size="17" /><span>Lihat website</span></a>
</header>
<div class="admin-shell">
    <aside class="admin-sidebar" id="admin-sidebar" data-admin-sidebar>
        <div class="admin-sidebar-inner">
            <div class="admin-sidebar-head">
                <a class="admin-sidebar-brand" href="{{ route('admin.dashboard') }}" aria-label="Pelangi Lollycandy — Dashboard">
                    <img src="{{ asset('images/pelangi-logo.png') }}" alt="Pelangi Lollycandy">
                </a>
                <button type="button" class="admin-sidebar-close" data-admin-nav-close aria-label="Tutup navigasi"><x-icon name="close" size="18" /></button>
            </div>
            <nav class="admin-nav" aria-label="Navigasi admin">
                <a class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}" title="Dashboard" aria-label="Dashboard" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif><x-icon name="dashboard" /><span class="admin-nav-label">Dashboard</span></a>
                @php($navGroups = [
                    ['key' => 'master', 'label' => 'Master Data', 'icon' => 'boxes', 'links' => [['categories', 'Kategori Produk', 'tags'], ['products', 'Produk', 'package'], ['raw-materials', 'Bahan Baku', 'boxes'], ['suppliers', 'Supplier', 'truck'], ['customers', 'Pelanggan', 'users']]],
                    ['key' => 'transactions', 'label' => 'Transaksi', 'icon' => 'receipt', 'links' => [['purchases', 'Pembelian', 'cart'], ['productions', 'Produksi', 'factory'], ['sales', 'Penjualan', 'receipt']]],
                    ['key' => 'stock', 'label' => 'Persediaan', 'icon' => 'warehouse', 'links' => [['stock', 'Stok', 'warehouse']]],
                    ['key' => 'reports', 'label' => 'Laporan', 'icon' => 'chart', 'links' => [['reports', 'Rekap & Laporan', 'chart']]],
                    ['key' => 'other', 'label' => 'Lainnya', 'icon' => 'store', 'links' => [['marketplace-links', 'Marketplace', 'store']]],
                ])
                @foreach($navGroups as $group)
                    @php($groupActive = collect($group['links'])->contains(fn ($link) => request()->routeIs('admin.'.$link[0].'.*')))
                    <div class="admin-nav-group" data-nav-group="{{ $group['key'] }}" data-active="{{ $groupActive ? 'true' : 'false' }}">
                        <button class="admin-nav-parent {{ $groupActive ? 'is-active' : '' }}" type="button" aria-expanded="{{ $groupActive ? 'true' : 'false' }}" aria-controls="nav-{{ $group['key'] }}" title="{{ $group['label'] }}" aria-label="{{ $group['label'] }}"><x-icon :name="$group['icon']" /><span class="admin-nav-label">{{ $group['label'] }}</span><x-icon name="chevron-down" size="15" class="admin-nav-chevron" /></button>
                        <div class="admin-nav-children" id="nav-{{ $group['key'] }}" @if(!$groupActive) inert @endif><div class="admin-nav-children-inner">
                            @foreach($group['links'] as [$resource, $label, $icon])
                                <a class="admin-nav-link {{ request()->routeIs('admin.'.$resource.'.*') ? 'is-active' : '' }}" href="{{ route('admin.'.$resource.'.index') }}" title="{{ $label }}" aria-label="{{ $label }}" @if(request()->routeIs('admin.'.$resource.'.*')) aria-current="page" @endif><x-icon :name="$icon" /><span class="admin-nav-label">{{ $label }}</span></a>
                            @endforeach
                            @if($group['key'] === 'stock' && request()->routeIs('admin.stock.history'))<a class="admin-nav-link is-active" href="{{ url()->current() }}" aria-current="page" title="Riwayat Stok"><x-icon name="warehouse" /><span class="admin-nav-label">Riwayat Stok</span></a>@endif
                        </div></div>
                    </div>
                @endforeach
            </nav>
            <div class="admin-bottom-actions">
                <form class="admin-logout" method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="btn admin-logout-button" type="submit" title="Keluar" aria-label="Keluar"><x-icon name="logout" /><span class="admin-nav-label">Keluar</span></button>
                </form>
            </div>
        </div>
    </aside>
    <main class="admin-main" data-admin-main tabindex="-1">
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
<button type="button" class="admin-nav-overlay" data-admin-nav-overlay aria-label="Tutup navigasi" tabindex="-1"></button>
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
