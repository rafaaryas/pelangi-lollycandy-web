@extends('layouts.app')

@section('title', 'Contact - Pelangi Lollycandy')

@section('content')
<section class="contact-section">
    <div class="contact-container">
        <h1>Hubungi Kami</h1>
        <p>Kirim pesan langsung ke WhatsApp kami</p>

        <form method="POST" action="{{ route('contact.store') }}" class="contact-card contact-form">
            @csrf
            <input id="name" placeholder="Nama" required>
            <input id="email" placeholder="Email" required>
            <input id="phone" placeholder="No. WhatsApp" required>
            <input id="subject" placeholder="Subjek" required>
            <textarea id="message" rows="5" placeholder="Pesan" required></textarea>
            <button class="contact-btn">Kirim</button>
        </form>
    </div>
</section>
@endsection
