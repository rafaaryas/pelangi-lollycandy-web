@extends('layouts.admin')
@section('title', 'Inquiry')

@section('content')
<div class="admin-page-head">
    <h1>Inquiry Masuk</h1>
    <a class="btn btn-outline" href="{{ route('admin.inquiries.export') }}">Export CSV</a>
</div>

<div class="card">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Nama</th><th>Kontak</th><th>Subjek</th><th>Pesan</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse($inquiries as $inquiry)
                <tr>
                    <td>{{ $inquiry->name }}</td>
                    <td>{{ $inquiry->phone }}<br><small class="text-muted">{{ $inquiry->email }}</small></td>
                    <td>{{ $inquiry->subject ?: '-' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($inquiry->message, 90) }}</td>
                    <td><span class="badge {{ $inquiry->is_handled ? 'badge-new' : 'badge-best' }}">{{ $inquiry->is_handled ? 'Handled' : 'Open' }}</span></td>
                    <td>
                        @if(!$inquiry->is_handled)
                            <form method="POST" action="{{ route('admin.inquiries.handle', $inquiry) }}">
                                @csrf
                                <button class="btn btn-secondary">Mark Handled</button>
                            </form>
                        @else
                            <span class="text-muted">Selesai</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">Belum ada inquiry.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:1rem;">{{ $inquiries->links() }}</div>
</div>
@endsection
