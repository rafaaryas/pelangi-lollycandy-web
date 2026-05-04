@extends('layouts.admin')
@section('content')
<h1>Kategori</h1>
<a class="btn btn-primary" href="{{ route('admin.categories.create') }}">Tambah Kategori</a>
<div class="card" style="margin-top:1rem">
@foreach($categories as $category)
<p>{{ $category->name }}</p>
@endforeach
{{ $categories->links() }}
</div>
@endsection
