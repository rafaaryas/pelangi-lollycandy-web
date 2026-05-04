@extends('layouts.admin')
@section('content')
<h1>Produk</h1>
<a class="btn btn-primary" href="{{ route('admin.products.create') }}">Tambah Produk</a>
<div class="card" style="margin-top:1rem">
@foreach($products as $product)
    <p>{{ $product->name }} - {{ $product->category->name ?? '-' }}</p>
@endforeach
{{ $products->links() }}
</div>
@endsection
