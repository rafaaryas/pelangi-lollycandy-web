<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceLink;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index()
    {
        $marketplaces = MarketplaceLink::query()->where('is_active', true)->get();

        return view('public.contact', compact('marketplaces'));
    }

    public function store(Request $request)
    {
        // Contact form target: redirect visitors to WhatsApp with a prefilled message.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'subject' => ['nullable', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $message = implode("\n", array_filter([
            'Halo Pelangi Lollycandy, saya ingin menghubungi admin.',
            'Nama: '.$validated['name'],
            isset($validated['email']) ? 'Email: '.$validated['email'] : null,
            'No. WhatsApp: '.$validated['phone'],
            isset($validated['subject']) ? 'Subjek: '.$validated['subject'] : null,
            'Pesan: '.$validated['message'],
        ]));

        return redirect()->away('https://wa.me/6285184005430?text='.urlencode($message));
    }
}
