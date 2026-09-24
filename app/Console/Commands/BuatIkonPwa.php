<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Menggambar ulang ikon PWA ke public/icons.
 *
 * Bentuknya sama dengan lambang di resources/views/components/app-logo.blade.php:
 * es krim stik berhuruf KS. Digambar memakai GD (bawaan PHP) supaya tidak perlu
 * paket tambahan dan hasilnya bisa dibuat ulang di komputer mana pun:
 *
 *     php artisan pwa:ikon
 *
 * Semua bentuk digambar pada kanvas 4x lebih besar lalu dikecilkan, supaya tepi
 * lengkung dan huruf tampil halus tanpa perlu pustaka antialias.
 */
class BuatIkonPwa extends Command
{
    protected $signature = 'pwa:ikon';

    protected $description = 'Menggambar ulang ikon PWA (192, 512, maskable, apple-touch) ke public/icons';

    /** Kanvas digambar sebesar ini kali ukuran akhir, lalu dikecilkan. */
    private const SKALA_GAMBAR = 4;

    /** Lebar ruang gambar lambang, sama dengan viewBox SVG-nya. */
    private const KOTAK = 48;

    public function handle(): int
    {
        if (! extension_loaded('gd')) {
            $this->error('Ekstensi PHP "gd" belum aktif. Aktifkan dulu di php.ini.');

            return self::FAILURE;
        }

        $folder = public_path('icons');

        if (! is_dir($folder)) {
            mkdir($folder, 0o755, true);
        }

        // Warna sesuai bagian F CLAUDE.md. Ikon biasa memakai latar frost,
        // ikon maskable memakai latar berry dengan lambang dibalik supaya
        // tetap kuat saat dipotong bulat oleh peluncur aplikasi.
        $terang = [
            'latar' => '#EEF6FA', 'badan' => '#5B2A6E', 'lelehan' => '#12A383',
            'stik' => '#C9A227', 'huruf' => '#FFFFFF',
        ];

        $gelap = [
            'latar' => '#5B2A6E', 'badan' => '#EEF6FA', 'lelehan' => '#12A383',
            'stik' => '#C9A227', 'huruf' => '#5B2A6E',
        ];

        $berkas = [
            // nama berkas => [ukuran, warna, bagian lambang terhadap lebar kanvas]
            'icon-192.png' => [192, $terang, 0.92],
            'icon-512.png' => [512, $terang, 0.92],
            // Ikon maskable: lambang hanya 60% supaya aman walau dipotong.
            'icon-maskable-512.png' => [512, $gelap, 0.60],
            // iOS tidak memakai manifest, ikonnya ditunjuk lewat <link> sendiri.
            'apple-touch-icon.png' => [180, $terang, 0.86],
        ];

        foreach ($berkas as $nama => [$ukuran, $warna, $bagian]) {
            $gambar = $this->gambarIkon($ukuran, $warna, $bagian);
            imagepng($gambar, $folder.DIRECTORY_SEPARATOR.$nama);
            imagedestroy($gambar);

            $this->line("  <fg=green>dibuat</> icons/{$nama} ({$ukuran}px)");
        }

        $this->info('Ikon PWA selesai digambar.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $warna
     * @return \GdImage
     */
    private function gambarIkon(int $ukuran, array $warna, float $bagian)
    {
        $besar = $ukuran * self::SKALA_GAMBAR;
        $kanvas = imagecreatetruecolor($besar, $besar);
        imagefilledrectangle($kanvas, 0, 0, $besar, $besar, $this->warna($kanvas, $warna['latar']));

        // Satu satuan viewBox = berapa piksel pada kanvas besar.
        $satuan = ($besar * $bagian) / self::KOTAK;
        $geserX = ($besar - self::KOTAK * $satuan) / 2;
        $geserY = $geserX;

        $x = fn (float $n): float => $geserX + $n * $satuan;
        $y = fn (float $n): float => $geserY + $n * $satuan;
        $p = fn (float $n): float => $n * $satuan;

        // 1. Stik kayu, digambar lebih dulu supaya pangkalnya tertutup badan.
        $this->kotakTumpul(
            $kanvas, $x(21.5), $y(34), $p(5), $p(12), $p(2.5),
            $this->warna($kanvas, $warna['stik'])
        );

        // 2. Badan es krim.
        $this->kotakTumpul(
            $kanvas, $x(8), $y(2), $p(32), $p(36), $p(12),
            $this->warna($kanvas, $warna['badan'])
        );

        // 3. Lelehan di bagian atas: pita penuh lalu tepi bawahnya dibuat
        //    bergelombang dengan menimpa warna badan di atasnya.
        $lelehan = $this->warna($kanvas, $warna['lelehan']);
        $this->kotakTumpul($kanvas, $x(8), $y(2), $p(32), $p(14), $p(12), $lelehan, bawahTumpul: false);
        $this->gelombang($kanvas, $x(8), $x(40), $y(13), $y(16), $p(2.2), $this->warna($kanvas, $warna['badan']));

        // 4. Monogram KS, digambar dari goresan berujung bulat.
        $this->monogram($kanvas, $x(24), $y(25), $p(11), $p(2.2), $this->warna($kanvas, $warna['huruf']));

        $kecil = imagescale($kanvas, $ukuran, $ukuran, IMG_BICUBIC);
        imagedestroy($kanvas);

        return $kecil;
    }

    /**
     * Persegi panjang bersudut tumpul. $bawahTumpul dimatikan untuk pita
     * lelehan, yang sudut atasnya saja yang membulat.
     */
    private function kotakTumpul(
        \GdImage $img, float $kiri, float $atas, float $lebar, float $tinggi,
        float $jari, int $warna, bool $bawahTumpul = true
    ): void {
        $kanan = $kiri + $lebar;
        $bawah = $atas + $tinggi;
        $jari = min($jari, $lebar / 2, $tinggi / 2);
        $d = (int) round($jari * 2);

        imagefilledrectangle($img, (int) round($kiri), (int) round($atas + $jari), (int) round($kanan), (int) round($bawah - ($bawahTumpul ? $jari : 0)), $warna);
        imagefilledrectangle($img, (int) round($kiri + $jari), (int) round($atas), (int) round($kanan - $jari), (int) round($bawah), $warna);

        imagefilledellipse($img, (int) round($kiri + $jari), (int) round($atas + $jari), $d, $d, $warna);
        imagefilledellipse($img, (int) round($kanan - $jari), (int) round($atas + $jari), $d, $d, $warna);

        if ($bawahTumpul) {
            imagefilledellipse($img, (int) round($kiri + $jari), (int) round($bawah - $jari), $d, $d, $warna);
            imagefilledellipse($img, (int) round($kanan - $jari), (int) round($bawah - $jari), $d, $d, $warna);
        }
    }

    /**
     * Menimpa daerah di bawah garis gelombang, dari $garisY sampai $sampaiY.
     */
    private function gelombang(\GdImage $img, float $kiri, float $kanan, float $garisY, float $sampaiY, float $tinggiOmbak, int $warna): void
    {
        $titik = [];
        $lebar = $kanan - $kiri;
        $langkah = max(1, (int) round($lebar / 160));

        for ($x = $kiri; $x <= $kanan; $x += $langkah) {
            $titik[] = (int) round($x);
            $titik[] = (int) round($garisY + $tinggiOmbak * sin(2 * M_PI * ($x - $kiri) / ($lebar / 2)));
        }

        array_push($titik, (int) round($kanan), (int) round($sampaiY), (int) round($kiri), (int) round($sampaiY));

        imagefilledpolygon($img, $titik, $warna);
    }

    /**
     * Huruf "KS" dari goresan lurus berujung bulat, dipusatkan di ($tengahX, $tengahY).
     */
    private function monogram(\GdImage $img, float $tengahX, float $tengahY, float $tinggi, float $tebal, int $warna): void
    {
        $lebarHuruf = $tinggi * 0.62;
        $jarak = $tinggi * 0.20;
        $kiri = $tengahX - ($lebarHuruf * 2 + $jarak) / 2;

        $atas = $tengahY - $tinggi / 2 + $tebal / 2;
        $bawah = $tengahY + $tinggi / 2 - $tebal / 2;
        $tengahH = ($atas + $bawah) / 2;

        // K
        $kA = $kiri + $tebal / 2;
        $kB = $kiri + $lebarHuruf - $tebal / 2;
        $this->goresan($img, $kA, $atas, $kA, $bawah, $tebal, $warna);
        $this->goresan($img, $kA, $tengahH, $kB, $atas, $tebal, $warna);
        $this->goresan($img, $kA, $tengahH, $kB, $bawah, $tebal, $warna);

        // S: dua busur setengah lingkaran yang bertemu di tengah huruf.
        // Dibuat melengkung, bukan bersiku, supaya tidak terbaca sebagai angka 5.
        $sTengahX = $kiri + $lebarHuruf + $jarak + $lebarHuruf / 2;
        $rx = ($lebarHuruf - $tebal) / 2;
        $ry = ($bawah - $atas) / 4;

        $this->busur($img, $sTengahX, $atas + $ry, $rx, $ry, -60, -270, $tebal, $warna);
        $this->busur($img, $sTengahX, $bawah - $ry, $rx, $ry, -90, 120, $tebal, $warna);
    }

    /**
     * Busur elips dari $dariDerajat ke $sampaiDerajat, digambar sebagai
     * rangkaian goresan pendek berujung bulat.
     */
    private function busur(
        \GdImage $img, float $tengahX, float $tengahY, float $rx, float $ry,
        float $dariDerajat, float $sampaiDerajat, float $tebal, int $warna
    ): void {
        $bagian = 48;
        $sebelumX = $sebelumY = null;

        for ($i = 0; $i <= $bagian; $i++) {
            $sudut = deg2rad($dariDerajat + ($sampaiDerajat - $dariDerajat) * $i / $bagian);
            $x = $tengahX + $rx * cos($sudut);
            $y = $tengahY + $ry * sin($sudut);

            if ($sebelumX !== null) {
                $this->goresan($img, $sebelumX, $sebelumY, $x, $y, $tebal, $warna);
            }

            [$sebelumX, $sebelumY] = [$x, $y];
        }
    }

    /**
     * Satu goresan tebal berujung bulat: badan berupa segi empat miring,
     * ujungnya ditutup lingkaran supaya sambungan huruf tidak bersudut tajam.
     */
    private function goresan(\GdImage $img, float $x1, float $y1, float $x2, float $y2, float $tebal, int $warna): void
    {
        $dx = $x2 - $x1;
        $dy = $y2 - $y1;
        $panjang = sqrt($dx * $dx + $dy * $dy);

        if ($panjang > 0) {
            $nx = -$dy / $panjang * $tebal / 2;
            $ny = $dx / $panjang * $tebal / 2;

            imagefilledpolygon($img, [
                (int) round($x1 + $nx), (int) round($y1 + $ny),
                (int) round($x2 + $nx), (int) round($y2 + $ny),
                (int) round($x2 - $nx), (int) round($y2 - $ny),
                (int) round($x1 - $nx), (int) round($y1 - $ny),
            ], $warna);
        }

        $d = (int) round($tebal);
        imagefilledellipse($img, (int) round($x1), (int) round($y1), $d, $d, $warna);
        imagefilledellipse($img, (int) round($x2), (int) round($y2), $d, $d, $warna);
    }

    private function warna(\GdImage $img, string $hex): int
    {
        [$r, $g, $b] = sscanf(ltrim($hex, '#'), '%2x%2x%2x');

        return imagecolorallocate($img, $r, $g, $b);
    }
}
