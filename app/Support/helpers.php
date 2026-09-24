<?php

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

if (! function_exists('rupiah')) {
    /**
     * Format angka menjadi Rupiah tanpa desimal, mis. 261000 => "Rp261.000".
     *
     * @param  int|float|string|null  $angka
     */
    function rupiah($angka, bool $denganPrefix = true): string
    {
        $nilai = (int) round((float) ($angka ?? 0));
        $negatif = $nilai < 0;
        $teks = number_format(abs($nilai), 0, ',', '.');

        return ($negatif ? '-' : '').($denganPrefix ? 'Rp' : '').$teks;
    }
}

if (! function_exists('angka')) {
    /**
     * Format angka biasa dengan pemisah ribuan ala Indonesia, mis. 1250 => "1.250".
     *
     * @param  int|float|string|null  $angka
     */
    function angka($angka, int $desimal = 0): string
    {
        return number_format((float) ($angka ?? 0), $desimal, ',', '.');
    }
}

if (! function_exists('persen')) {
    /**
     * Format angka menjadi persen, mis. 81.69 => "81,7%".
     *
     * @param  int|float|string|null  $angka
     */
    function persen($angka, int $desimal = 1): string
    {
        return number_format((float) ($angka ?? 0), $desimal, ',', '.').'%';
    }
}

if (! function_exists('tanggal_indo')) {
    /**
     * Tanggal format Indonesia, mis. "23 September 2026".
     *
     * @param  DateTimeInterface|string|null  $tanggal
     */
    function tanggal_indo($tanggal, bool $denganJam = false): string
    {
        if (blank($tanggal)) {
            return '-';
        }

        $carbon = $tanggal instanceof CarbonInterface
            ? $tanggal
            : Carbon::parse($tanggal);

        $carbon = $carbon->locale('id');

        return $denganJam
            ? $carbon->translatedFormat('j F Y, H:i')
            : $carbon->translatedFormat('j F Y');
    }
}

if (! function_exists('tanggal_singkat')) {
    /**
     * Tanggal ringkas untuk tabel/kartu, mis. "23 Sep 2026".
     *
     * @param  DateTimeInterface|string|null  $tanggal
     */
    function tanggal_singkat($tanggal): string
    {
        if (blank($tanggal)) {
            return '-';
        }

        $carbon = $tanggal instanceof CarbonInterface ? $tanggal : Carbon::parse($tanggal);

        return $carbon->locale('id')->translatedFormat('j M Y');
    }
}

if (! function_exists('terbilang_angka')) {
    /**
     * Ubah bilangan bulat menjadi kata Bahasa Indonesia (tanpa kata "rupiah").
     */
    function terbilang_angka(int $angka): string
    {
        $angka = abs($angka);

        $satuan = [
            'nol', 'satu', 'dua', 'tiga', 'empat', 'lima',
            'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas',
        ];

        if ($angka < 12) {
            return $satuan[$angka];
        }

        if ($angka < 20) {
            return terbilang_angka($angka - 10).' belas';
        }

        if ($angka < 100) {
            $sisa = $angka % 10;

            return trim(terbilang_angka(intdiv($angka, 10)).' puluh'.($sisa ? ' '.terbilang_angka($sisa) : ''));
        }

        if ($angka < 200) {
            $sisa = $angka - 100;

            return trim('seratus'.($sisa ? ' '.terbilang_angka($sisa) : ''));
        }

        if ($angka < 1000) {
            $sisa = $angka % 100;

            return trim(terbilang_angka(intdiv($angka, 100)).' ratus'.($sisa ? ' '.terbilang_angka($sisa) : ''));
        }

        if ($angka < 2000) {
            $sisa = $angka - 1000;

            return trim('seribu'.($sisa ? ' '.terbilang_angka($sisa) : ''));
        }

        if ($angka < 1_000_000) {
            $sisa = $angka % 1000;

            return trim(terbilang_angka(intdiv($angka, 1000)).' ribu'.($sisa ? ' '.terbilang_angka($sisa) : ''));
        }

        if ($angka < 1_000_000_000) {
            $sisa = $angka % 1_000_000;

            return trim(terbilang_angka(intdiv($angka, 1_000_000)).' juta'.($sisa ? ' '.terbilang_angka($sisa) : ''));
        }

        if ($angka < 1_000_000_000_000) {
            $sisa = $angka % 1_000_000_000;

            return trim(terbilang_angka(intdiv($angka, 1_000_000_000)).' miliar'.($sisa ? ' '.terbilang_angka($sisa) : ''));
        }

        $sisa = $angka % 1_000_000_000_000;

        return trim(terbilang_angka(intdiv($angka, 1_000_000_000_000)).' triliun'.($sisa ? ' '.terbilang_angka($sisa) : ''));
    }
}

if (! function_exists('terbilang')) {
    /**
     * Ubah nominal rupiah menjadi kata, mis. 261000 => "dua ratus enam puluh satu ribu rupiah".
     *
     * @param  int|float|string|null  $angka
     */
    function terbilang($angka, bool $kapitalAwal = false): string
    {
        $nilai = (int) round((float) ($angka ?? 0));
        $kata = terbilang_angka($nilai);

        if ($nilai < 0) {
            $kata = 'minus '.$kata;
        }

        $hasil = $kata.' rupiah';

        return $kapitalAwal ? ucfirst($hasil) : $hasil;
    }
}

if (! function_exists('versi_aset')) {
    /**
     * Penanda versi aset hasil build, dipakai sebagai versi cache PWA.
     *
     * Nilainya ikut berubah setiap kali Vite membangun ulang (isi
     * public/build/manifest.json berubah), sehingga service worker lama
     * otomatis diganti dan cache lamanya dibuang. Saat "npm run dev"
     * berjalan, manifestnya belum ada dan nilainya "dev".
     */
    function versi_aset(): string
    {
        static $versi = null;

        if ($versi !== null) {
            return $versi;
        }

        $manifest = public_path('build/manifest.json');

        return $versi = is_file($manifest)
            ? substr(md5_file($manifest), 0, 10)
            : 'dev';
    }
}

if (! function_exists('nav_url')) {
    /**
     * URL menu yang aman dipakai walau route-nya belum dibuat (tahap berikutnya).
     */
    function nav_url(?string $namaRoute): string
    {
        if (blank($namaRoute)) {
            return '#';
        }

        return Route::has($namaRoute) ? route($namaRoute) : '#';
    }
}

if (! function_exists('nav_aktif')) {
    /**
     * Menandai menu yang sedang dibuka, mis. kunci "produk" cocok dengan
     * route "produk.index", "produk.create", dan seterusnya.
     */
    function nav_aktif(string $kunci): bool
    {
        return request()->routeIs($kunci) || request()->routeIs($kunci.'.*');
    }
}

if (! function_exists('nav_boleh')) {
    /**
     * Menentukan apakah satu item menu boleh dilihat pengguna yang sedang masuk,
     * berdasarkan kunci "roles" di config/navigation.php.
     *
     * @param  array<string, mixed>  $item
     */
    function nav_boleh(array $item): bool
    {
        $pengguna = auth()->user();

        if (! $pengguna) {
            return false;
        }

        $peran = $item['roles'] ?? null;

        return blank($peran) || in_array($pengguna->role, (array) $peran, true);
    }
}

if (! function_exists('nav_grup_terpakai')) {
    /**
     * Item satu grup menu yang boleh dilihat pengguna saat ini.
     *
     * @param  array<string, mixed>  $grup
     * @return Collection<int, array<string, mixed>>
     */
    function nav_grup_terpakai(array $grup): Collection
    {
        return collect($grup['items'] ?? [])->filter('nav_boleh')->values();
    }
}
