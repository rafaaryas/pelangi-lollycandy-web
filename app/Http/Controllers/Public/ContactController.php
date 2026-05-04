<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'subject' => ['nullable', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        Inquiry::query()->create($validated);

        return back()->with('success', 'Inquiry berhasil dikirim. Tim kami akan segera menghubungi Anda.');
    }
}
