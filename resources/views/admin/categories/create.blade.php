@extends('layouts.admin')
@section('content')
<h1>Tambah Kategori</h1>
<form method="POST" action="{{ route('admin.categories.store') }}" class="card">@csrf
    <input name="name" placeholder="Nama kategori">
    <textarea name="description" placeholder="Deskripsi"></textarea>
    <label><input type="checkbox" name="is_active" value="1" checked> Aktif</label>
    <button class="btn btn-primary">Simpan</button>
</form>
@endsection
