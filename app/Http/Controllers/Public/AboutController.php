<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;

class AboutController extends Controller
{
    public function index()
    {
        $timeline = [
            ['year' => '2019', 'title' => 'Brand Lahir', 'desc' => 'Pelangi Lollycandy dimulai dari produk rumahan premium.'],
            ['year' => '2021', 'title' => 'Skala Nasional', 'desc' => 'Masuk berbagai marketplace dan reseller.'],
            ['year' => '2024', 'title' => 'Inovasi Varian', 'desc' => 'Rilis seri playful untuk keluarga modern.'],
        ];
        $testimonials = Testimonial::query()->where('is_active', true)->latest()->take(4)->get();

        return view('public.about', compact('timeline', 'testimonials'));
    }
}
