@extends('layouts.admin')
@section('content')
<h1>Edit Produk</h1>
<form class="card" method="POST" action="{{ route('admin.products.update', $product) }}" style="display:grid;gap:.7rem">
    @csrf @method('PUT')
    <input name="name" value="{{ $product->name }}">
    <select name="category_id">@foreach($categories as $cat)<option value="{{ $cat->id }}" @selected($cat->id===$product->category_id)>{{ $cat->name }}</option>@endforeach</select>
    <input name="sku" value="{{ $product->sku }}">
    <input name="price_from" value="{{ $product->price_from }}">
    <textarea name="description">{{ $product->description }}</textarea>
    <button class="btn btn-primary">Update</button>
</form>
@endsection
