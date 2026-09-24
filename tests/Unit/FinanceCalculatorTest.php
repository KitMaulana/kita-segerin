<?php

namespace Tests\Unit;

use App\Services\FinanceCalculator;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Menguji seluruh rumus pada bagian B CLAUDE.md.
 *
 * Contoh uji utama: harga modal 2.500, harga jual 5.000, fee nominal 500,
 * kirim 71, terjual 58, retur 13, rusak 0
 * → penjualan kotor 290.000; fee 29.000; setoran 261.000; HPP 145.000; laba kotor 116.000.
 */
class FinanceCalculatorTest extends TestCase
{
    private FinanceCalculator $hitung;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hitung = new FinanceCalculator;
    }

    public function test_contoh_uji_bagian_b(): void
    {
        $hasil = $this->hitung->hitungBaris(
            qtySent: 71,
            qtySold: 58,
            qtyReturned: 13,
            qtyDamaged: 0,
            unitCost: 2500,
            unitPrice: 5000,
            feePerUnit: 500,
        );

        $this->assertSame(0, $hasil['sisa_belum_dicatat']);
        $this->assertSame(290_000, $hasil['penjualan_kotor']);
        $this->assertSame(29_000, $hasil['total_fee_toko']);
        $this->assertSame(261_000, $hasil['setoran']);
        $this->assertSame(145_000, $hasil['hpp']);
        $this->assertSame(116_000, $hasil['laba_kotor']);
        $this->assertSame(0, $hasil['kerugian_rusak']);
        $this->assertSame(81.69, $hasil['tingkat_laku']);
    }

    public function test_fee_nominal(): void
    {
        $this->assertSame(500, $this->hitung->feePerPcs(5000, 'nominal', 500));
        $this->assertSame(0, $this->hitung->feePerPcs(5000, 'nominal', 0));
    }

    public function test_fee_persen_dibulatkan_ke_rupiah_terdekat(): void
    {
        // 10% dari Rp5.000 = Rp500
        $this->assertSame(500, $this->hitung->feePerPcs(5000, 'percent', 10));

        // 10% dari Rp4.000 = Rp400
        $this->assertSame(400, $this->hitung->feePerPcs(4000, 'percent', 10));

        // 15% dari Rp3.500 = Rp525
        $this->assertSame(525, $this->hitung->feePerPcs(3500, 'percent', 15));

        // 7% dari Rp3.500 = Rp245
        $this->assertSame(245, $this->hitung->feePerPcs(3500, 'percent', 7));
    }

    public function test_fee_tidak_boleh_melebihi_harga_jual(): void
    {
        $this->assertSame(5000, $this->hitung->feePerPcs(5000, 'nominal', 9000));
        $this->assertSame(5000, $this->hitung->feePerPcs(5000, 'percent', 150));
    }

    public function test_fee_dengan_jenis_tidak_dikenal_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->hitung->feePerPcs(5000, 'gratis', 10);
    }

    public function test_fee_negatif_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->hitung->feePerPcs(5000, 'nominal', -100);
    }

    public function test_setoran_dan_laba_per_pcs(): void
    {
        $this->assertSame(4500, $this->hitung->setoranPerPcs(5000, 500));
        $this->assertSame(2000, $this->hitung->labaPerPcs(5000, 500, 2500));
    }

    public function test_ringkasan_per_pcs_dengan_fee_persen(): void
    {
        // Syarat selesai Tahap 5: fee 10% dari Rp5.000 → fee Rp500, setoran Rp4.500.
        $r = $this->hitung->ringkasanPerPcs(hargaModal: 2500, hargaJual: 5000, jenisFee: 'percent', nilaiFee: 10);

        $this->assertSame(500, $r['fee_per_pcs']);
        $this->assertSame(4500, $r['setoran_per_pcs']);
        $this->assertSame(2000, $r['laba_per_pcs']);
        $this->assertSame(44.44, $r['margin']);
    }

    public function test_baris_dengan_barang_rusak(): void
    {
        $hasil = $this->hitung->hitungBaris(
            qtySent: 50, qtySold: 40, qtyReturned: 5, qtyDamaged: 5,
            unitCost: 2500, unitPrice: 5000, feePerUnit: 500,
        );

        $this->assertSame(0, $hasil['sisa_belum_dicatat']);
        $this->assertSame(200_000, $hasil['penjualan_kotor']);
        $this->assertSame(20_000, $hasil['total_fee_toko']);
        $this->assertSame(180_000, $hasil['setoran']);
        $this->assertSame(100_000, $hasil['hpp']);
        $this->assertSame(80_000, $hasil['laba_kotor']);
        $this->assertSame(12_500, $hasil['kerugian_rusak']);   // 5 × 2.500
        $this->assertSame(67_500, $hasil['laba_bersih_baris']);
    }

    public function test_baris_yang_belum_selesai_dicatat(): void
    {
        $hasil = $this->hitung->hitungBaris(
            qtySent: 100, qtySold: 30, qtyReturned: 10, qtyDamaged: 0,
            unitCost: 2500, unitPrice: 5000, feePerUnit: 500,
        );

        $this->assertSame(60, $hasil['sisa_belum_dicatat']);
    }

    public function test_qty_melebihi_kiriman_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('melebihi jumlah yang dikirim');

        $this->hitung->hitungBaris(
            qtySent: 71, qtySold: 58, qtyReturned: 20, qtyDamaged: 0,
            unitCost: 2500, unitPrice: 5000, feePerUnit: 500,
        );
    }

    public function test_qty_negatif_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->hitung->hitungBaris(
            qtySent: 71, qtySold: -1, qtyReturned: 0, qtyDamaged: 0,
            unitCost: 2500, unitPrice: 5000, feePerUnit: 500,
        );
    }

    public function test_tingkat_laku(): void
    {
        $this->assertSame(81.69, $this->hitung->tingkatLaku(71, 58));
        $this->assertSame(100.0, $this->hitung->tingkatLaku(50, 50));
        $this->assertSame(0.0, $this->hitung->tingkatLaku(0, 0));
    }

    public function test_menjumlahkan_banyak_baris(): void
    {
        $baris = [
            $this->hitung->hitungBaris(71, 58, 13, 0, 2500, 5000, 500) + ['qty_kirim' => 71, 'qty_terjual' => 58, 'qty_retur' => 13, 'qty_rusak' => 0],
            $this->hitung->hitungBaris(50, 40, 5, 5, 2500, 5000, 500) + ['qty_kirim' => 50, 'qty_terjual' => 40, 'qty_retur' => 5, 'qty_rusak' => 5],
        ];

        $total = $this->hitung->jumlahkanBaris($baris);

        $this->assertSame(121, $total['qty_kirim']);
        $this->assertSame(98, $total['qty_terjual']);
        $this->assertSame(490_000, $total['penjualan_kotor']);
        $this->assertSame(49_000, $total['total_fee_toko']);
        $this->assertSame(441_000, $total['setoran']);
        $this->assertSame(245_000, $total['hpp']);
        $this->assertSame(196_000, $total['laba_kotor']);
        $this->assertSame(12_500, $total['kerugian_rusak']);
        $this->assertSame(183_500, $total['laba_bersih_baris']);
    }

    public function test_laba_bersih_periode(): void
    {
        // Σ laba kotor 196.000 − rusak 12.500 − biaya 50.000 = 133.500
        $this->assertSame(133_500, $this->hitung->labaBersih(196_000, 12_500, 50_000));
    }

    public function test_laba_bersih_bisa_negatif(): void
    {
        $this->assertSame(-30_000, $this->hitung->labaBersih(100_000, 30_000, 100_000));
    }
}
