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
- [x] Ikon PWA & service worker — selesai di Tahap 10

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

## Tahap 2 — Database, model, seeder ✅

**Selesai:** 24 September 2026

| Poin | Hasil |
|---|---|
| 1. Migration, model, relasi | 6 migration bertema (`core`, `master`, `purchase_and_stock`, `consignment`, `invoice`, `finance`) memuat seluruh tabel bagian E, lengkap dengan cast, relasi Eloquent, dan soft delete pada data master |
| 2. `FinanceCalculator` | Semua rumus bagian B + `resolvePrice(store, product, date)` |
| 3. `DocumentNumber` | Penomoran `BLI/KRM/INV/BYR` dengan `lockForUpdate` |
| 4. `StockService` | Stok gudang, stok dititipkan di toko, nilai persediaan |
| 5. Seeder | Akun `pemilik`, pengaturan usaha, 7 kategori biaya, 1 pemasok, 5 produk contoh, toko Koperasi Budi Utama |
| 6. Unit test | `FinanceCalculatorTest` memakai contoh uji bagian B, termasuk fee persen dan penolakan qty melebihi kiriman |

### Keputusan penting

1. **Stok tidak disimpan sebagai kolom angka.** Sesuai bagian E, stok gudang selalu `SUM(qty)` dari
   `stock_movements` lewat `StockService`, supaya tidak pernah selisih dengan riwayat mutasinya.
2. **Uang memakai `unsignedBigInteger`** di seluruh tabel; tidak ada kolom float/decimal untuk rupiah.
3. **Fee persen dibulatkan sekali di `FinanceCalculator`**, lalu hasil rupiahnya disalin (snapshot) ke
   `consignment_items.fee_per_unit`, supaya cetakan lama tidak pernah berubah angkanya.

---

## Tahap 3 — Akun admin & pengaturan usaha ✅

**Selesai:** 24 September 2026

| Poin | Hasil |
|---|---|
| 1. Menu Akun Admin | `UserController`: daftar, tambah, ubah, aktif/nonaktif, reset kata sandi, atur peran |
| 2. Hak akses | 9 Gate di `AuthServiceProvider` sesuai tabel peran bagian E + middleware `aktif` (`EnsureUserIsActive`) |
| 3. Halaman profil | Ubah nama & kata sandi sendiri |
| 4. Pengaturan Usaha | `SettingController`: identitas usaha, logo, rekening bank, penandatangan, jatuh tempo, catatan tagihan |
| 5. Log aktivitas | Pencatatan otomatis + halaman "Log Aktivitas" khusus pemilik |

### Keputusan penting

1. **Gate, bukan Policy.** Hak akses di bagian E berbasis peran (bukan kepemilikan baris), jadi 9 Gate
   bernama (`input-transaksi`, `kelola-tagihan`, `batalkan-tagihan`, `kelola-akun`, dll.) lebih ringkas
   daripada Policy per model. Blade memakai `@can` dengan nama Gate yang sama.
2. **Pemilik terakhir dilindungi**: pemilik tidak bisa menonaktifkan atau menurunkan peran dirinya sendiri,
   dan sistem menolak tindakan yang menyisakan nol pemilik aktif.
3. **Menu disaring per peran** — kunci `roles` di `config/navigation.php` yang disiapkan Tahap 1
   sekarang benar-benar ditegakkan di sidebar, navigasi bawah, dan halaman "Lainnya".

---

## Tahap 4 — Produk, pemasok, pembelian & stok ✅

**Selesai:** 24 September 2026

| Poin | Hasil |
|---|---|
| 1. CRUD Pemasok | `SupplierController` |
| 2. CRUD Produk | `ProductController` + pratinjau Alpine.js (setoran & laba per pcs saat mengetik harga) |
| 3. Riwayat harga modal | Tercatat ke `product_cost_histories`, tampil di detail produk |
| 4. Pembelian | `PurchaseController`: banyak baris, total otomatis, opsi "Perbarui harga modal", otomatis membuat `stock_movements` (+) dan kas keluar |
| 5. Menu Stok | `StockController`: stok gudang, stok di toko, tanda warna mango saat menipis, nilai persediaan, penyesuaian manual dengan alasan wajib |

### Keputusan penting

1. **Satu pembelian = satu `DB::transaction()`** yang menulis `purchases`, `purchase_items`,
   `stock_movements`, `cash_transactions`, dan (opsional) `product_cost_histories` sekaligus.
2. **Perubahan harga modal selalu berjejak**, baik dari form produk (`source: manual`) maupun dari
   pembelian (`source: purchase`).

---

## Tahap 5 — Toko, harga jual & fee ✅

**Selesai:** 24 September 2026

| Poin | Hasil |
|---|---|
| 1. CRUD Toko | `StoreController` (kode, nama, jenis, kontak, alamat, tempo bayar) |
| 2. Tab Harga & Fee | Tabel semua produk aktif: harga jual, jenis & nilai fee, setoran/pcs, laba/pcs dari `FinanceCalculator`; harga khusus toko punya tanggal mulai berlaku, yang kosong ditandai "default" |
| 3. Simulasi cepat | Masukkan jumlah terjual per produk → penjualan kotor, fee toko, setoran, laba |
| 4. Riwayat harga | Perubahan harga khusus per toko bisa ditelusuri |

### Keputusan penting

1. **Harga khusus tidak menimpa baris lama.** Mengubah harga toko membuat baris `store_prices` baru
   dengan `effective_from` sendiri; `resolvePrice()` memilih baris berlaku terakhir pada tanggal kirim.
   Baris-baris itu sekaligus menjadi riwayat harga per toko.

---

## Tahap 6 — Pengiriman titip jual & rekonsiliasi ✅

**Selesai:** 24 September 2026

| Poin | Hasil |
|---|---|
| 1. Buat pengiriman | `ConsignmentController`: validasi qty tidak melebihi stok gudang, snapshot `unit_cost`/`unit_price`/`fee_per_unit`, `stock_movements` (−) |
| 2. Cetak | Surat jalan PDF A4 (dompdf) + versi struk 58mm lewat CSS print, berkolom tanda tangan pengirim & penerima toko |
| 3. Rekonsiliasi | Isi terjual/retur/rusak, tombol "Terjual semua", `sisa_belum_dicatat` dihitung langsung di layar dan ditolak jika < 0; retur → `return_in` (+), rusak → `damaged`, status menjadi `settled` |
| 4. Ringkasan | Penjualan kotor, fee toko, setoran, HPP, laba kotor, kerugian rusak, tingkat laku (%) |
| 5. Daftar | Filter toko/status/rentang tanggal; kartu di HP, tabel di desktop |

### Keputusan penting

1. **`damaged` dicatat tanpa mengubah stok gudang** karena barangnya memang sudah keluar saat pengiriman —
   barisnya ada murni sebagai jejak kerugian, sesuai bagian G Tahap 6 poin 3.

---

## Tahap 7 — Tagihan setoran & pembayaran ✅

**Selesai:** 24 September 2026

| Poin | Hasil |
|---|---|
| 1. Buat tagihan | `InvoiceController`: pilih toko → daftar pengiriman `settled` yang belum ditagih → centang → `invoices` + `invoice_items` (snapshot); pengiriman menjadi `invoiced` dan terkunci |
| 2. Cetak | Tagihan PDF A4 berkop lengkap (terbilang, rekening, catatan, tanda tangan & stempel) + versi struk 58mm |
| 3. WhatsApp | Tombol `wa.me` berisi nomor tagihan, total, dan jatuh tempo |
| 4. Pembayaran | `PaymentController`: bertahap, bukti opsional, status `unpaid`/`partial`/`paid` otomatis, kas masuk otomatis, kuitansi bisa dicetak |
| 5. Batalkan tagihan | Khusus pemilik, alasan wajib; pengiriman kembali `settled`; ditolak bila sudah ada pembayaran |
| 6. Daftar tagihan | Filter status & toko, tanda warna strawberry saat lewat jatuh tempo |

### Keputusan penting

1. **`invoice_items` menyimpan `product_name` dan seluruh angka satuannya**, bukan sekadar `product_id`,
   supaya cetak ulang tagihan lama tetap sama walau produknya diubah atau dinonaktifkan.

---

## Tahap 8 — Pembukuan & rekap lengkap ✅

**Selesai:** 24 September 2026

| Poin | Hasil |
|---|---|
| 1. Biaya operasional | `ExpenseController` (CRUD + unggah bukti), otomatis masuk buku kas |
| 2. Buku Kas | `CashBookController` + `CashBookService`: saldo berjalan, filter periode & kategori, input manual modal pemilik/prive/lainnya |
| 3. Tujuh laporan | `ReportService`: `labaRugi`, `rekapProduk`, `rekapToko`, `piutang` (umur 0–7/8–14/15–30/>30 hari), `arusKas`, `persediaan`, `rekapPeriodePengiriman` — semuanya berfilter periode |
| 4. Ekspor | PDF (dompdf; laporan produk/toko/periode-pengiriman memakai A4 landscape) dan Excel `.xlsx` |
| 5. Feature test | `LaporanTest` (16 test) membandingkan total laporan laba rugi dengan data yang dibuatnya |

### Keputusan penting

1. **`openspout/openspout`, bukan `maatwebsite/excel`.** Bagian C mengizinkan penggantian ini bila ada
   masalah kompatibilitas. Ekspor ditulis lewat pembungkus tipis `App\Support\ExcelWriter` yang melakukan
   streaming ke output, jadi baris yang banyak tidak menumpuk di memori.
2. **Tidak ada perhitungan uang di Blade.** Seluruh angka laporan berasal dari query agregat di
   `ReportService`, sesuai bagian G Tahap 8 poin 5.
3. **Dua sumbu waktu dipisah tegas**: laba rugi memakai tanggal rekonsiliasi (`settled_date`),
   arus kas memakai tanggal uang benar-benar bergerak (`cash_transactions.date`).

---

## Tahap 9 — Beranda infografis ✅

**Selesai:** 24 September 2026

| Poin | Hasil |
|---|---|
| 1. Kartu ringkasan | Setoran & laba bersih bulan ini (dengan pembanding % bulan lalu), piutang belum dibayar, nilai persediaan, pcs terjual |
| 2–5. Grafik Chart.js | Tren setoran & laba 6 bulan terakhir, donat komposisi penjualan per produk, batang berujung bulat "tingkat laku" ala es krim stik, 5 toko dengan setoran terbesar |
| 6. Panel perhatian | Tagihan lewat jatuh tempo, stok menipis, pengiriman belum direkonsiliasi lebih dari 7 hari |
| 7. Tombol cepat | Catat pengiriman, Buat tagihan, Catat biaya |
| 8. Cetak infografis | `beranda-cetak.blade.php` dengan CSS print A4 landscape |

### Keputusan penting

1. **Cache 5 menit per bulan yang ditampilkan.** Cache dibersihkan otomatis oleh model event
   (`saved`/`deleted`) pada Purchase, StockMovement, Consignment, ConsignmentItem, Invoice, Payment,
   dan kawan-kawannya — didaftarkan di `AppServiceProvider`, sehingga transaksi baru langsung terlihat
   tanpa menunggu lima menit.
2. **Beranda sementara Tahap 1 diganti.** `PlaceholderController` dan `placeholder.blade.php` dihapus
   karena seluruh menu sudah punya halaman asli; `TataLetakTest` disesuaikan agar memeriksa kartu
   ringkasan dashboard, bukan angka contoh `Rp261.000` milik halaman sementara.

### Hasil uji Tahap 2–9

- `php artisan migrate:fresh --seed` — berjalan tanpa error
- `php artisan test` — **191 test hijau, 644 assertion**

### Yang masih tertunda

- [x] **Tahap 10 — PWA**: `manifest.webmanifest`, `sw.js`, ikon 192/512/maskable, halaman `/offline`,
      tombol "Pasang aplikasi"
- [ ] **Tahap 11** — feature test alur lengkap, audit keamanan (rate limit login, batas unggah berkas),
      fitur backup database, `docs/PANDUAN-PENGGUNA.md`, `docs/DEPLOY.md`

---

## Tahap 10 — PWA ✅

**Selesai:** 24 September 2026

| Poin | Hasil |
|---|---|
| 1. Manifest & ikon | `public/manifest.webmanifest` (name KITAA SEGERIN, short_name Segerin, start_url `/beranda`, standalone, theme berry, latar frost) + ikon 192, 512, maskable 512, dan apple-touch 180 |
| 2. Service worker | `public/sw.js`: cache-first untuk aset build/font/ikon, network-first untuk halaman, dan daftar larangan untuk laporan keuangan, dokumen cetak, serta seluruh permintaan non-GET |
| 3. Halaman offline | Route publik `/offline` + `resources/views/offline.blade.php` |
| 4. Pendaftaran & versi | `resources/js/pwa.js` mendaftarkan `/sw.js?v=<versi_aset()>`; versi berubah tiap build |
| 5. Tombol pasang | Bagian "Aplikasi" di halaman Lainnya: tombol pasang (Android/desktop), petunjuk Bagikan → Tambah ke Layar Utama (iPhone), dan keterangan bila sudah terpasang |
| 6. Meta tag | `theme-color`, `apple-touch-icon`, `apple-mobile-web-app-*` di kedua layout (setelah masuk maupun halaman masuk) |

### Keputusan penting

1. **Versi cache lewat query string, bukan menulis ulang `sw.js` saat build.**
   Service worker membaca versinya sendiri dari `new URL(self.location).searchParams.get('v')`,
   dan Blade mengisi `v` dari helper baru `versi_aset()` — 10 karakter pertama md5
   `public/build/manifest.json`. Setiap `npm run build` mengubah nilai itu, sehingga peramban
   melihat URL service worker yang berbeda, memasang yang baru, lalu membuang cache lama di
   tahap `activate`. Tidak perlu langkah build tambahan atau paket apa pun.
2. **Ikon digambar dengan GD lewat `php artisan pwa:ikon`.** Tidak ada Imagick maupun pengubah
   SVG→PNG di komputer ini, dan `@fontsource` hanya memuat woff/woff2 sehingga GD tidak bisa
   menulis teks. Karena itu bentuk es krim dan huruf KS digambar dari bangun dasar
   (persegi tumpul, gelombang sinus, goresan berujung bulat, dua busur untuk huruf S) pada kanvas
   4× lalu dikecilkan agar tepinya halus. Ikonnya jadi bisa dibuat ulang di komputer mana pun
   tanpa paket tambahan, dan bentuknya sama dengan `components/app-logo.blade.php`.
3. **Halaman `/offline` sengaja tidak memakai layout aplikasi.** Gayanya ditulis langsung di
   dalam berkas supaya tetap rapi walau berkas CSS hasil build belum sempat tersimpan, dan
   isinya tidak memuat data usaha apa pun karena halaman ini boleh dibuka tanpa masuk.
4. **Halaman terlarang tetap dilayani, hanya tidak disimpan.** Saat tidak ada koneksi, membuka
   `/laporan` tetap memunculkan halaman `/offline` buatan sendiri, bukan halaman error peramban.
   `/masuk` boleh disimpan, tetapi `/login` dan `/logout` masuk daftar larangan supaya token CSRF
   basi tidak pernah dipakai ulang.
5. **`public/.htaccess` diberi tipe `application/manifest+json`** untuk `.webmanifest` dan
   `Cache-Control: no-cache` untuk `sw.js`. Tanpa itu sebagian Apache di hosting mengirim
   manifest sebagai berkas unduhan sehingga aplikasi tidak bisa dipasang.

### Hasil uji

- `php artisan test` — **202 test hijau, 690 assertion** (11 di antaranya `tests/Feature/PwaTest.php`)
- `php artisan migrate:fresh --seed` — berjalan tanpa error
- `node --check` pada `public/sw.js` dan `resources/js/pwa.js` — sintaks valid
- 22 kasus aturan cache diperiksa satu per satu (mis. `/laporan/produk/pdf` dilarang,
  `/tagihan/9` boleh, `/tagihan/9/cetak` dilarang, `/laporankeuangan` tidak ikut terlarang) — semua benar
- `/manifest.webmanifest`, `/sw.js`, `/icons/icon-192.png`, `/offline` diuji lewat `php artisan serve` — semua 200 dengan tipe berkas yang benar

### Belum diverifikasi otomatis

- **Lighthouse belum dijalankan.** `npx lighthouse` gagal di Windows (galat penghapusan folder
  sementara), dan Lighthouse versi baru sudah menghapus kategori PWA. Pemeriksaan installable
  dan perilaku offline perlu dilakukan manual lewat Chrome DevTools → Application
  (lihat `docs/DEPLOY.md` pada Tahap 11).

### Catatan untuk nanti

- Halaman yang sudah tersimpan masih bisa dilihat saat offline walau pengguna sudah keluar.
  Risikonya kecil (aplikasi dipakai pemilik dan 1–3 admin di perangkat sendiri), tetapi kalau
  aplikasi nanti dipakai di HP bersama, cache halaman sebaiknya dihapus saat keluar.
- PWA hanya bisa dipasang lewat **HTTPS** atau **localhost**. Di hosting nanti wajib pasang
  sertifikat SSL lebih dulu — dicatat untuk `docs/DEPLOY.md` di Tahap 11.

### Berkas penting Tahap 10

| Berkas | Isi |
|---|---|
| `public/manifest.webmanifest` | Identitas aplikasi & daftar ikon |
| `public/sw.js` | Aturan cache dan halaman offline |
| `public/icons/` | 4 ikon hasil `php artisan pwa:ikon` |
| `app/Console/Commands/BuatIkonPwa.php` | Penggambar ikon (GD) |
| `resources/js/pwa.js` | Pendaftaran service worker & tombol pasang |
| `resources/views/offline.blade.php` | Halaman saat tidak ada koneksi |
| `app/Support/helpers.php` | Helper baru `versi_aset()` |
| `tests/Feature/PwaTest.php` | 11 test manifest, ikon, offline, meta tag, aturan sw.js |

---

## Tahap 11 — Uji akhir, keamanan, backup, online ⏳

Belum dikerjakan.
