@extends('layouts.admin')
@section('title', 'Testimonials')

@section('content')
<div class="admin-page-head">
    <h1>Testimonials</h1>
    <button class="btn btn-primary" data-modal-open="testimonial-create-modal">Tambah Testimonial</button>
</div>

<div class="card">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Nama</th><th>Role</th><th>Quote</th><th>Rating</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse($testimonials as $testimonial)
                <tr>
                    <td>{{ $testimonial->name }}</td>
                    <td>{{ $testimonial->role }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($testimonial->quote, 90) }}</td>
                    <td>{{ $testimonial->rating }}/5</td>
                    <td><span class="badge {{ $testimonial->is_active ? 'badge-new' : 'badge-best' }}">{{ $testimonial->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td>
                        <div class="admin-actions">
                            <button class="btn btn-secondary" data-modal-open="edit-testimonial-{{ $testimonial->id }}">Edit</button>
                            <form method="POST" action="{{ route('admin.testimonials.destroy', $testimonial) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">Belum ada testimonial.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:1rem;">{{ $testimonials->links() }}</div>
</div>

<div class="modal" id="testimonial-create-modal" data-modal>
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-small">
        <div class="modal-head"><h3>Tambah Testimonial</h3><button data-modal-close class="btn btn-outline">Tutup</button></div>
        <form method="POST" action="{{ route('admin.testimonials.store') }}" class="admin-form-grid">
            @csrf
            <div class="form-group form-col-2"><label>Nama</label><input name="name" required></div>
            <div class="form-group form-col-2"><label>Role</label><input name="role"></div>
            <div class="form-group form-col-2"><label>Quote</label><textarea name="quote" rows="4" required></textarea></div>
            <div class="form-group"><label>Rating</label><input type="number" min="1" max="5" name="rating" value="5" required></div>
            <div class="form-group"><label><input type="checkbox" name="is_active" value="1" checked> Aktif</label></div>
            <div class="form-actions"><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

@foreach($testimonials as $testimonial)
<div class="modal" id="edit-testimonial-{{ $testimonial->id }}" data-modal>
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-small">
        <div class="modal-head"><h3>Edit Testimonial</h3><button data-modal-close class="btn btn-outline">Tutup</button></div>
        <form method="POST" action="{{ route('admin.testimonials.update', $testimonial) }}" class="admin-form-grid">
            @csrf @method('PUT')
            <div class="form-group form-col-2"><label>Nama</label><input name="name" value="{{ $testimonial->name }}" required></div>
            <div class="form-group form-col-2"><label>Role</label><input name="role" value="{{ $testimonial->role }}"></div>
            <div class="form-group form-col-2"><label>Quote</label><textarea name="quote" rows="4" required>{{ $testimonial->quote }}</textarea></div>
            <div class="form-group"><label>Rating</label><input type="number" min="1" max="5" name="rating" value="{{ $testimonial->rating }}" required></div>
            <div class="form-group"><label><input type="checkbox" name="is_active" value="1" @checked($testimonial->is_active)> Aktif</label></div>
            <div class="form-actions"><button class="btn btn-secondary">Update</button></div>
        </form>
    </div>
</div>
@endforeach
@endsection
