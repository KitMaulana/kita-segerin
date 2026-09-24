<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Menguji pembantu format uang, tanggal, dan terbilang (bagian B & D CLAUDE.md).
 */
class HelperFormatTest extends TestCase
{
    public function test_rupiah_memakai_titik_sebagai_pemisah_ribuan(): void
    {
        $this->assertSame('Rp261.000', rupiah(261000));
        $this->assertSame('Rp1.250.000', rupiah(1250000));
        $this->assertSame('Rp0', rupiah(0));
        $this->assertSame('Rp2.500', rupiah(2500));
    }

    public function test_rupiah_membulatkan_dan_menangani_nilai_negatif(): void
    {
        $this->assertSame('Rp500', rupiah(499.6));
        $this->assertSame('-Rp5.000', rupiah(-5000));
        $this->assertSame('Rp0', rupiah(null));
        $this->assertSame('261.000', rupiah(261000, denganPrefix: false));
    }

    public function test_tanggal_tampil_dalam_bahasa_indonesia(): void
    {
        $this->assertSame('23 September 2026', tanggal_indo('2026-09-23'));
        $this->assertSame('1 Januari 2026', tanggal_indo('2026-01-01'));
        $this->assertSame('23 Sep 2026', tanggal_singkat('2026-09-23'));
        $this->assertSame('-', tanggal_indo(null));
    }

    public function test_terbilang_mengubah_angka_menjadi_kata(): void
    {
        $this->assertSame('dua ratus enam puluh satu ribu rupiah', terbilang(261000));
        $this->assertSame('nol rupiah', terbilang(0));
        $this->assertSame('seribu rupiah', terbilang(1000));
        $this->assertSame('seribu lima ratus rupiah', terbilang(1500));
        $this->assertSame('sebelas ribu rupiah', terbilang(11000));
        $this->assertSame('lima belas rupiah', terbilang(15));
        $this->assertSame('seratus dua puluh lima rupiah', terbilang(125));
        $this->assertSame('satu juta dua ratus lima puluh ribu rupiah', terbilang(1250000));
        $this->assertSame('satu miliar rupiah', terbilang(1_000_000_000));
    }

    public function test_terbilang_bisa_diawali_huruf_kapital(): void
    {
        $this->assertSame('Dua ratus enam puluh satu ribu rupiah', terbilang(261000, kapitalAwal: true));
    }

    public function test_angka_dan_persen_memakai_format_indonesia(): void
    {
        $this->assertSame('1.250', angka(1250));
        $this->assertSame('81,7%', persen(81.69));
        $this->assertSame('100%', persen(100, desimal: 0));
    }
}
