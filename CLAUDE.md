# CLAUDE.md — Sistem Keuangan KITAA SEGERIN

> File ini dibaca otomatis oleh Claude Code setiap sesi. Bagian **A–F** adalah konteks dan aturan
> permanen proyek. Bagian **G** berisi prompt per tahap yang ditempel pengguna ke chat Claude Code
> satu per satu. Jangan mengerjakan tahap berikutnya sebelum tahap yang sedang berjalan dinyatakan selesai.

---

## Cara pakai (untuk pengguna)

1. Pasang: PHP 8.3+, Composer, Node.js LTS, MySQL/MariaDB (paling mudah pakai **Laragon**), VS Code, dan ekstensi **Claude Code**.
2. Buat folder kosong, misalnya `C:\laragon\www\kitaa-segerin`, lalu simpan file `CLAUDE.md` ini di dalamnya.
3. Buka folder itu di VS Code, buka panel Claude Code.
4. Salin prompt **Tahap 1** dari bagian G, tempel, kirim. Tunggu sampai selesai, cek hasilnya di browser.
5. Kalau sudah sesuai, lanjut ke tahap berikutnya. Kalau ada yang salah, jelaskan ke Claude apa yang salah sebelum lanjut.
6. Setelah setiap tahap berhasil, lakukan commit git (Claude akan menawarkan perintahnya).
7. Tips: kalau sesi terasa panjang/lambat, ketik `/clear` lalu mulai tahap berikutnya — konteks proyek tetap terbaca dari file ini dan dari `docs/PROGRESS.md`.

---

## A. Konteks usaha

**KITAA SEGERIN** adalah usaha pemasok es krim (reseller). Produk diambil langsung dari perusahaan/distributor
es krim, disimpan di freezer gudang, lalu dititipkan ke **koperasi sekolah, kantin, dan toko-toko**
dengan sistem **titip jual (konsinyasi)**.

Alur bisnis nyata:

1. **Kulakan** — beli es krim dari pemasok dengan **harga modal** per pcs → stok gudang bertambah.
2. **Pengiriman titip jual** — es krim dikirim ke toko (tercatat jumlah kirim per produk) → stok gudang berkurang, barang kini "dititipkan" di toko.
3. **Rekonsiliasi** — pada akhir periode (biasanya mingguan) dihitung per produk: **terjual**, **ditarik/retur** (kembali ke gudang), dan **rusak/meleleh**.
4. **Tagihan setoran** — toko menyetor hasil penjualan dikurangi **fee toko** per pcs yang laku.
5. **Pembayaran** — toko membayar tagihan (tunai/transfer/QRIS), bisa lunas sekaligus atau bertahap.
6. **Pembukuan** — semua uang masuk-keluar tercatat di buku kas, ditambah biaya operasional (bensin, dry ice, listrik freezer, kemasan, dll.).

Contoh produk (untuk seeder): Sutaco Taro, Sumico Millo, 143 Cup Strawberry, Semangka Nanas, Piscok Krispi.
Contoh toko (untuk seeder): Koperasi Budi Utama (jenis: koperasi sekolah).
Harga di seeder hanya contoh — pengguna akan mengubahnya lewat aplikasi.

Pengguna aplikasi: pemilik usaha dan 1–3 admin. Sering dipakai dari HP saat mengantar barang ke toko,
jadi **tampilan mobile adalah prioritas utama**.

---

## B. Rumus perhitungan (WAJIB diikuti, semua dalam Rupiah bulat)

Definisi per pcs:

| Istilah | Arti |
|---|---|
| Harga modal | Harga beli dari pemasok per pcs |
| Harga jual | Harga jual es krim di toko (harga yang disepakati dengan toko) per pcs |
| Fee toko | Bagian untuk toko per pcs yang **laku**. Bisa **nominal** (mis. Rp500) atau **persen** dari harga jual (mis. 10%) — hasil persen dibulatkan ke rupiah terdekat |
| Setoran per pcs | Harga jual − fee toko |
| Laba per pcs | Setoran per pcs − harga modal |

Per baris produk dalam satu pengiriman:

```
sisa_belum_dicatat = qty_kirim − (qty_terjual + qty_retur + qty_rusak)   // harus ≥ 0
penjualan_kotor    = qty_terjual × harga_jual
total_fee_toko     = qty_terjual × fee_per_pcs
setoran            = penjualan_kotor − total_fee_toko      // ini yang ditagih ke toko
hpp                = qty_terjual × harga_modal
laba_kotor         = setoran − hpp
kerugian_rusak     = qty_rusak × harga_modal               // ditanggung KITAA SEGERIN
```

Per periode:

```
laba_bersih = Σ laba_kotor − Σ kerugian_rusak − Σ biaya_operasional
```

Contoh uji (angka fiktif, dipakai di unit test):
harga modal 2.500, harga jual 5.000, fee nominal 500, kirim 71, terjual 58, retur 13, rusak 0
→ penjualan kotor 290.000; fee 29.000; setoran 261.000; HPP 145.000; laba kotor 116.000.

Aturan penting:
- **Snapshot harga**: saat pengiriman dibuat, harga modal, harga jual, dan fee per pcs **disalin** ke baris item pengiriman. Perubahan harga di master data TIDAK boleh mengubah transaksi lama.
- Harga jual & fee diambil dari **harga khusus toko** jika ada (berlaku pada tanggal kirim), jika tidak ada pakai **harga default produk**.
- Laporan laba-rugi memakai **tanggal rekonsiliasi** (saat penjualan tercatat). Laporan kas memakai **tanggal uang benar-benar masuk/keluar**.
- Barang titipan yang belum direkonsiliasi tetap dihitung sebagai **persediaan milik KITAA SEGERIN** (stok di toko).
- Seluruh perhitungan uang ditaruh di satu tempat: `app/Services/FinanceCalculator.php`, dan wajib punya unit test.

---

## C. Teknologi

- **Laravel** versi stabil terbaru (12.x atau lebih baru), PHP 8.3+
- **Laravel Breeze** (stack Blade) untuk login/profil — tanpa fitur register publik
- **Blade + Tailwind CSS + Alpine.js** (bawaan Breeze), build pakai Vite
- **MySQL/MariaDB**
- **Chart.js** (npm) untuk infografis
- **barryvdh/laravel-dompdf** untuk PDF (tagihan, surat jalan, laporan)
- **maatwebsite/excel** untuk ekspor Excel (jika tidak kompatibel dengan versi Laravel, pakai `openspout/openspout`)
- **Font Plus Jakarta Sans** via `@fontsource/plus-jakarta-sans` (dipasang lokal, bukan CDN, supaya PWA tetap tampil saat offline)
- PWA dibuat manual: `public/manifest.webmanifest` + `public/sw.js` (tanpa paket pihak ketiga)
- Testing: Pest atau PHPUnit (pakai bawaan instalasi)

Konfigurasi wajib di `.env` / `config/app.php`:
`APP_NAME="KITAA SEGERIN"`, `APP_TIMEZONE=Asia/Jakarta`, `APP_LOCALE=id`, `APP_FAKER_LOCALE=id_ID`, Carbon locale `id`.

---

## D. Aturan kerja untuk Claude

1. **Bahasa**: seluruh teks antarmuka, pesan validasi, dan pesan error dalam **Bahasa Indonesia** yang baku dan sederhana. Komentar kode boleh Bahasa Indonesia.
2. **Penamaan kode**: nama tabel, kolom, model, dan class dalam **Bahasa Inggris** (sesuai konvensi Laravel). URL/route memakai slug Bahasa Indonesia (`/produk`, `/toko`, `/pengiriman`, `/tagihan`). Lihat glosarium di bagian E.
3. **Uang** disimpan sebagai `unsignedBigInteger` (Rupiah tanpa desimal). Jangan pakai float/decimal untuk uang. Tampilkan dengan helper `rupiah($angka)` → `Rp1.250.000`.
4. **Tanggal** tampil format Indonesia: `23 September 2026`. Input tanggal pakai `<input type="date">`.
5. Setiap operasi yang menulis ke lebih dari satu tabel wajib dibungkus `DB::transaction()`.
6. Validasi pakai **Form Request**. Otorisasi pakai **Policy/Gate** sesuai peran.
7. Penomoran dokumen otomatis dan unik, dibuat dengan `lockForUpdate` agar tidak dobel:
   - Pembelian `BLI/2026/09/0001`, Pengiriman `KRM/2026/09/0001`, Tagihan `INV/KS/2026/09/0001`, Pembayaran `BYR/2026/09/0001`.
8. Data master (produk, toko, pemasok) memakai **soft delete**; produk/toko yang sudah punya transaksi hanya boleh **dinonaktifkan**, tidak dihapus.
9. Transaksi yang sudah masuk tagihan **terkunci** (tidak bisa diedit). Untuk koreksi, tagihan harus dibatalkan dulu oleh Pemilik.
10. Semua halaman (kecuali login dan halaman offline) wajib login.
11. Setelah menyelesaikan tiap tahap: jalankan `php artisan migrate:fresh --seed` (hanya di lokal) dan `php artisan test`, lalu perbarui `docs/PROGRESS.md` (tahap yang selesai, keputusan penting, hal yang tertunda) dan tawarkan perintah `git commit`.
12. Jangan memasang paket baru di luar daftar bagian C tanpa menjelaskan alasannya dan meminta persetujuan.
13. Kerjakan **hanya** tahap yang diminta. Jika menemukan hal ambigu, tanyakan singkat sebelum menulis banyak kode.

---

## E. Struktur data

### Glosarium

| Istilah di UI | Tabel |
|---|---|
| Pengguna / Akun admin | `users` |
| Pengaturan usaha | `settings` |
| Pemasok | `suppliers` |
| Produk / Es krim | `products` |
| Riwayat harga modal | `product_cost_histories` |
| Toko / Mitra | `stores` |
| Harga khusus toko | `store_prices` |
| Pembelian / Kulakan | `purchases`, `purchase_items` |
| Mutasi stok | `stock_movements` |
| Pengiriman titip jual | `consignments`, `consignment_items` |
| Tagihan setoran | `invoices`, `invoice_items` |
| Pembayaran | `payments` |
| Kategori biaya & Biaya operasional | `expense_categories`, `expenses` |
| Buku kas | `cash_transactions` |
| Log aktivitas | `activity_logs` |

### Skema tabel

- **users**: name, username (unik), email (nullable), password, role enum(`owner`,`admin`,`viewer`), is_active, last_login_at
- **settings**: key (unik), value (text) — berisi nama_usaha, alamat, telepon, email, logo_path, bank_nama, bank_nomor_rekening, bank_atas_nama, kota_ttd, nama_penandatangan, jatuh_tempo_hari (default 7), catatan_tagihan
- **suppliers**: name, contact_person, phone, address, notes, is_active, soft deletes
- **products**: code (unik, mis. `ES-001`), name, supplier_id (nullable), variant (cup/stik/cone/lainnya), unit (default `pcs`), cost_price, default_selling_price, default_fee_type enum(`nominal`,`percent`), default_fee_value, min_stock, photo_path, is_active, soft deletes
- **product_cost_histories**: product_id, old_price, new_price, source enum(`manual`,`purchase`), user_id, created_at
- **stores**: code (unik, mis. `TK-001`), name, type enum(`koperasi`,`kantin`,`toko`,`minimarket`,`lainnya`), contact_person, phone, address, payment_term_days (nullable, pakai default settings), is_active, notes, soft deletes
- **store_prices**: store_id, product_id, selling_price, fee_type, fee_value, effective_from (date); unik (store_id, product_id, effective_from)
- **purchases**: number, supplier_id, purchase_date, total, notes, update_cost_price (bool), user_id
- **purchase_items**: purchase_id, product_id, qty, unit_cost, subtotal
- **stock_movements**: product_id, date, type enum(`purchase_in`,`consignment_out`,`return_in`,`damaged`,`adjustment`), qty (bertanda: + masuk, − keluar), reference (morph nullable), notes, user_id
  → Stok gudang = SUM(qty) per produk. Jangan simpan stok sebagai angka terpisah yang bisa selisih.
- **consignments**: number, store_id, sent_date, settled_date (nullable), status enum(`sent`,`settled`,`invoiced`,`cancelled`), invoice_id (nullable), notes, user_id
- **consignment_items**: consignment_id, product_id, qty_sent, qty_sold, qty_returned, qty_damaged, unit_cost, unit_price, fee_per_unit (snapshot dalam rupiah)
- **invoices**: number, store_id, invoice_date, due_date, period_start, period_end, gross_total, fee_total, amount_due, amount_paid, status enum(`unpaid`,`partial`,`paid`,`cancelled`), notes, user_id
- **invoice_items** (snapshot untuk cetak ulang yang konsisten): invoice_id, consignment_id, product_id, product_name, qty_sold, unit_price, fee_per_unit, net_per_unit, subtotal
- **payments**: number, invoice_id, paid_at, amount, method enum(`cash`,`transfer`,`qris`), reference, proof_path, user_id
- **expense_categories**: name, is_active — seeder: Transportasi/BBM, Dry ice & es batu, Listrik freezer, Kemasan & plastik, Upah/gaji, Perawatan freezer, Lain-lain
- **expenses**: date, expense_category_id, amount, description, proof_path, user_id
- **cash_transactions**: date, direction enum(`in`,`out`), category enum(`sales_payment`,`purchase`,`expense`,`owner_capital`,`owner_withdrawal`,`other`), amount, description, reference (morph nullable), user_id
  → Pembayaran tagihan, pembelian, dan biaya **otomatis** membuat baris buku kas. Modal pemilik, prive, dan lain-lain diinput manual.
- **activity_logs**: user_id, action, subject (morph), description, ip_address, created_at

### Peran & hak akses

| Fitur | owner (Pemilik) | admin | viewer (Pemantau) |
|---|---|---|---|
| Beranda & laporan | ✓ | ✓ | ✓ |
| Input produk, toko, pembelian, pengiriman, rekonsiliasi, biaya | ✓ | ✓ | – |
| Buat tagihan & catat pembayaran | ✓ | ✓ | – |
| Ubah harga modal / harga jual / fee | ✓ | ✓ (tercatat di log) | – |
| Batalkan tagihan, hapus transaksi | ✓ | – | – |
| Kelola akun admin, pengaturan usaha | ✓ | – | – |

---

## F. Panduan tampilan

Arah desain: segar dan dingin seperti isi freezer es krim, tapi tetap serius untuk urusan uang.

- Warna (definisikan sebagai token Tailwind di `tailwind.config.js`):
  - `frost` #EEF6FA — latar halaman
  - `ink` #1D2B36 — teks utama
  - `berry` #5B2A6E — warna utama (header, tombol utama, menu aktif)
  - `mint` #12A383 — angka positif, laba, status lunas
  - `strawberry` #D9435A — rugi, jatuh tempo, rusak
  - `mango` #E89B2D — peringatan (stok menipis, bayar sebagian)
- Font: Plus Jakarta Sans untuk semua teks. Angka uang dan jumlah memakai `tabular-nums` dan rata kanan di tabel.
- Mobile: navigasi bawah berisi 5 menu (Beranda, Pengiriman, Tagihan, Laporan, Lainnya). Desktop: sidebar kiri.
- Satu elemen khas saja: grafik "tingkat laku" per produk (terjual ÷ kirim) digambar sebagai batang berujung bulat seperti es krim stik. Bagian lain tetap tenang dan rapi.
- Form panjang dipecah per langkah di HP. Tombol aksi memakai kata kerja jelas: "Simpan pengiriman", "Buat tagihan", "Catat pembayaran".
- Halaman kosong memberi arahan, mis. "Belum ada pengiriman. Catat pengiriman pertama untuk mulai menghitung setoran."
- Aksesibilitas: fokus keyboard terlihat, kontras cukup, ukuran sentuh minimal 44px, hormati `prefers-reduced-motion`.

---

## G. Tahapan pengerjaan (prompt untuk ditempel)

### Tahap 1 — Fondasi proyek

```
Baca CLAUDE.md. Kerjakan Tahap 1:
1. Buat proyek Laravel baru di folder ini (versi stabil terbaru), pasang Breeze stack Blade.
2. Atur .env untuk MySQL database "kitaa_segerin", timezone Asia/Jakarta, locale id. Pasang file bahasa Indonesia untuk pesan validasi.
3. Hapus route & tampilan register publik. Login memakai username ATAU email.
4. Pasang font Plus Jakarta Sans lewat @fontsource, token warna di tailwind.config.js sesuai bagian F.
5. Buat helper global: rupiah(), tanggal_indo(), terbilang() (angka ke kata Rupiah, mis. "dua ratus enam puluh satu ribu rupiah").
6. Buat layout utama: sidebar (desktop) dan navigasi bawah (mobile) dengan menu sesuai bagian G (isi halaman boleh placeholder dulu).
7. Buat docs/PROGRESS.md dan inisialisasi git.
Setelah selesai, jelaskan cara menjalankan (php artisan serve + npm run dev).
```
**Selesai jika**: bisa login, layout tampil rapi di HP & laptop, helper `rupiah(261000)` menghasilkan `Rp261.000`.

### Tahap 2 — Database, model, seeder

```
Kerjakan Tahap 2 sesuai bagian E di CLAUDE.md:
1. Buat semua migration, model, relasi Eloquent, cast, dan soft delete.
2. Buat App\Services\FinanceCalculator berisi semua rumus bagian B, plus fungsi resolvePrice(store, product, date) untuk mengambil harga khusus toko atau harga default.
3. Buat App\Services\DocumentNumber untuk penomoran otomatis bagian D poin 7.
4. Buat App\Services\StockService (stok gudang, stok di toko/titipan, nilai persediaan).
5. Seeder: 1 akun owner (username: pemilik, password: ganti-segera), settings default KITAA SEGERIN, kategori biaya, 5 produk contoh, 1 pemasok contoh, toko Koperasi Budi Utama.
6. Unit test untuk FinanceCalculator memakai contoh uji di bagian B, termasuk fee persen dan validasi qty yang melebihi kiriman.
Jalankan migrate:fresh --seed dan php artisan test.
```
**Selesai jika**: semua test hijau, seeder berjalan tanpa error.

### Tahap 3 — Kelola akun admin & pengaturan usaha

```
Kerjakan Tahap 3:
1. Menu "Akun Admin" (khusus owner): daftar, tambah, ubah, nonaktifkan/aktifkan, reset password, atur peran (owner/admin/viewer). Owner tidak bisa menonaktifkan dirinya sendiri, dan minimal harus selalu ada 1 owner aktif.
2. Middleware/Gate sesuai tabel peran di bagian E. Akun nonaktif tidak bisa login.
3. Halaman profil: ubah nama & password sendiri.
4. Menu "Pengaturan Usaha" (khusus owner): nama usaha, alamat, telepon, logo, rekening bank, kota & nama penandatangan, jatuh tempo default, catatan kaki tagihan.
5. Log aktivitas otomatis untuk aksi tambah/ubah/hapus penting, dengan halaman "Log Aktivitas" (owner).
```
**Selesai jika**: admin tidak bisa membuka menu akun; viewer tidak melihat tombol tambah/ubah.

### Tahap 4 — Produk, harga modal, pemasok, pembelian & stok

```
Kerjakan Tahap 4:
1. CRUD Pemasok.
2. CRUD Produk: kode, nama, foto, varian, pemasok, harga modal, harga jual default, fee default (nominal/persen), stok minimum, aktif/nonaktif. Tampilkan pratinjau langsung (Alpine.js) setoran per pcs dan laba per pcs saat mengetik harga.
3. Setiap perubahan harga modal tercatat di product_cost_histories dan bisa dilihat di detail produk.
4. Menu Pembelian (kulakan): pilih pemasok, tanggal, banyak baris produk (qty & harga modal satuan), total otomatis. Opsi "Perbarui harga modal produk". Menyimpan pembelian otomatis membuat stock_movements (+) dan cash_transactions (keluar).
5. Menu Stok: stok gudang, stok dititipkan di toko, stok minimum (tandai warna mango jika menipis), nilai persediaan. Fitur penyesuaian stok manual dengan alasan wajib diisi.
```
**Selesai jika**: membeli 100 pcs membuat stok gudang +100 dan buku kas keluar sesuai total.

### Tahap 5 — Toko, harga jual & fee

```
Kerjakan Tahap 5:
1. CRUD Toko/Mitra (kode, nama, jenis, kontak, alamat, tempo bayar).
2. Di detail toko, tab "Harga & Fee": tabel semua produk aktif berisi harga jual, jenis fee, nilai fee, setoran per pcs, laba per pcs (dihitung FinanceCalculator). Bisa ubah harga khusus toko dengan tanggal mulai berlaku; jika kosong pakai harga default produk (tandai "default").
3. Simulasi cepat di halaman yang sama: masukkan jumlah terjual per produk → tampil perkiraan penjualan kotor, fee toko, setoran, dan laba.
4. Riwayat perubahan harga per toko.
```
**Selesai jika**: fee persen 10% dari Rp5.000 menampilkan fee Rp500 dan setoran Rp4.500.

### Tahap 6 — Pengiriman titip jual & rekonsiliasi

```
Kerjakan Tahap 6:
1. Menu Pengiriman: buat pengiriman (toko, tanggal, baris produk & qty kirim). Validasi qty tidak melebihi stok gudang. Saat disimpan: snapshot unit_cost, unit_price, fee_per_unit ke item, dan stock_movements (−).
2. Cetak Surat Jalan / Nota Titip (PDF A4 dan versi struk 58mm via CSS print) dengan kolom tanda tangan pengirim & penerima toko.
3. Rekonsiliasi: di halaman detail pengiriman, isi terjual, retur, rusak per produk. Tombol "Terjual semua" untuk mengisi cepat. Tampilkan sisa_belum_dicatat secara langsung; tidak boleh simpan jika sisa < 0. Saat disimpan: retur → stock_movements (+) jenis return_in, rusak → stock_movements jenis damaged (tanpa mengubah stok gudang karena sudah keluar), status menjadi settled.
4. Ringkasan per pengiriman: penjualan kotor, fee toko, setoran, HPP, laba kotor, kerugian rusak, tingkat laku (%).
5. Daftar pengiriman dengan filter toko, status, rentang tanggal. Tampilan HP berupa kartu, desktop berupa tabel.
```
**Selesai jika**: contoh uji bagian B menghasilkan angka yang sama di layar.

### Tahap 7 — Tagihan setoran & pembayaran

```
Kerjakan Tahap 7:
1. Buat tagihan: pilih toko → tampil semua pengiriman berstatus settled yang belum ditagih → centang yang mau ditagih → sistem menghitung total dan membuat invoice + invoice_items (snapshot). Pengiriman berubah status invoiced dan terkunci.
2. Cetak tagihan PDF A4: kop usaha (logo, alamat, telepon), nomor & tanggal tagihan, jatuh tempo, data toko, tabel (produk, terjual, harga jual, fee/pcs, setoran/pcs, subtotal), total penjualan kotor, total fee toko, TOTAL SETORAN, terbilang, info rekening, catatan, kolom tanda tangan & stempel. Sediakan juga versi struk 58mm.
3. Tombol "Kirim via WhatsApp": membuka wa.me dengan nomor toko dan ringkasan tagihan (nomor, total, jatuh tempo).
4. Catat pembayaran (bisa bertahap, unggah bukti opsional). Status otomatis unpaid/partial/paid. Pembayaran otomatis masuk buku kas. Cetak kuitansi pembayaran.
5. Batalkan tagihan (owner saja, alasan wajib): pengiriman terkait kembali ke status settled; tidak bisa dibatalkan jika sudah ada pembayaran.
6. Daftar tagihan dengan filter status & toko, tandai strawberry jika lewat jatuh tempo.
```
**Selesai jika**: tagihan tercetak rapi di A4, terbilang benar, pembayaran sebagian membuat status "Sebagian".

### Tahap 8 — Pembukuan & rekap lengkap

```
Kerjakan Tahap 8:
1. Biaya operasional: CRUD biaya dengan kategori, unggah bukti; otomatis masuk buku kas.
2. Buku Kas: daftar transaksi masuk/keluar dengan saldo berjalan, filter periode & kategori, input manual untuk modal pemilik, prive, dan lainnya.
3. Menu Laporan dengan filter periode (hari ini, minggu ini, bulan ini, rentang bebas):
   a. Laba Rugi: penjualan kotor, fee toko, setoran, HPP, laba kotor, kerugian rusak, biaya operasional per kategori, laba bersih.
   b. Rekap per Produk: kirim, terjual, retur, rusak, tingkat laku, setoran, laba, margin %.
   c. Rekap per Toko: jumlah pengiriman, terjual, setoran, fee dibayar ke toko, laba, piutang.
   d. Piutang & umur piutang (0–7, 8–14, 15–30, >30 hari).
   e. Arus Kas: saldo awal, total masuk, total keluar, saldo akhir.
   f. Persediaan: stok gudang, stok di toko, nilai persediaan.
   g. Rekap Periode Pengiriman (format mingguan): per produk kirim-terjual-sisa-keuntungan, sama seperti laporan mingguan manual yang biasa dibuat.
4. Setiap laporan bisa dicetak PDF dan diekspor Excel.
5. Semua angka laporan diambil lewat FinanceCalculator / query agregat, bukan hitung ulang di Blade. Tambahkan feature test yang membandingkan total laporan laba rugi dengan data seeder.
```
**Selesai jika**: laba bersih di laporan = Σ laba kotor − rusak − biaya, dan cocok dengan hitungan manual.

### Tahap 9 — Beranda infografis

```
Kerjakan Tahap 9: halaman Beranda (dashboard) dengan Chart.js, filter bulan:
1. Kartu ringkasan: setoran bulan ini, laba bersih bulan ini (bandingkan % dengan bulan lalu), piutang belum dibayar, nilai persediaan, jumlah pcs terjual.
2. Grafik garis: tren setoran & laba 6 bulan terakhir.
3. Grafik donat: komposisi penjualan per produk.
4. Elemen khas: tingkat laku per produk sebagai batang berujung bulat seperti es krim stik (bagian F).
5. Grafik batang: 5 toko dengan setoran terbesar.
6. Panel perhatian: tagihan lewat jatuh tempo, stok menipis, pengiriman yang belum direkonsiliasi lebih dari 7 hari.
7. Tombol cepat: Catat pengiriman, Buat tagihan, Catat biaya.
8. Tombol "Cetak infografis" dengan CSS print yang rapi di A4 landscape.
Pastikan query efisien (agregat, tanpa N+1) dan data dashboard di-cache 5 menit, dihapus cache-nya setiap ada transaksi baru.
```
**Selesai jika**: beranda terbuka < 1 detik dengan data contoh dan terbaca jelas di layar HP.

### Tahap 10 — PWA

```
Kerjakan Tahap 10:
1. Buat public/manifest.webmanifest (name "KITAA SEGERIN", short_name "Segerin", theme_color berry, background_color frost, display standalone, start_url "/beranda") dan ikon 192, 512, serta maskable. Buat ikon sederhana berbentuk es krim stik dengan huruf KS.
2. Buat public/sw.js: cache-first untuk aset statis hasil build Vite, font, dan ikon; network-first untuk halaman HTML; JANGAN menyimpan cache untuk halaman laporan keuangan, request POST, dan file PDF. Halaman /offline tampil saat tidak ada koneksi.
3. Daftarkan service worker di layout, versi cache otomatis berubah setiap build.
4. Tampilkan tombol "Pasang aplikasi" (event beforeinstallprompt) dan petunjuk untuk iPhone (Bagikan → Tambah ke Layar Utama).
5. Meta tag apple-touch-icon & theme-color.
Jelaskan bahwa PWA hanya bisa dipasang di HTTPS atau localhost, dan cara mengujinya di Chrome DevTools → Application → Lighthouse.
```
**Selesai jika**: Lighthouse menyatakan aplikasi installable, dan saat offline muncul halaman offline, bukan error.

### Tahap 11 — Uji akhir, keamanan, backup, online

```
Kerjakan Tahap 11:
1. Jalankan seluruh test, perbaiki yang gagal. Tambahkan feature test alur lengkap: pembelian → pengiriman → rekonsiliasi → tagihan → pembayaran → laporan.
2. Audit keamanan: semua route punya middleware auth & otorisasi yang benar, CSRF aktif, upload file dibatasi (jpg/png/pdf, maks 2 MB), rate limit login.
3. Fitur backup database (owner): unduh file .sql lewat menu Pengaturan, dan perintah artisan untuk backup terjadwal.
4. Buat docs/PANDUAN-PENGGUNA.md (bahasa sederhana: cara input produk, kirim barang, rekonsiliasi, buat tagihan, catat pembayaran, baca laporan).
5. Buat docs/DEPLOY.md: langkah upload ke hosting cPanel/VPS, set .env produksi (APP_DEBUG=false), php artisan optimize, storage:link, dan aktifkan HTTPS agar PWA bisa dipasang.
```
**Selesai jika**: alur lengkap berjalan tanpa error dan panduan pengguna bisa diikuti orang lain.

---

## H. Ide pengembangan berikutnya (jangan dikerjakan kecuali diminta)

- Hutang ke pemasok (pembelian tempo) dan jatuh temponya
- Target penjualan bulanan per toko
- Portal baca-saja untuk toko (melihat tagihan & riwayat setoran sendiri)
- Pengingat tagihan otomatis via WhatsApp gateway
- Pemindai barcode produk dari kamera HP saat pengiriman
