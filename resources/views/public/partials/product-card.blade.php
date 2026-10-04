@php($productImagePath = $product->images->first()?->storagePath())
<article class="product-card">
    <a class="product-card-link" href="{{ route('products.show', $product->slug) }}" aria-label="Lihat {{ $product->name }}">
        <span class="product-media">
            <img loading="lazy" class="product-image" src="{{ $productImagePath ? asset('storage/'.$productImagePath) : asset(\App\Models\ProductImage::PLACEHOLDER) }}" alt="{{ $product->name }}" width="560" height="560">
            @if($product->badge === 'new') <span class="product-badge">BARU</span> @endif
            @if($product->badge === 'best_seller') <span class="product-badge">FAVORIT</span> @endif
        </span>
        <div class="product-card-body">
            <span class="product-card-category">{{ $product->category?->name ?? 'Pelangi Lollycandy' }}</span>
            @if(($headingLevel ?? 3) === 2)<h2 class="product-card-title">{{ $product->name }}</h2>@else<h3 class="product-card-title">{{ $product->name }}</h3>@endif
            <span class="product-card-price-label">Mulai dari</span>
            <span class="product-price">Rp {{ number_format($product->price_from, 0, ',', '.') }}</span>
            <span class="product-card-action">Lihat Produk <x-icon name="arrow-right" size="17" /></span>
        </div>
    </a>
</article>
