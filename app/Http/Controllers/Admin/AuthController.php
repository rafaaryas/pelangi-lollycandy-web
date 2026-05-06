<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

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
