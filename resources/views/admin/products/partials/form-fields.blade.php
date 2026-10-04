@php
    $editing = $product !== null;
    $productImagePath = $editing ? $product->images->first()?->storagePath() : null;
@endphp

{{-- Product identity fields: edit these to control what appears on the homepage/catalog. --}}
<h4 class="form-section-title">Informasi Dasar</h4>
<div class="form-group">
    <label for="product-name-{{ $product->id ?? 'new' }}">Nama Produk <span aria-hidden="true">*</span></label>
    <input id="product-name-{{ $product->id ?? 'new' }}" name="name" value="{{ old('name', $product->name ?? '') }}" required autocomplete="off" @if($errors->has('name')) aria-invalid="true" aria-describedby="error-name" @endif>
    <x-field-error name="name" />
</div>
<div class="form-group">
    <label for="product-category-{{ $product->id ?? 'new' }}">Kategori <span aria-hidden="true">*</span></label>
    <select id="product-category-{{ $product->id ?? 'new' }}" name="category_id" required @if($errors->has('category_id')) aria-invalid="true" aria-describedby="error-category_id" @endif>
        @foreach($categories as $category)
            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? '') == $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
    <x-field-error name="category_id" />
</div>
<div class="form-group">
    <label for="product-price-{{ $product->id ?? 'new' }}">Harga Produk <span aria-hidden="true">*</span></label>
    <input id="product-price-{{ $product->id ?? 'new' }}" type="number" step="0.01" min="0" name="price_from" value="{{ old('price_from', $product->price_from ?? '') }}" required @if($errors->has('price_from')) aria-invalid="true" aria-describedby="error-price_from" @endif><x-field-error name="price_from" />
</div>
<div class="form-group">
    <label for="product-minimum-stock-{{ $product->id ?? 'new' }}">Stok Minimum</label>
    <input id="product-minimum-stock-{{ $product->id ?? 'new' }}" type="number" step="0.001" min="0" name="minimum_stock" value="{{ old('minimum_stock', $product->minimum_stock ?? 0) }}">
    <small>Stok produk dikelola melalui menu Persediaan.</small>
</div>
<div class="form-group">
    <label for="product-badge-{{ $product->id ?? 'new' }}">Badge <span aria-hidden="true">*</span></label>
    <select id="product-badge-{{ $product->id ?? 'new' }}" name="badge" required @if($errors->has('badge')) aria-invalid="true" aria-describedby="error-badge" @endif>
        <option value="none" @selected(old('badge', $product->badge ?? 'none') === 'none')>None</option>
        <option value="new" @selected(old('badge', $product->badge ?? '') === 'new')>Baru</option>
        <option value="best_seller" @selected(old('badge', $product->badge ?? '') === 'best_seller')>Best Seller</option>
    </select>
    <x-field-error name="badge" />
</div>
<h4 class="form-section-title">Deskripsi Produk</h4>
<div class="form-group form-col-2">
    <label for="product-description-{{ $product->id ?? 'new' }}">Deskripsi <span aria-hidden="true">*</span></label>
    <textarea id="product-description-{{ $product->id ?? 'new' }}" class="admin-description-field" name="description" rows="5" required @if($errors->has('description')) aria-invalid="true" aria-describedby="error-description" @endif>{{ old('description', $product->description ?? '') }}</textarea><x-field-error name="description" />
</div>
<h4 class="form-section-title">Marketplace</h4>
<div class="form-group">
    <label for="product-marketplace-url-{{ $product->id ?? 'new' }}">Link Shopee</label>
    <input id="product-marketplace-url-{{ $product->id ?? 'new' }}" type="url" name="shopee_url" value="{{ old('shopee_url', $product->shopee_url ?? '') }}">
</div>
<div class="form-group">
    <label for="product-whatsapp-url-{{ $product->id ?? 'new' }}">Link WhatsApp</label>
    <input id="product-whatsapp-url-{{ $product->id ?? 'new' }}" type="url" name="whatsapp_url" value="{{ old('whatsapp_url', $product->whatsapp_url ?? '') }}" placeholder="Opsional, gunakan tautan default jika kosong">
</div>
<h4 class="form-section-title">Foto Produk</h4>
<div class="form-group">
    <span class="field-label">Upload Gambar</span>
    <label class="admin-file-picker" data-dropzone>
        <x-icon name="plus" size="18" />
        <span>Tarik foto ke sini atau pilih foto</span>
        <input type="file" name="image" accept="image/*" data-image-preview-input="preview-{{ $product->id ?? 'new' }}">
    </label>
    <img class="admin-image-preview" id="preview-{{ $product->id ?? 'new' }}" data-image-preview-img src="{{ $productImagePath ? asset('storage/'.$productImagePath) : '' }}" alt="Preview gambar produk" @if(!$productImagePath) hidden @endif>
    <button type="button" class="btn btn-secondary image-remove" data-image-remove="preview-{{ $product->id ?? 'new' }}" hidden>Hapus pilihan foto</button>
</div>
<div class="form-group">
    <span class="field-label">Status Aktif</span>
    <label style="display:flex;align-items:center;gap:.5rem">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))> Aktif
    </label>
</div>
