@extends('layouts.admin')
@section('content')
<h1>SEO Settings</h1>
<form method="POST" action="{{ route('admin.settings.update') }}" class="card" style="display:grid;gap:.7rem">
    @csrf @method('PUT')
    <input name="seo_meta_title" value="{{ $settings['seo_meta_title'] ?? '' }}" placeholder="Meta Title">
    <textarea name="seo_meta_description" placeholder="Meta Description">{{ $settings['seo_meta_description'] ?? '' }}</textarea>
    <input name="seo_og_image" value="{{ $settings['seo_og_image'] ?? '' }}" placeholder="OG image URL">
    <button class="btn btn-primary">Simpan</button>
</form>
@endsection
