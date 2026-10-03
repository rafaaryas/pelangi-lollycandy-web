@extends('layouts.admin')

@section('title', $item->name.' | '.$title)

@section('content')
<header class="module-page-head detail-page-head">
    <div><div class="admin-breadcrumb"><a href="{{ route($backRoute) }}">{{ $title }}</a><span>/</span><strong>{{ $item->name }}</strong></div><h1>{{ $item->name }}</h1><p>Rincian {{ strtolower($title) }} dan aktivitas terkait.</p></div>
    <a class="btn btn-secondary" href="{{ route($backRoute) }}">Kembali ke daftar</a>
</header>
<section class="document-summary">
    @foreach($summary as $metric)<div><span>{{ $metric['label'] }}</span><strong>{{ number_format($metric['value'], 0, ',', '.') }}</strong></div>@endforeach
</section>
<section class="document-section"><h2>Informasi {{ strtolower($title) }}</h2><dl class="master-detail-list">
    @foreach($details as $label => $value)<dt>{{ $label }}</dt><dd>{{ $value }}</dd>@endforeach
    <dt>Dibuat</dt><dd>{{ $item->created_at->format('d M Y, H:i') }}</dd>
    <dt>Diperbarui</dt><dd>{{ $item->updated_at->format('d M Y, H:i') }}</dd>
</dl></section>
<section class="document-section"><div class="document-section-heading"><div><h2>{{ $rowHeading }}</h2><p>Catatan aktivitas terbaru yang terkait.</p></div></div>
    <div class="admin-table-wrap module-table-wrap"><table class="admin-table module-table"><thead><tr><th>Referensi</th><th>Tanggal</th><th>Keterangan</th><th class="numeric-cell">Nilai</th></tr></thead><tbody>
        @forelse($rows as $row)<tr><td><a class="table-primary-link" href="{{ $row['url'] }}">{{ $row['reference'] }}</a></td><td>{{ $row['date']->format('d M Y') }}</td><td>{{ $row['description'] }}</td><td class="numeric-cell">{{ $row['quantity'] }}</td></tr>
        @empty<tr><td colspan="4"><div class="table-empty"><strong>{{ $emptyMessage }}</strong></div></td></tr>@endforelse
    </tbody></table></div>
</section>
@endsection
