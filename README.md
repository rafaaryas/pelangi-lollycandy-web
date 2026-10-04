# Pelangi Lollycandy

Website katalog dan aplikasi operasional untuk **Pelangi Lollycandy**. Aplikasi ini memadukan halaman publik untuk menampilkan produk dengan panel admin untuk mengelola katalog, persediaan, pembelian, produksi, penjualan, dan laporan.

## Fitur

- Katalog produk publik dengan kategori, galeri, detail produk, serta tautan marketplace/WhatsApp.
- Halaman beranda dan kontak yang responsif.
- Login admin dan dashboard ringkasan operasional.
- Manajemen produk, kategori, tautan marketplace, bahan baku, pemasok, dan pelanggan.
- Pencatatan pembelian bahan, produksi, serta penjualan.
- Stok produk dan bahan baku dengan riwayat pergerakan, penyesuaian, dan peringatan stok rendah.
- Laporan operasional untuk membantu memantau persediaan dan transaksi.

## Teknologi

- PHP 8.3+
- Laravel 13
- Blade dan Vite 8
- Database MySQL/MariaDB atau database yang didukung Laravel
- PHPUnit untuk pengujian

## Menjalankan secara lokal

### Prasyarat

- PHP 8.3 atau lebih baru
- Composer
- Node.js dan npm
- MySQL/MariaDB (atau driver database Laravel yang dipilih)

### Instalasi

```bash
git clone https://github.com/rafaaryas/pelangi-lollycandy-web.git
cd pelangi-lollycandy-web
composer install
npm install
```

Buat konfigurasi lokal dari template, lalu isi nilai database dan layanan yang Anda gunakan.

```bash
copy .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

Jalankan aplikasi dan Vite pada dua terminal terpisah:

```bash
php artisan serve
```

```bash
npm run dev
```

Aplikasi akan tersedia di `http://127.0.0.1:8000` secara default. Panel admin tersedia pada `/admin/login`.

## Data demo

Seeder data demo tersedia untuk membantu pengembangan lokal:

```bash
php artisan db:seed --class=DemoBusinessDataSeeder
```

Seeder ini sengaja menolak dijalankan pada lingkungan `production`. Pastikan produk katalog sudah tersedia sebelum menjalankannya.

## Pengujian

```bash
php artisan test
```

## Keamanan konfigurasi

- Jangan commit `.env`, database lokal, log, atau berkas cadangan.
- Gunakan `.env.example` sebagai template; jangan menyimpan API key, kata sandi, atau data pelanggan asli di dalamnya.
- Setelah kredensial pernah masuk ke riwayat Git, rotasi kredensial tersebut—menambah `.gitignore` tidak menghapus nilai dari commit lama.

## Lisensi

Kode ini dikelola untuk Pelangi Lollycandy. Semua hak cipta dan ketentuan penggunaan mengikuti kebijakan pemilik proyek.
