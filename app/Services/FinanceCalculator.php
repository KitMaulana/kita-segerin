<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Store;
use App\Models\StorePrice;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Satu-satunya tempat perhitungan uang (bagian B CLAUDE.md).
 *
 * Seluruh nilai adalah Rupiah bulat. Tidak ada perhitungan uang yang boleh
 * ditulis ulang di controller maupun Blade.
 */
class FinanceCalculator
{
    /**
     * Fee toko per pcs dalam rupiah.
     *
     * Fee persen dihitung dari harga jual lalu dibulatkan ke rupiah terdekat,
     * mis. 10% dari Rp5.000 = Rp500.
     */
    public function feePerPcs(int $hargaJual, string $jenisFee, int $nilaiFee): int
    {
        if ($nilaiFee < 0) {
            throw new InvalidArgumentException('Nilai fee tidak boleh negatif.');
        }

        $fee = match ($jenisFee) {
            'percent' => (int) round($hargaJual * $nilaiFee / 100),
            'nominal' => $nilaiFee,
            default => throw new InvalidArgumentException("Jenis fee tidak dikenal: {$jenisFee}."),
        };

        // Fee tidak boleh melebihi harga jual, karena setoran tidak boleh negatif.
        return min($fee, $hargaJual);
    }

    /** Setoran per pcs = harga jual − fee toko. */
    public function setoranPerPcs(int $hargaJual, int $feePerPcs): int
    {
        return $hargaJual - $feePerPcs;
    }

    /** Laba per pcs = setoran per pcs − harga modal. */
    public function labaPerPcs(int $hargaJual, int $feePerPcs, int $hargaModal): int
    {
        return $this->setoranPerPcs($hargaJual, $feePerPcs) - $hargaModal;
    }

    /**
     * Ringkasan satu pcs untuk pratinjau di form dan tabel harga toko.
     *
     * @return array{fee_per_pcs: int, setoran_per_pcs: int, laba_per_pcs: int, margin: float}
     */
    public function ringkasanPerPcs(int $hargaModal, int $hargaJual, string $jenisFee, int $nilaiFee): array
    {
        $fee = $this->feePerPcs($hargaJual, $jenisFee, $nilaiFee);
        $setoran = $this->setoranPerPcs($hargaJual, $fee);
        $laba = $setoran - $hargaModal;

        return [
            'fee_per_pcs' => $fee,
            'setoran_per_pcs' => $setoran,
            'laba_per_pcs' => $laba,
            'margin' => $setoran > 0 ? round($laba / $setoran * 100, 2) : 0.0,
        ];
    }

    /**
     * Perhitungan satu baris produk dalam satu pengiriman.
     *
     * @return array{
     *     sisa_belum_dicatat: int, penjualan_kotor: int, total_fee_toko: int,
     *     setoran: int, hpp: int, laba_kotor: int, kerugian_rusak: int,
     *     laba_bersih_baris: int, tingkat_laku: float
     * }
     *
     * @throws InvalidArgumentException bila jumlah tercatat melebihi jumlah kirim
     */
    public function hitungBaris(
        int $qtySent,
        int $qtySold,
        int $qtyReturned,
        int $qtyDamaged,
        int $unitCost,
        int $unitPrice,
        int $feePerUnit,
    ): array {
        foreach (compact('qtySent', 'qtySold', 'qtyReturned', 'qtyDamaged') as $nama => $nilai) {
            if ($nilai < 0) {
                throw new InvalidArgumentException("Jumlah {$nama} tidak boleh negatif.");
            }
        }

        $sisa = $qtySent - ($qtySold + $qtyReturned + $qtyDamaged);

        if ($sisa < 0) {
            throw new InvalidArgumentException(
                'Jumlah terjual, retur, dan rusak melebihi jumlah yang dikirim.'
            );
        }

        $penjualanKotor = $qtySold * $unitPrice;
        $totalFee = $qtySold * $feePerUnit;
        $setoran = $penjualanKotor - $totalFee;
        $hpp = $qtySold * $unitCost;
        $labaKotor = $setoran - $hpp;
        $kerugianRusak = $qtyDamaged * $unitCost;

        return [
            'sisa_belum_dicatat' => $sisa,
            'penjualan_kotor' => $penjualanKotor,
            'total_fee_toko' => $totalFee,
            'setoran' => $setoran,
            'hpp' => $hpp,
            'laba_kotor' => $labaKotor,
            'kerugian_rusak' => $kerugianRusak,
            'laba_bersih_baris' => $labaKotor - $kerugianRusak,
            'tingkat_laku' => $this->tingkatLaku($qtySent, $qtySold),
        ];
    }

    /**
     * Tingkat laku = terjual ÷ kirim, dalam persen (0–100).
     */
    public function tingkatLaku(int $qtySent, int $qtySold): float
    {
        return $qtySent > 0 ? round($qtySold / $qtySent * 100, 2) : 0.0;
    }

    /**
     * Menjumlahkan banyak baris menjadi satu ringkasan.
     *
     * @param  iterable<array<string, int|float>>  $baris
     * @return array<string, int|float>
     */
    public function jumlahkanBaris(iterable $baris): array
    {
        $total = [
            'qty_kirim' => 0,
            'qty_terjual' => 0,
            'qty_retur' => 0,
            'qty_rusak' => 0,
            'penjualan_kotor' => 0,
            'total_fee_toko' => 0,
            'setoran' => 0,
            'hpp' => 0,
            'laba_kotor' => 0,
            'kerugian_rusak' => 0,
        ];

        foreach ($baris as $b) {
            foreach (array_keys($total) as $kunci) {
                $total[$kunci] += (int) ($b[$kunci] ?? 0);
            }
        }

        $total['laba_bersih_baris'] = $total['laba_kotor'] - $total['kerugian_rusak'];
        $total['tingkat_laku'] = $this->tingkatLaku($total['qty_kirim'], $total['qty_terjual']);
        $total['margin'] = $total['setoran'] > 0
            ? round($total['laba_kotor'] / $total['setoran'] * 100, 2)
            : 0.0;

        return $total;
    }

    /**
     * Laba bersih periode = Σ laba kotor − Σ kerugian rusak − Σ biaya operasional.
     */
    public function labaBersih(int $totalLabaKotor, int $totalKerugianRusak, int $totalBiaya): int
    {
        return $totalLabaKotor - $totalKerugianRusak - $totalBiaya;
    }

    /**
     * Harga jual dan fee yang berlaku untuk satu toko dan satu produk pada tanggal tertentu.
     *
     * Memakai harga khusus toko bila ada dan sudah berlaku pada tanggal itu;
     * bila tidak ada, memakai harga default produk.
     *
     * @return array{
     *     unit_cost: int, unit_price: int, fee_type: string, fee_value: int,
     *     fee_per_unit: int, setoran_per_pcs: int, laba_per_pcs: int, sumber: string
     * }
     */
    public function resolvePrice(Store $store, Product $product, Carbon|string|null $tanggal = null): array
    {
        $tanggal = $tanggal instanceof Carbon ? $tanggal : Carbon::parse($tanggal ?? now());

        $hargaToko = StorePrice::query()
            ->where('store_id', $store->getKey())
            ->where('product_id', $product->getKey())
            ->whereDate('effective_from', '<=', $tanggal->toDateString())
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();

        $hargaJual = $hargaToko?->selling_price ?? $product->default_selling_price;
        $jenisFee = $hargaToko?->fee_type ?? $product->default_fee_type;
        $nilaiFee = $hargaToko?->fee_value ?? $product->default_fee_value;

        $feePerUnit = $this->feePerPcs($hargaJual, $jenisFee, $nilaiFee);

        return [
            'unit_cost' => (int) $product->cost_price,
            'unit_price' => (int) $hargaJual,
            'fee_type' => $jenisFee,
            'fee_value' => (int) $nilaiFee,
            'fee_per_unit' => $feePerUnit,
            'setoran_per_pcs' => $this->setoranPerPcs($hargaJual, $feePerUnit),
            'laba_per_pcs' => $this->labaPerPcs($hargaJual, $feePerUnit, (int) $product->cost_price),
            'sumber' => $hargaToko ? 'khusus' : 'default',
        ];
    }

    /**
     * Membagi daftar tagihan ke dalam kelompok umur piutang.
     *
     * @param  iterable<Invoice>  $tagihan
     * @return array<string, array{jumlah: int, nilai: int}>
     */
    public function umurPiutang(iterable $tagihan): array
    {
        $kelompok = [
            '0-7' => ['jumlah' => 0, 'nilai' => 0],
            '8-14' => ['jumlah' => 0, 'nilai' => 0],
            '15-30' => ['jumlah' => 0, 'nilai' => 0],
            '>30' => ['jumlah' => 0, 'nilai' => 0],
        ];

        foreach ($tagihan as $t) {
            $hari = max(0, $t->umurPiutangHari());

            $kunci = match (true) {
                $hari <= 7 => '0-7',
                $hari <= 14 => '8-14',
                $hari <= 30 => '15-30',
                default => '>30',
            };

            $kelompok[$kunci]['jumlah']++;
            $kelompok[$kunci]['nilai'] += $t->sisaTagihan();
        }

        return $kelompok;
    }
}
