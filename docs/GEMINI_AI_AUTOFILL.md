# Gemini AI Product Autofill

## Alur

Modal Tambah dan Edit Produk memakai kontrol AI pada partial form yang sama. Foto tetap diunggah dan disimpan melalui alur CRUD yang sudah ada hanya setelah admin menekan tombol simpan. Analisis memakai `POST /admin/products/ai-autofill` dengan sesi admin, CSRF, batas 10 permintaan per menit, dan foto JPEG/PNG/WebP maksimal 4 MB. Endpoint membaca kategori aktif dari database lalu mengirim salinan gambar dan konteks nama/deskripsi ke Gemini. Bila ekstensi GD tersedia, salinan gambar besar dikecilkan sampai sisi terpanjang 1280 piksel. Foto katalog asli tidak diubah.

Backend memakai Laravel HTTP client ke Gemini Generate Content REST API dengan JSON Schema dan thinking level rendah untuk mengurangi waktu tunggu pada tugas katalog yang sederhana. Batas waktu request 45 detik. Hasil JSON divalidasi lagi; ID kategori di luar daftar aktif dihapus dan ditandai untuk diperiksa. AI hanya mengusulkan nama, kategori, dan deskripsi. Badge, harga, stok, tautan, dan status tetap di tangan admin. Request dan respons AI tidak disimpan ke database atau log aplikasi.

## Konfigurasi

1. Buat API key di [Google AI Studio](https://aistudio.google.com/app/apikey).
2. Isi `.env` lokal, jangan menaruh key di JavaScript atau commit:

   ```env
   GEMINI_API_KEY=isi_key_anda
   GEMINI_MODEL=gemini-3.5-flash-lite
   AI_AUTOFILL_ENABLED=true
   ```

3. Jika konfigurasi pernah di-cache, jalankan `php artisan config:clear`.

Model default `gemini-3.5-flash-lite` mendukung input gambar dan structured output serta tercantum sebagai model stabil Google dengan Free Tier. Model 2.5 Flash dibatasi untuk pengguna baru; karena itu key baru dapat menerima 404 walaupun key tercantum di AI Studio. Thinking level minimal membantu mengurangi latensi untuk tugas katalog sederhana. Jika model tidak tersedia, pilih model multimodal yang mendukung structured output dan tersedia untuk key tersebut. Jangan gunakan model khusus *image generation* untuk analisis ini. Tidak ada pengaturan billing yang diubah oleh fitur ini.

## Penggunaan

Buka `/admin/products`, pilih Tambah Produk atau Edit, lalu aktifkan **✨ AI Autofill** di bawah Upload Gambar. Toggle awalnya OFF; pilihan terakhir disimpan di localStorage per ID admin. Mengunggah foto ketika toggle ON langsung memulai analisis. Jika foto sudah dipilih sebelumnya, klik **✨ Analisis Foto Sekarang**. Tombol **✨ Analisis Ulang** tersedia setelah hasil AI. Admin dapat mengedit semua field. Sebelum menimpa field yang diubah manual, aplikasi meminta konfirmasi. Menonaktifkan toggle membatalkan request yang aktif dan mempertahankan isi form. Bila analisis gagal, form tetap dapat diisi manual.

## File

- `app/Services/GeminiProductAnalyzer.php`, `ProductAiException.php`: pemanggilan API, validasi, dan pemetaan kegagalan.
- `app/Http/Controllers/Admin/ProductAiController.php`, `routes/web.php`: endpoint admin.
- `resources/views/admin/products/*`, `resources/js/product-ai-autofill.js`, `resources/css/product-ai-autofill.css`: UI.
- `config/services.php`, `.env.example`: konfigurasi.
- `tests/Feature/ProductAiAutofillTest.php`: pengujian backend dengan HTTP fake.

## Menjalankan dan menguji

```sh
composer install
npm install
php artisan serve
npm run dev
php artisan test --filter=ProductAiAutofillTest
npm run build
```

Jalankan migrasi hanya pada database baru sesuai prosedur proyek. Jangan reset database yang berisi produk. Pengujian otomatis memakai SQLite in-memory dan HTTP fake, tanpa key atau kuota Gemini. Integrasi live harus dicoba sendiri setelah API key lokal dipasang.

## Masalah umum dan kuota

- **Key belum dikonfigurasi:** isi `GEMINI_API_KEY` di `.env`, lalu bersihkan cache konfigurasi.
- **Model tidak tersedia:** periksa akses model di AI Studio dan ubah `GEMINI_MODEL`.
- **429:** batas penggunaan/kuota tercapai; tunggu dan lihat pemakaian serta batas proyek di AI Studio.
- **Timeout/koneksi:** cek internet server, lalu gunakan Analisis Ulang.
- **Kategori tidak cocok:** pilih kategori aktif secara manual.

[Harga dan Free Tier](https://ai.google.dev/gemini-api/docs/pricing) serta [rate limit](https://ai.google.dev/gemini-api/docs/rate-limits) dapat berubah dan bergantung pada proyek, model, serta wilayah. Pantau batas dan pemakaian aktual di Google AI Studio. Fitur ini tidak menjamin akses Free Tier untuk setiap key.
