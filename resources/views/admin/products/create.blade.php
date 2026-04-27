@extends('layouts.admin')
@section('content')
<h1>Tambah Produk</h1>
<form class="card" method="POST" action="{{ route('admin.products.store') }}" style="display:grid;gap:.7rem">
    @csrf
    <input name="name" placeholder="Nama Produk">
    <select name="category_id">@foreach($categories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach</select>
    <input name="sku" placeholder="SKU">
    <input name="price_from" placeholder="Harga mulai">
    <textarea name="description" placeholder="Deskripsi"></textarea>
    <button class="btn btn-primary">Simpan</button>
</form>
@endsection
