<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#fff9f6">
    <title>Masuk | Admin Pelangi Lollycandy</title>
    @include('partials.favicons')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-page">
<div class="route-progress" data-route-progress aria-hidden="true"><span></span></div>
<main class="login-stage">
    <div class="login-container">
        <section class="login-panel" aria-labelledby="login-title">
            <a class="login-brand" href="{{ route('home') }}" aria-label="Pelangi Lollycandy, halaman utama">
                <img src="{{ asset('images/pelangi-logo.png') }}" alt="Pelangi Lollycandy">
            </a>

            <div class="login-intro">
                <h1 id="login-title">Selamat datang kembali</h1>
                <p>Kelola operasional Pelangi Lollycandy melalui satu ruang kerja.</p>
            </div>

            @if($errors->has('login'))
                <div class="login-alert" role="alert" aria-live="assertive">
                    <x-icon name="alert" size="18" />
                    <span>{{ $errors->first('login') }}</span>
                </div>
            @endif

            <form class="login-form" method="POST" action="{{ route('admin.login.submit') }}" data-login-form>
                @csrf
                <div class="login-field-group">
                    <label for="login-email">Email</label>
                    <div class="login-input-wrap {{ $errors->has('email') ? 'has-error' : '' }}">
                        <x-icon name="mail" size="18" />
                        <input id="login-email" type="email" name="email" value="{{ old('email') }}" placeholder="Masukkan email" required autocomplete="email" @if($errors->has('email')) aria-invalid="true" aria-describedby="login-email-error" @endif>
                    </div>
                    @error('email')<span class="login-field-error" id="login-email-error" role="alert">{{ $message }}</span>@enderror
                </div>

                <div class="login-field-group">
                    <label for="login-password">Password</label>
                    <div class="login-input-wrap {{ $errors->has('password') ? 'has-error' : '' }}">
                        <x-icon name="lock" size="18" class="login-password-mark" />
                        <input id="login-password" type="password" name="password" placeholder="Masukkan password" required autocomplete="current-password" @if($errors->has('password')) aria-invalid="true" aria-describedby="login-password-error" @endif>
                        <button class="password-toggle" type="button" data-password-toggle aria-label="Tampilkan password" title="Tampilkan password" aria-controls="login-password">
                            <span data-password-show-icon><x-icon name="eye" size="18" /></span>
                            <span data-password-hide-icon hidden><x-icon name="eye-off" size="18" /></span>
                        </button>
                    </div>
                    @error('password')<span class="login-field-error" id="login-password-error" role="alert">{{ $message }}</span>@enderror
                </div>

                <label class="login-remember"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> <span>Ingat saya</span></label>
                <button class="login-submit" type="submit" data-login-submit>
                    <span data-login-submit-label>Masuk</span>
                </button>
            </form>

            <p class="login-footnote">Akses khusus untuk pengelola Pelangi Lollycandy.</p>
        </section>

        <aside class="login-visual" aria-label="Foto Pelangi Lollycandy">
            <img src="{{ asset('images/ceria.jpg') }}" alt="Suasana ceria Pelangi Lollycandy">
        </aside>
    </div>
</main>
</body>
</html>
