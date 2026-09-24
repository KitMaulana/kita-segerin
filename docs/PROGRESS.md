# Catatan Kemajuan — KITAA SEGERIN

Berkas ini dicatat setiap kali satu tahap di `CLAUDE.md` bagian G selesai.

---

## Tahap 1 — Fondasi proyek ✅

**Selesai:** 24 September 2026

### Yang dikerjakan

| Poin | Hasil |
|---|---|
| 1. Proyek Laravel + Breeze | Laravel **12.69.2**, Breeze **2.4** stack Blade |
| 2. `.env`, locale, bahasa validasi | MySQL `kitaa_segerin`, `Asia/Jakarta`, locale `id`, `lang/id/*` lengkap |
| 3. Hapus register, login username/email | Route & controller register dihapus; login pakai username **atau** email |
| 4. Font & warna | `@fontsource/plus-jakarta-sans` (lokal), token warna di `tailwind.config.js` |
| 5. Helper global | `rupiah()`, `tanggal_indo()`, `terbilang()` + `angka()`, `persen()`, `tanggal_singkat()` |
| 6. Layout | Sidebar (desktop) + navigasi bawah 5 menu (HP), halaman menu lain masih sementara |
| 7. Dokumentasi & git | `docs/PROGRESS.md`, repositori git diinisialisasi |

### Hasil uji

- `php artisan migrate:fresh --seed` — berjalan tanpa error
- `php artisan test` — **32 test hijau, 121 assertion**
- `rupiah(261000)` → `Rp261.000`
- `tanggal_indo('2026-09-23')` → `23 September 2026`
- `terbilang(261000)` → `dua ratus enam puluh satu ribu rupiah`
- Build Vite berhasil: 20 aturan `@font-face`, bobot 400–800, semua aset lokal

### Keputusan penting

1. **PHP 8.2.12, bukan 8.3+.** `CLAUDE.md` bagian C meminta PHP 8.3+, tetapi XAMPP di komputer ini
   memakai PHP 8.2.12. Laravel 12 mensyaratkan `^8.2`, jadi semuanya berjalan normal. Naikkan ke
   PHP 8.3/8.4 saat deploy (lihat `docs/DEPLOY.md` pada Tahap 11).
2. **Node.js 24.19.0 dipasang lewat winget** karena sebelumnya belum ada di komputer ini.
3. **Tailwind v3, bukan v4.** Breeze 2.4 menghasilkan `package.json` yang memuat `@tailwindcss/vite ^4`
   sekaligus `tailwindcss ^3`, padahal `vite.config.js` dan `resources/css/app.css` memakai gaya v3
   (`@tailwind base;` + `tailwind.config.js`). Paket v4 yang tidak terpakai dihapus supaya tidak bentrok,
   dan `postcss-import` ditambahkan agar `@import` font `@fontsource` diproses sebelum aturan Tailwind.
   Ini juga sesuai permintaan `CLAUDE.md` bagian F yang menyebut token warna ditaruh di `tailwind.config.js`.
4. **Kolom `username` sudah ditambahkan sekarang** (bukan menunggu Tahap 2) karena Tahap 1 mensyaratkan
   login memakai username. Kolom `email` dibuat **nullable** karena bagian E menyebutnya opsional.
   Kolom `role`, `is_active`, dan `last_login_at` menyusul pada Tahap 2.
5. **Seeder Tahap 1 hanya membuat akun pemilik** (`pemilik` / `ganti-segera`) supaya aplikasi bisa dibuka.
   Pengaturan usaha, kategori biaya, produk, pemasok, dan toko contoh dibuat pada Tahap 2.
6. **Verifikasi email dan pendaftaran mandiri dihapus** seluruhnya (controller, tampilan, rute, test),
   sesuai aturan bahwa akun hanya dibuat oleh pemilik.
7. **Hapus akun sendiri dihapus** dari halaman profil. Penonaktifan akun menjadi wewenang pemilik (Tahap 3).
8. **Komponen layout berbasis class Breeze diganti komponen anonim.** `app/View/Components/AppLayout.php`
   dan `GuestLayout.php` dihapus, digantikan `resources/views/components/app-layout.blade.php` dan
   `guest-layout.blade.php` supaya bisa menerima `title` dan `subtitle` langsung dari halaman.
9. **Struktur menu ditaruh di `config/navigation.php`** agar sidebar, navigasi bawah, dan halaman
   "Lainnya" membaca sumber yang sama. Kunci `roles` sudah disiapkan tetapi **belum ditegakkan** —
   penyaringan per peran dipasang pada Tahap 3.

### Yang masih tertunda

- [ ] **Penyaringan menu per peran** (`roles` di `config/navigation.php`) — Tahap 3
- [ ] **Middleware penolak akun nonaktif** — Tahap 3 (catatan sudah ditulis di `routes/web.php`)
- [ ] **Halaman sementara** untuk 13 menu — diganti bertahap, daftarnya ada di
      `app/Http/Controllers/PlaceholderController.php` beserta tahapnya
- [ ] Ikon PWA & service worker — Tahap 10

### Berkas penting Tahap 1

| Berkas | Isi |
|---|---|
| `app/Support/helpers.php` | Semua pembantu format (uang, tanggal, terbilang, menu) |
| `config/navigation.php` | Struktur menu sidebar, navigasi bawah, halaman "Lainnya" |
| `resources/views/components/app-layout.blade.php` | Rangka halaman setelah login |
| `resources/views/components/guest-layout.blade.php` | Rangka halaman masuk |
| `resources/views/layouts/partials/` | Sidebar, topbar, navigasi bawah, pesan flash |
| `tailwind.config.js` | Token warna frost/ink/berry/mint/strawberry/mango |
| `tests/Unit/HelperFormatTest.php` | Uji format uang, tanggal, terbilang |
| `tests/Feature/TataLetakTest.php` | Uji rangka halaman dan kelengkapan menu |

---

## Tahap 2 — Database, model, seeder ⏳

Belum dikerjakan.
