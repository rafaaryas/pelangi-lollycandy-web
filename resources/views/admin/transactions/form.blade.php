@extends('layouts.admin')

@section('title', $title.' | Pelangi Admin')

@section('content')
@php
    $isEdit = $record !== null;
    $resource = ['purchases' => 'purchases', 'productions' => 'productions', 'sales' => 'sales'][$kind];
    $formAction = $isEdit ? route('admin.'.$resource.'.update', $record) : route('admin.'.$resource.'.store');
    $dateField = ['purchases' => 'purchase_date', 'productions' => 'production_date', 'sales' => 'sale_date'][$kind];
    $numberField = ['purchases' => 'reference_number', 'productions' => 'production_number', 'sales' => 'invoice_number'][$kind];
    $lines = $kind === 'purchases' ? ($record?->details ?? collect()) : ($kind === 'productions' ? ($record?->materials ?? collect()) : ($record?->details ?? collect()));
    $initialLines = $lines->isNotEmpty() ? $lines : collect([null]);
@endphp
<header class="module-page-head">
    <div><div class="admin-breadcrumb"><a href="{{ route('admin.'.$resource.'.index') }}">{{ ucfirst($resource) }}</a><span>/</span><strong>{{ $isEdit ? 'Edit Draft' : 'Baru' }}</strong></div><h1>{{ $title }}</h1><p>Isi informasi transaksi. Nilai dan stok akan dihitung ulang di server.</p></div>
</header>
<form method="POST" action="{{ $formAction }}" class="transaction-form" data-transaction-form data-transaction-kind="{{ $kind }}" data-loading-form>
    @csrf @if($isEdit) @method('PUT') @endif
    <section class="form-section">
        <div class="form-section-heading"><h2>Informasi transaksi</h2><p>Nomor dapat dikosongkan untuk dibuat otomatis.</p></div>
        <div class="transaction-fields">
            @if($kind === 'purchases')
                <label class="module-field">Supplier<select name="supplier_id" required><option value="">Pilih supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected(old('supplier_id', $record?->supplier_id) == $supplier->id)>{{ $supplier->name }}</option>@endforeach</select></label>
            @elseif($kind === 'sales')
                <label class="module-field">Pelanggan <span class="field-optional">(opsional)</span><select name="customer_id"><option value="">Pelanggan umum</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected(old('customer_id', $record?->customer_id) == $customer->id)>{{ $customer->name }}</option>@endforeach</select></label>
            @endif
            <label class="module-field">Tanggal<input type="date" name="{{ $dateField }}" value="{{ old($dateField, $record?->{$dateField}?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required></label>
            <label class="module-field">{{ $kind === 'purchases' ? 'Nomor pembelian' : ($kind === 'productions' ? 'Nomor produksi' : 'Nomor invoice') }}<input name="{{ $numberField }}" value="{{ old($numberField, $record?->{$numberField}) }}" placeholder="Dibuat otomatis bila kosong"></label>
            <label class="module-field module-field-wide">Catatan<textarea name="notes" rows="2">{{ old('notes', $record?->notes) }}</textarea></label>
        </div>
    </section>

    @if($kind === 'purchases')
        <section class="form-section" data-line-section>
            <div class="form-section-heading"><div><h2>Bahan baku dibeli</h2><p>Pilih bahan, jumlah, dan harga per satuan.</p></div><button type="button" class="btn btn-secondary btn-compact" data-add-line><x-icon name="plus" />Tambah bahan</button></div>
            <div class="line-table-wrap"><table class="line-table"><thead><tr><th>Bahan baku</th><th>Jumlah</th><th>Satuan</th><th>Harga satuan</th><th>Subtotal</th><th></th></tr></thead><tbody data-lines>
                @foreach($initialLines as $index => $line)
                    <tr data-line>
                        <td><select name="items[{{ $index }}][raw_material_id]" data-material required><option value="">Pilih bahan baku</option>@foreach($materials as $material)<option value="{{ $material->id }}" data-unit="{{ $material->unit }}" @selected(old("items.$index.raw_material_id", $line?->raw_material_id) == $material->id)>{{ $material->name }}</option>@endforeach</select></td>
                        <td><input name="items[{{ $index }}][quantity]" type="number" step="0.001" min="0.001" value="{{ old("items.$index.quantity", $line?->quantity) }}" data-quantity required></td>
                        <td><span class="line-unit" data-unit-label>—</span></td>
                        <td><input name="items[{{ $index }}][unit_price]" type="number" step="0.01" min="0" value="{{ old("items.$index.unit_price", $line?->unit_price) }}" data-price required></td>
                        <td class="line-subtotal" data-subtotal>Rp0</td><td><button type="button" class="line-remove" data-remove-line aria-label="Hapus baris"><x-icon name="close" size="15" /></button></td>
                    </tr>
                @endforeach
            </tbody></table></div>
            <div class="transaction-total"><span>Total pembelian</span><strong data-total>Rp0</strong></div>
        </section>
    @elseif($kind === 'productions')
        <section class="form-section" data-line-section>
            <div class="form-section-heading"><div><h2>Bahan baku digunakan</h2><p>Stok yang tersedia ditampilkan saat konfirmasi.</p></div><button type="button" class="btn btn-secondary btn-compact" data-add-line><x-icon name="plus" />Tambah bahan</button></div>
            <div class="line-table-wrap"><table class="line-table"><thead><tr><th>Bahan baku</th><th>Jumlah digunakan</th><th>Satuan</th><th></th></tr></thead><tbody data-lines>
                @foreach($initialLines as $index => $line)<tr data-line>
                    <td><select name="materials[{{ $index }}][raw_material_id]" data-material required><option value="">Pilih bahan</option>@foreach($materials as $material)<option value="{{ $material->id }}" data-unit="{{ $material->unit }}" data-stock="{{ $material->current_stock }}" @selected(old("materials.$index.raw_material_id", $line?->raw_material_id) == $material->id)>{{ $material->name }} (tersedia {{ number_format($material->current_stock, 3, ',', '.') }} {{ $material->unit }})</option>@endforeach</select></td>
                    <td><input name="materials[{{ $index }}][quantity_used]" type="number" step="0.001" min="0.001" value="{{ old("materials.$index.quantity_used", $line?->quantity_used) }}" data-quantity required></td>
                    <td><span class="line-unit" data-unit-label>—</span></td><td><button type="button" class="line-remove" data-remove-line aria-label="Hapus baris"><x-icon name="close" size="15" /></button></td>
                </tr>@endforeach
            </tbody></table></div>
            <p class="transaction-quantity-summary" data-production-summary="materials">Total bahan digunakan: —</p>
        </section>
        <section class="form-section" data-line-section>
            <div class="form-section-heading"><div><h2>Hasil produksi</h2><p>Produk jadi akan menambah stok saat dikonfirmasi.</p></div><button type="button" class="btn btn-secondary btn-compact" data-add-line><x-icon name="plus" />Tambah produk</button></div>
            <div class="line-table-wrap"><table class="line-table"><thead><tr><th>Produk</th><th>Jumlah dihasilkan</th><th>Satuan</th><th></th></tr></thead><tbody data-lines>
                @php($resultLines = $record?->results ?? collect([null]))
                @foreach($resultLines as $index => $line)<tr data-line>
                    <td><select name="results[{{ $index }}][product_id]" data-product required><option value="">Pilih produk</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected(old("results.$index.product_id", $line?->product_id) == $product->id)>{{ $product->name }}{{ $product->stock_quantity === null ? ' (stok belum dicatat)' : '' }}</option>@endforeach</select></td>
                    <td><input name="results[{{ $index }}][quantity_produced]" type="number" step="0.001" min="0.001" value="{{ old("results.$index.quantity_produced", $line?->quantity_produced) }}" required></td>
                    <td>pcs</td><td><button type="button" class="line-remove" data-remove-line aria-label="Hapus baris"><x-icon name="close" size="15" /></button></td>
                </tr>@endforeach
            </tbody></table></div>
            <p class="transaction-quantity-summary" data-production-summary="results">Total hasil produksi: 0 pcs</p>
        </section>
    @else
        <section class="form-section" data-line-section>
            <div class="form-section-heading"><div><h2>Produk terjual</h2><p>Harga dan jumlah membentuk total penjualan.</p></div><button type="button" class="btn btn-secondary btn-compact" data-add-line><x-icon name="plus" />Tambah produk</button></div>
            <div class="line-table-wrap"><table class="line-table"><thead><tr><th>Produk</th><th>Stok</th><th>Jumlah</th><th>Harga</th><th>Subtotal</th><th></th></tr></thead><tbody data-lines>
                @foreach($initialLines as $index => $line)<tr data-line>
                    <td><select name="items[{{ $index }}][product_id]" data-product required><option value="">Pilih produk</option>@foreach($products as $product)<option value="{{ $product->id }}" data-price="{{ $product->price_from }}" data-stock="{{ $product->stock_quantity }}" @selected(old("items.$index.product_id", $line?->product_id) == $product->id)>{{ $product->name }}{{ $product->stock_quantity === null ? ' (stok belum dicatat)' : '' }}</option>@endforeach</select></td>
                    <td class="line-stock" data-stock-label>—</td>
                    <td><input name="items[{{ $index }}][quantity]" type="number" step="0.001" min="0.001" value="{{ old("items.$index.quantity", $line?->quantity) }}" data-quantity required></td>
                    <td><input name="items[{{ $index }}][unit_price]" type="number" step="0.01" min="0" value="{{ old("items.$index.unit_price", $line?->unit_price) }}" data-price required></td>
                    <td class="line-subtotal" data-subtotal>Rp0</td><td><button type="button" class="line-remove" data-remove-line aria-label="Hapus baris"><x-icon name="close" size="15" /></button></td>
                </tr>@endforeach
            </tbody></table></div>
            <div class="transaction-total"><span>Total penjualan</span><strong data-total>Rp0</strong></div>
        </section>
    @endif

    <footer class="transaction-form-footer"><a class="text-action" href="{{ route('admin.'.$resource.'.index') }}"><x-icon name="arrow-left" size="16" />Kembali</a><div><button class="btn btn-secondary" name="submit_action" value="draft" type="submit"><x-icon name="save" />Simpan Draft</button><button class="btn btn-primary" name="submit_action" value="confirm" type="submit"><x-icon name="check" />Simpan &amp; Konfirmasi</button></div></footer>
</form>
@endsection
