<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:254'],
            'password' => ['required', 'string', 'max:4096'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Masukkan alamat email yang valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // Hash account identifiers so the shared cache does not contain email addresses.
        $accountKey = 'admin-login:account:'.hash('sha256', Str::lower(trim($credentials['email'])).'|'.$request->ip());
        $ipKey = 'admin-login:ip:'.hash('sha256', (string) $request->ip());
        if (RateLimiter::tooManyAttempts($accountKey, 5) || RateLimiter::tooManyAttempts($ipKey, 30)) {
            $seconds = max(RateLimiter::availableIn($accountKey), RateLimiter::availableIn($ipKey));

            return back()->withErrors(['login' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik."])
                ->onlyInput('email');
        }

        // Count before password verification so unsuccessful attempts and non-admin
        // accounts share the same protection. Only a successful admin login clears it.
        RateLimiter::hit($accountKey, 60);
        RateLimiter::hit($ipKey, 60);

        try {
            if (!Auth::attempt($credentials, $request->boolean('remember'))) {
                return back()
                    ->withErrors(['login' => 'Email atau password salah. Silakan cek kembali data login Anda.'])
                    ->onlyInput('email');
            }

            $request->session()->regenerate();

            if (!Auth::user()->is_admin) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('admin.login')
                    ->withErrors(['login' => 'Akun ini belum memiliki akses admin. Hubungi pemilik sistem untuk mengaktifkan akses.'])
                    ->onlyInput('email');
            }

            RateLimiter::clear($accountKey);

            return redirect()->route('admin.dashboard');
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['login' => 'Terjadi kesalahan saat login. Silakan coba lagi beberapa saat lagi.'])
                ->onlyInput('email');
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
