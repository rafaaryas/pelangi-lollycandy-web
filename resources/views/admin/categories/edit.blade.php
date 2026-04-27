@extends('layouts.admin')
@section('content')
<h1>Edit Kategori</h1>
<form method="POST" action="{{ route('admin.categories.update', $category) }}" class="card">@csrf @method('PUT')
    <input name="name" value="{{ $category->name }}">
    <textarea name="description">{{ $category->description }}</textarea>
    <label><input type="checkbox" name="is_active" value="1" @checked($category->is_active)> Aktif</label>
    <button class="btn btn-primary">Update</button>
</form>
@endsection
