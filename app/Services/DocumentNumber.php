<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Penomoran dokumen otomatis dan unik (bagian D poin 7 CLAUDE.md).
 *
 * Nomor urut diambil dengan lockForUpdate di dalam transaksi supaya dua
 * pengguna yang menyimpan bersamaan tidak mendapat nomor yang sama.
 */
class DocumentNumber
{
    /**
     * Pola nomor per jenis dokumen.
     *
     * @var array<string, array{tabel: string, awalan: string}>
     */
    private const POLA = [
        'purchase' => ['tabel' => 'purchases', 'awalan' => 'BLI'],
        'consignment' => ['tabel' => 'consignments', 'awalan' => 'KRM'],
        'invoice' => ['tabel' => 'invoices', 'awalan' => 'INV/KS'],
        'payment' => ['tabel' => 'payments', 'awalan' => 'BYR'],
    ];

    /**
     * Membuat nomor berikutnya, mis. BLI/2026/09/0001 atau INV/KS/2026/09/0001.
     *
     * Wajib dipanggil di dalam DB::transaction().
     */
    public function berikutnya(string $jenis, Carbon|string|null $tanggal = null): string
    {
        if (! isset(self::POLA[$jenis])) {
            throw new InvalidArgumentException("Jenis dokumen tidak dikenal: {$jenis}.");
        }

        ['tabel' => $tabel, 'awalan' => $awalan] = self::POLA[$jenis];

        $tanggal = $tanggal instanceof Carbon ? $tanggal : Carbon::parse($tanggal ?? now());

        $prefix = sprintf('%s/%s/%s/', $awalan, $tanggal->format('Y'), $tanggal->format('m'));

        $terakhir = DB::table($tabel)
            ->where('number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('number')
            ->value('number');

        $urut = $terakhir
            ? ((int) substr($terakhir, strlen($prefix))) + 1
            : 1;

        return $prefix.str_pad((string) $urut, 4, '0', STR_PAD_LEFT);
    }

    /** Nomor pembelian, mis. BLI/2026/09/0001. */
    public function pembelian(Carbon|string|null $tanggal = null): string
    {
        return $this->berikutnya('purchase', $tanggal);
    }

    /** Nomor pengiriman, mis. KRM/2026/09/0001. */
    public function pengiriman(Carbon|string|null $tanggal = null): string
    {
        return $this->berikutnya('consignment', $tanggal);
    }

    /** Nomor tagihan, mis. INV/KS/2026/09/0001. */
    public function tagihan(Carbon|string|null $tanggal = null): string
    {
        return $this->berikutnya('invoice', $tanggal);
    }

    /** Nomor pembayaran, mis. BYR/2026/09/0001. */
    public function pembayaran(Carbon|string|null $tanggal = null): string
    {
        return $this->berikutnya('payment', $tanggal);
    }

    /**
     * Kode berurutan untuk data master, mis. ES-001 atau TK-001.
     */
    public function kodeMaster(string $tabel, string $awalan, int $panjang = 3): string
    {
        $terakhir = DB::table($tabel)
            ->where('code', 'like', $awalan.'-%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $urut = $terakhir
            ? ((int) substr($terakhir, strlen($awalan) + 1)) + 1
            : 1;

        return $awalan.'-'.str_pad((string) $urut, $panjang, '0', STR_PAD_LEFT);
    }
}
