<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceLink;
use App\Support\BusinessContact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index()
    {
        $marketplaces = MarketplaceLink::query()->where('is_active', true)->get();

        $whatsAppUrl = BusinessContact::whatsAppUrl();

        return view('public.contact', compact('marketplaces', 'whatsAppUrl'));
    }

    public function store(Request $request)
    {
        // Contact form target: redirect visitors to WhatsApp with a prefilled message.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^(?:\+62|62|0)8[0-9]{7,12}$/'],
            'subject' => ['nullable', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'required' => ':attribute wajib diisi.',
            'email' => 'Masukkan alamat email yang valid.',
            'max' => ':attribute terlalu panjang.',
            'phone.regex' => 'Masukkan nomor WhatsApp Indonesia yang valid, misalnya 081234567890.',
        ], [
            'name' => 'Nama',
            'email' => 'Email',
            'phone' => 'No. WhatsApp',
            'subject' => 'Subjek',
            'message' => 'Pesan',
        ]);

        $message = implode("\n", array_filter([
            'Halo Pelangi Lollycandy, saya ingin menghubungi admin.',
            'Nama: '.$validated['name'],
            isset($validated['email']) ? 'Email: '.$validated['email'] : null,
            'No. WhatsApp: '.$validated['phone'],
            isset($validated['subject']) ? 'Subjek: '.$validated['subject'] : null,
            'Pesan: '.$validated['message'],
        ]));

        $whatsAppUrl = BusinessContact::whatsAppUrl($message);
        if ($whatsAppUrl === null) {
            $error = 'WhatsApp belum tersedia. Silakan gunakan tautan marketplace yang aktif.';

            return $request->expectsJson()
                ? response()->json(['message' => $error], 503)
                : back()->withErrors(['contact' => $error])->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json(['url' => $whatsAppUrl]);
        }

        return redirect()->away($whatsAppUrl);
    }
}
