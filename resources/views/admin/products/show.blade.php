@extends('layouts.admin')

@section('title', $product->name.' | Produk')

@section('content')
<header class="module-page-head detail-page-head">
    <div><div class="admin-breadcrumb"><a href="{{ route('admin.products.index') }}">Produk</a><span>/</span><strong>{{ $product->name }}</strong></div><h1>{{ $product->name }}</h1><p>{{ $product->category->name }} · {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</p></div>
    <a class="btn btn-secondary" href="{{ route('admin.products.index') }}">Kembali ke produk</a>
</header>

<section class="document-summary">
    <div><span>Harga mulai</span><strong>Rp{{ number_format($product->price_from, 0, ',', '.') }}</strong></div>
    <div><span>Stok saat ini</span><strong>{{ $product->stock_quantity === null ? 'Belum dicatat' : number_format($product->stock_quantity, 3, ',', '.').' pcs' }}</strong></div>
    <div><span>Minimum stok</span><strong>{{ number_format($product->minimum_stock, 3, ',', '.') }} pcs</strong></div>
    <div><span>Penjualan tercatat</span><strong>{{ $product->sale_details_count }}</strong></div>
    <div><span>Batch produksi</span><strong>{{ $product->production_results_count }}</strong></div>
</section>

<section class="document-section product-detail-content">
    @php($imagePath = $product->images->first()?->storagePath())
    <div class="product-detail-image"><img src="{{ $imagePath ? asset('storage/'.$imagePath) : asset(\App\Models\ProductImage::PLACEHOLDER) }}" alt="{{ $product->name }}"></div>
    <div><h2>Informasi produk</h2><p>{{ $product->description }}</p><dl class="product-detail-meta"><dt>Kategori</dt><dd>{{ $product->category->name }}</dd><dt>Status</dt><dd>{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</dd><dt>Label</dt><dd>{{ $product->badge === 'none' ? 'Tanpa label' : str_replace('_', ' ', ucfirst($product->badge)) }}</dd><dt>Diperbarui</dt><dd>{{ $product->updated_at->format('d M Y, H:i') }}</dd></dl></div>
</section>

<section class="document-section"><div class="document-section-heading"><div><h2>Histori stok</h2><p>Sepuluh pergerakan terakhir untuk produk ini.</p></div></div>
    @if($recentMovements->isNotEmpty())
        <div class="admin-table-wrap module-table-wrap"><table class="admin-table module-table"><thead><tr><th>Tanggal</th><th>Jenis</th><th class="numeric-cell">Jumlah</th><th>Catatan</th></tr></thead><tbody>
            @foreach($recentMovements as $movement)<tr><td>{{ $movement->movement_date->format('d M Y, H:i') }}</td><td>{{ str_replace('_', ' ', ucfirst($movement->movement_type)) }}</td><td class="numeric-cell">{{ number_format($movement->quantity, 3, ',', '.') }} pcs</td><td>{{ $movement->notes ?: '—' }}</td></tr>@endforeach
        </tbody></table></div>
    @else<p class="inline-empty">Belum ada pergerakan stok untuk produk ini.</p>@endif
</section>
@endsection
