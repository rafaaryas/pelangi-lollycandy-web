@extends('layouts.app')

@section('title', 'Contact - Pelangi Lollycandy')

@section('content')
<section class="section">
    <div class="container">
        <h1>Hubungi Kami</h1>
        <div class="card">
            <p>Butuh katalog grosir? Klik WhatsApp atau kirim inquiry.</p>
            <a class="btn btn-primary" href="#">Chat WhatsApp</a>
        </div>
        <form method="POST" action="{{ route('contact.store') }}" class="card" style="margin-top:1rem;display:grid;gap:.7rem">
            @csrf
            <input name="name" placeholder="Nama">
            <input name="email" placeholder="Email">
            <input name="phone" placeholder="No. WhatsApp">
            <input name="subject" placeholder="Subjek">
            <textarea name="message" rows="5" placeholder="Pesan"></textarea>
            <button class="btn btn-primary">Kirim</button>
        </form>
    </div>
</section>
@endsection
