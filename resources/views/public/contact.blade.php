@extends('layouts.app')

@section('title', 'Contact - Pelangi Lollycandy')

@section('content')
<section class="contact-section">
    <div class="contact-container">
        <h1>Hubungi Kami</h1>
        <p>Kirim pesan langsung ke WhatsApp kami</p>

        <form method="POST" action="{{ route('contact.store') }}" class="contact-card contact-form">
            @csrf
            {{-- CONTACT FORM: submitted data is converted into a WhatsApp prefilled message. --}}
            <input id="name" name="name" placeholder="Nama" required>
            <input id="email" name="email" type="email" placeholder="Email">
            <input id="phone" name="phone" placeholder="No. WhatsApp" required>
            <input id="subject" name="subject" placeholder="Subjek">
            <textarea id="message" name="message" rows="5" placeholder="Pesan" required></textarea>
            <button class="contact-btn">Kirim</button>
        </form>
    </div>
</section>
@endsection
