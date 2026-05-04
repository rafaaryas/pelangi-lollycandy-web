@extends('layouts.app')

@section('title', 'About - Pelangi Lollycandy')

@section('content')
<section class="section">
    <div class="container">
        <h1>About Pelangi Lollycandy</h1>
        <p>Pelangi Lollycandy menggabungkan rasa manis premium, visual playful, dan kualitas produksi yang konsisten.</p>
        <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));margin-top:1rem">
            @foreach($timeline as $item)
                <div class="card"><strong>{{ $item['year'] }}</strong><h3>{{ $item['title'] }}</h3><p>{{ $item['desc'] }}</p></div>
            @endforeach
        </div>
    </div>
</section>
@endsection
