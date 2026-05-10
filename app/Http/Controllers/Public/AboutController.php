<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

class AboutController extends Controller
{
    public function index()
    {
        $timeline = [
            [
                'year' => '2024',
                'title' => 'Mulai Berproduksi',
                'desc' => 'Pelangi Lollycandy mulai menghadirkan permen colorful untuk kebutuhan keluarga, reseller, dan event.',
            ],
            [
                'year' => '2025',
                'title' => 'Varian Bertambah',
                'desc' => 'Pilihan bentuk, ukuran, dan kemasan dikembangkan agar pelanggan lebih mudah memilih produk.',
            ],
            [
                'year' => '2026',
                'title' => 'Marketplace Aktif',
                'desc' => 'Katalog dan pemesanan dibuat lebih mudah melalui marketplace dan kanal kontak resmi.',
            ],
        ];

        return view('public.about', compact('timeline'));
    }
}
