@php
    $editing = $product !== null;
    $productImagePath = $editing ? $product->images->first()?->storagePath() : null;
@endphp

{{-- Product identity fields: edit these to control what appears on the homepage/catalog. --}}
<div class="form-group">
    <label>Nama Produk</label>
    <input name="name" value="{{ old('name', $product->name ?? '') }}" required>
</div>
<div class="form-group">
    <label>Kategori</label>
    <select name="category_id" required>
        @foreach($categories as $category)
            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? '') == $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
</div>
<div class="form-group form-col-2">
    <label>Deskripsi</label>
    <textarea class="admin-description-field" name="description" rows="5" required>{{ old('description', $product->description ?? '') }}</textarea>
</div>
<div class="form-group">
    <label>Harga Produk</label>
    <input type="number" step="0.01" min="0" name="price_from" value="{{ old('price_from', $product->price_from ?? '') }}" required>
</div>
<div class="form-group">
    <label>Badge</label>
    <select name="badge">
        <option value="none" @selected(old('badge', $product->badge ?? 'none') === 'none')>None</option>
        <option value="new" @selected(old('badge', $product->badge ?? '') === 'new')>Baru</option>
        <option value="best_seller" @selected(old('badge', $product->badge ?? '') === 'best_seller')>Best Seller</option>
    </select>
</div>
<div class="form-group">
    <label>Link Shopee</label>
    <input name="shopee_url" value="{{ old('shopee_url', $product->shopee_url ?? '') }}">
</div>
<div class="form-group">
    <label>Upload Gambar</label>
    <label class="admin-file-picker">
        <span>Pilih Foto</span>
        <input type="file" name="image" accept="image/*" data-image-preview-input="preview-{{ $product->id ?? 'new' }}">
    </label>
    <img class="admin-image-preview" id="preview-{{ $product->id ?? 'new' }}" data-image-preview-img src="{{ $productImagePath ? asset('storage/'.$productImagePath) : asset(\App\Models\ProductImage::PLACEHOLDER) }}" alt="Preview gambar produk">
</div>
<div class="form-group">
    <label>Status Aktif</label>
    <label style="display:flex;align-items:center;gap:.5rem">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))> Aktif
    </label>
</div>
