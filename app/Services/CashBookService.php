<?php

namespace App\Services;

use App\Models\CashTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Pencatatan buku kas. Pembayaran tagihan, pembelian, dan biaya memanggil
 * service ini supaya seluruh uang masuk-keluar tercatat di satu tempat.
 */
class CashBookService
{
    public function catat(
        string $arah,
        string $kategori,
        int $jumlah,
        string $keterangan,
        Carbon|string|null $tanggal = null,
        ?Model $referensi = null,
    ): CashTransaction {
        return CashTransaction::create([
            'date' => Carbon::parse($tanggal ?? now())->toDateString(),
            'direction' => $arah,
            'category' => $kategori,
            'amount' => abs($jumlah),
            'description' => $keterangan,
            'reference_type' => $referensi?->getMorphClass(),
            'reference_id' => $referensi?->getKey(),
            'user_id' => auth()->id(),
        ]);
    }

    public function masuk(string $kategori, int $jumlah, string $keterangan, Carbon|string|null $tanggal = null, ?Model $referensi = null): CashTransaction
    {
        return $this->catat('in', $kategori, $jumlah, $keterangan, $tanggal, $referensi);
    }

    public function keluar(string $kategori, int $jumlah, string $keterangan, Carbon|string|null $tanggal = null, ?Model $referensi = null): CashTransaction
    {
        return $this->catat('out', $kategori, $jumlah, $keterangan, $tanggal, $referensi);
    }

    /**
     * Menghapus baris buku kas yang dibuat otomatis oleh sebuah dokumen.
     * Dipakai saat dokumen sumbernya dihapus atau dibatalkan.
     */
    public function hapusUntuk(Model $referensi): int
    {
        return CashTransaction::query()
            ->where('reference_type', $referensi->getMorphClass())
            ->where('reference_id', $referensi->getKey())
            ->delete();
    }

    /**
     * Saldo kas sampai tanggal tertentu (inklusif).
     */
    public function saldoSampai(Carbon|string|null $tanggal = null): int
    {
        $query = CashTransaction::query();

        if ($tanggal) {
            $query->whereDate('date', '<=', Carbon::parse($tanggal)->toDateString());
        }

        return (int) $query->selectRaw(
            "COALESCE(SUM(CASE WHEN direction = 'in' THEN amount ELSE -amount END), 0) as saldo"
        )->value('saldo');
    }

    /**
     * Saldo sebelum tanggal tertentu (eksklusif), dipakai sebagai saldo awal laporan.
     */
    public function saldoSebelum(Carbon|string $tanggal): int
    {
        return (int) CashTransaction::query()
            ->whereDate('date', '<', Carbon::parse($tanggal)->toDateString())
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN amount ELSE -amount END), 0) as saldo")
            ->value('saldo');
    }

    /**
     * Total masuk dan keluar dalam satu rentang tanggal.
     *
     * @return array{masuk: int, keluar: int, selisih: int}
     */
    public function ringkasanPeriode(Carbon|string $dari, Carbon|string $sampai): array
    {
        $hasil = CashTransaction::query()
            ->whereBetween('date', [
                Carbon::parse($dari)->toDateString(),
                Carbon::parse($sampai)->toDateString(),
            ])
            ->selectRaw("
                COALESCE(SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END), 0) as masuk,
                COALESCE(SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END), 0) as keluar
            ")
            ->first();

        $masuk = (int) $hasil->masuk;
        $keluar = (int) $hasil->keluar;

        return ['masuk' => $masuk, 'keluar' => $keluar, 'selisih' => $masuk - $keluar];
    }
}
