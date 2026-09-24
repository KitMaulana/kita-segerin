# KITAA SEGERIN

Sistem keuangan untuk usaha pemasok es krim dengan sistem **titip jual (konsinyasi)**:
kulakan dari pemasok → dikirim ke koperasi sekolah/kantin/toko → rekonsiliasi terjual,
retur, dan rusak → tagihan setoran → pembayaran → pembukuan.

Aturan bisnis, rumus perhitungan, skema tabel, dan tahapan pengerjaan ada di [CLAUDE.md](CLAUDE.md).
Catatan kemajuan tiap tahap ada di [docs/PROGRESS.md](docs/PROGRESS.md).

## Kebutuhan

| Perangkat lunak | Versi di komputer ini |
|---|---|
| PHP | 8.2.12 (XAMPP) — minimal `^8.2` |
| Composer | 2.10.3 |
| Node.js | 24.19.0 |
| MySQL / MariaDB | dari XAMPP |

## Menjalankan aplikasi

Pastikan **Apache** dan **MySQL** di panel XAMPP sudah menyala, lalu buka dua terminal
di folder proyek ini.

**Terminal 1 — server PHP:**

```bash
php artisan serve
```

**Terminal 2 — pembangun tampilan (CSS & JavaScript):**

```bash
npm run dev
```

Buka <http://localhost:8000> di peramban.

> `npm run dev` hanya diperlukan selama pengembangan agar perubahan tampilan langsung terlihat.
> Untuk dipakai sehari-hari atau di hosting, jalankan `npm run build` sekali saja.

## Masuk pertama kali

| Isian | Nilai |
|---|---|
| Username | `pemilik` |
| Kata sandi | `ganti-segera` |

Segera ganti kata sandi lewat menu **Profil Saya**.

## Menyiapkan ulang database

```bash
php artisan migrate:fresh --seed
```

Perintah ini **menghapus seluruh data** lalu mengisi ulang data contoh. Jalankan hanya di komputer lokal.

## Menjalankan pengujian

```bash
php artisan test
```

Pengujian memakai SQLite di memori, jadi database `kitaa_segerin` tidak terpengaruh.

## Peta folder

| Folder | Isi |
|---|---|
| `app/Support/helpers.php` | Pembantu format Rupiah, tanggal Indonesia, terbilang |
| `app/Services/` | Perhitungan uang, penomoran dokumen, stok (mulai Tahap 2) |
| `config/navigation.php` | Struktur menu sidebar dan navigasi bawah |
| `resources/views/components/` | Komponen tampilan yang dipakai ulang |
| `resources/views/layouts/partials/` | Sidebar, header, navigasi bawah, pesan |
| `docs/` | Catatan kemajuan, panduan pengguna, panduan deploy |
