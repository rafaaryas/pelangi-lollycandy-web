@extends('layouts.admin')
@section('content')
<h1>Inquiry</h1>
<a class="btn btn-outline" href="{{ route('admin.inquiries.export') }}">Export CSV</a>
<div class="card" style="margin-top:1rem">
@foreach($inquiries as $inquiry)
    <p><strong>{{ $inquiry->name }}</strong> - {{ $inquiry->phone }} - {{ $inquiry->is_handled ? 'Handled' : 'Open' }}</p>
@endforeach
{{ $inquiries->links() }}
</div>
@endsection
