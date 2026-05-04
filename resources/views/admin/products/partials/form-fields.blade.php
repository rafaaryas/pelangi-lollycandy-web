@php
    $editing = $product !== null;
    $variants = $editing ? $product->variants : collect([(object) ['id' => null, 'size' => '', 'flavor' => '', 'stock_info' => '', 'price' => '', 'is_active' => true]]);
@endphp

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
    <textarea name="description" rows="4" required>{{ old('description', $product->description ?? '') }}</textarea>
</div>
<div class="form-group">
    <label>SKU</label>
    <input name="sku" value="{{ old('sku', $product->sku ?? '') }}" required>
</div>
<div class="form-group">
    <label>Harga Produk</label>
    <input type="number" step="0.01" min="0" name="price_from" value="{{ old('price_from', $product->price_from ?? '') }}" required>
</div>
<div class="form-group">
    <label>Rasa Utama</label>
    <input name="flavor" value="{{ old('flavor', $product->flavor ?? '') }}">
</div>
<div class="form-group">
    <label>Berat (gram)</label>
    <input type="number" min="0" name="weight_gram" value="{{ old('weight_gram', $product->weight_gram ?? 0) }}" required>
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
    <input type="file" name="image" accept="image/*" data-image-preview-input="preview-{{ $product->id ?? 'new' }}">
    <img class="admin-image-preview" id="preview-{{ $product->id ?? 'new' }}" src="{{ $editing && $product->images->first() ? asset('storage/'.$product->images->first()->image_path) : 'https://placehold.co/220x160?text=Preview' }}" alt="Preview">
</div>
<div class="form-group">
    <label>Status Aktif</label>
    <label style="display:flex;align-items:center;gap:.5rem">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))> Aktif
    </label>
</div>

<div class="form-group form-col-2">
    <label>Varian Produk (opsional)</label>
    <div class="variant-grid">
        @foreach($variants as $index => $variant)
            <div class="variant-card">
                <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variant->id ?? '' }}">
                <input name="variants[{{ $index }}][size]" placeholder="Ukuran" value="{{ old("variants.$index.size", $variant->size ?? '') }}">
                <input name="variants[{{ $index }}][flavor]" placeholder="Rasa" value="{{ old("variants.$index.flavor", $variant->flavor ?? '') }}">
                <input name="variants[{{ $index }}][stock_info]" placeholder="Stok info" value="{{ old("variants.$index.stock_info", $variant->stock_info ?? '') }}">
                <input type="number" step="0.01" min="0" name="variants[{{ $index }}][price]" placeholder="Harga" value="{{ old("variants.$index.price", $variant->price ?? '') }}">
                <label><input type="checkbox" name="variants[{{ $index }}][is_active]" value="1" @checked(old("variants.$index.is_active", $variant->is_active ?? true))> Aktif</label>
            </div>
        @endforeach
    </div>
</div>
