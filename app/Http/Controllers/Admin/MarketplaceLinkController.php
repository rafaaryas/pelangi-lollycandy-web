<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceLink;
use Illuminate\Http\Request;

class MarketplaceLinkController extends Controller
{
    public function index()
    {
        $links = MarketplaceLink::latest()->paginate(15);
        return view('admin.marketplace-links.index', compact('links'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'platform' => ['required', 'string', 'max:80', 'unique:marketplace_links,platform'],
            'label' => ['nullable', 'string', 'max:80'],
            'url' => ['required', 'url', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        MarketplaceLink::create($validated);

        return redirect()->route('admin.marketplace-links.index')->with('success', 'Marketplace link berhasil ditambahkan.');
    }

    public function update(Request $request, MarketplaceLink $marketplace_link)
    {
        $validated = $request->validate([
            'platform' => ['required', 'string', 'max:80', 'unique:marketplace_links,platform,'.$marketplace_link->id],
            'label' => ['nullable', 'string', 'max:80'],
            'url' => ['required', 'url', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $marketplace_link->update($validated);

        return redirect()->route('admin.marketplace-links.index')->with('success', 'Marketplace link berhasil diupdate.');
    }

    public function destroy(MarketplaceLink $marketplace_link)
    {
        $marketplace_link->delete();
        return redirect()->route('admin.marketplace-links.index')->with('success', 'Marketplace link berhasil dihapus.');
    }
}
