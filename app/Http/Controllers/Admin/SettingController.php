<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'seo_meta_title' => ['nullable', 'string', 'max:160'],
            'seo_meta_description' => ['nullable', 'string', 'max:300'],
            'seo_og_image' => ['nullable', 'url', 'max:255'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return back()->with('success', 'SEO settings berhasil diupdate.');
    }
}
