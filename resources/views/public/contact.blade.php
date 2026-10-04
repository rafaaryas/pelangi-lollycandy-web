@extends('layouts.app')

@section('title', 'Hubungi Kami - Pelangi Lollycandy')

@section('content')
<section class="contact-section" aria-labelledby="contact-title">
    <div class="container contact-container">
        <div class="contact-intro"><p class="eyebrow">Punya pertanyaan?</p><h1 id="contact-title">Hubungi Kami</h1><p>Mau tanya produk, kebutuhan grosir, reseller, event, hampers, atau kolaborasi? Ceritakan saja kepada kami.</p></div>
        <div class="contact-layout">
            <form method="POST" action="{{ route('contact.store') }}" class="contact-form" aria-label="Formulir kontak">
                @csrf
                <div class="contact-form-head"><h2>Kirimi kami pesan</h2><p>Pesanmu akan dibuka di WhatsApp untuk kamu kirim.</p></div>
                <div class="contact-feedback" data-contact-feedback role="status" aria-live="polite" hidden></div>
                @if($errors->any())<div class="form-error-summary" role="alert">Periksa kembali kolom yang ditandai sebelum melanjutkan.</div>@endif
                <div class="contact-fields">
                    <div class="contact-field"><label for="name">Nama <span aria-hidden="true">*</span></label><input id="name" name="name" autocomplete="name" value="{{ old('name') }}" placeholder="Nama kamu" required maxlength="120" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>@error('name')<span class="field-error" id="name-error">{{ $message }}</span>@enderror</div>
                    <div class="contact-field"><label for="email">Email <span class="optional">(opsional)</span></label><input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" placeholder="nama@email.com" maxlength="120" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>@error('email')<span class="field-error" id="email-error">{{ $message }}</span>@enderror</div>
                    <div class="contact-field"><label for="phone">No. WhatsApp <span aria-hidden="true">*</span></label><input id="phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" required maxlength="30" @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror>@error('phone')<span class="field-error" id="phone-error">{{ $message }}</span>@enderror</div>
                    <div class="contact-field"><label for="subject">Subjek <span class="optional">(opsional)</span></label><input id="subject" name="subject" value="{{ old('subject') }}" placeholder="Apa yang ingin dibahas?" maxlength="180" @error('subject') aria-invalid="true" aria-describedby="subject-error" @enderror>@error('subject')<span class="field-error" id="subject-error">{{ $message }}</span>@enderror</div>
                    <div class="contact-field contact-field-full"><label for="message">Pesan <span aria-hidden="true">*</span></label><textarea id="message" name="message" rows="5" placeholder="Ceritakan kebutuhanmu di sini…" required maxlength="2000" @error('message') aria-invalid="true" aria-describedby="message-error" @enderror>{{ old('message') }}</textarea>@error('message')<span class="field-error" id="message-error">{{ $message }}</span>@enderror</div>
                </div>
                <button class="btn btn-primary contact-submit" type="submit"><img src="{{ asset('images/marketplace-icons/whatsapp.svg') }}" alt="" width="20" height="20"><span data-contact-button-label>Kirim via WhatsApp</span><x-icon name="arrow-right" size="17" /></button>
            </form>
            <aside class="contact-aside"><div class="contact-aside-art" aria-hidden="true"><img src="{{ asset('images/pelangi-hero-candy.png') }}" alt="" loading="lazy" width="3168" height="1344"></div><h2>Bisa hubungi kami untuk</h2><ul><li>Pemesanan produk</li><li>Grosir &amp; reseller</li><li>Hampers &amp; event</li><li>Kolaborasi</li><li>Pertanyaan produk</li></ul><p>Lebih nyaman chat langsung? <a href="https://wa.me/6285184005430" target="_blank" rel="noopener">Buka WhatsApp <x-icon name="arrow-right" size="15" /></a></p></aside>
        </div>
    </div>
</section>
@endsection
