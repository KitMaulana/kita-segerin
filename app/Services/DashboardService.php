<?php

namespace App\Services;

use App\Models\Consignment;
use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Data beranda (dashboard). Seluruh angka di-cache 5 menit dan cache-nya
 * dihapus setiap ada transaksi baru lewat DashboardService::bersihkanCache().
 */
class DashboardService
{
    public const PREFIKS_CACHE = 'beranda';

    private const MASA_CACHE = 300; // 5 menit

    public function __construct(
        private readonly ReportService $laporan,
        private readonly StockService $stok,
    ) {}

    /**
     * Seluruh data beranda untuk satu bulan.
     *
     * @return array<string, mixed>
     */
    public function data(Carbon $bulan): array
    {
        $kunci = self::PREFIKS_CACHE.':'.$bulan->format('Y-m');

        return Cache::remember($kunci, self::MASA_CACHE, function () use ($bulan) {
            $awal = $bulan->copy()->startOfMonth();
            $akhir = $bulan->copy()->endOfMonth();

            $bulanLalu = $bulan->copy()->subMonthNoOverflow();

            $ini = $this->laporan->labaRugi($awal, $akhir);
            $lalu = $this->laporan->labaRugi($bulanLalu->copy()->startOfMonth(), $bulanLalu->copy()->endOfMonth());
            $persediaan = $this->laporan->persediaan();

            return [
                'bulan' => $bulan->copy(),
                'ringkas' => $ini,
                'bulan_lalu' => $lalu,
                'perubahan_setoran' => $this->persenPerubahan($lalu['setoran'], $ini['setoran']),
                'perubahan_laba' => $this->persenPerubahan($lalu['laba_bersih'], $ini['laba_bersih']),
                'piutang' => $this->laporan->totalPiutang(),
                'persediaan' => $persediaan,
                'tren' => $this->laporan->trenBulanan(6),
                'per_produk' => $this->laporan->rekapProduk($awal, $akhir),
                'toko_teratas' => $this->laporan->tokoTeratas($awal, $akhir, 5),
                'perhatian' => $this->perhatian(),
            ];
        });
    }

    /**
     * Panel perhatian: tagihan lewat jatuh tempo, stok menipis, dan
     * pengiriman yang belum direkonsiliasi lebih dari 7 hari.
     *
     * @return array<string, mixed>
     */
    public function perhatian(): array
    {
        $jatuhTempo = Invoice::jatuhTempo()
            ->with('store:id,name')
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        $belumRekonsiliasi = Consignment::query()
            ->where('status', 'sent')
            ->whereDate('sent_date', '<=', now()->subDays(7)->toDateString())
            ->with('store:id,name')
            ->orderBy('sent_date')
            ->limit(10)
            ->get();

        $stokMenipis = $this->stok->stokMenipis();

        return [
            'jatuh_tempo' => $jatuhTempo,
            'nilai_jatuh_tempo' => (int) $jatuhTempo->sum(fn (Invoice $i) => $i->sisaTagihan()),
            'stok_menipis' => $stokMenipis,
            'belum_rekonsiliasi' => $belumRekonsiliasi,
            'ada_perhatian' => $jatuhTempo->isNotEmpty()
                || $stokMenipis->isNotEmpty()
                || $belumRekonsiliasi->isNotEmpty(),
        ];
    }

    /**
     * Persentase perubahan dari bulan lalu ke bulan ini.
     * Mengembalikan null bila bulan lalu nol supaya tidak menampilkan angka menyesatkan.
     */
    private function persenPerubahan(int $lalu, int $ini): ?float
    {
        if ($lalu === 0) {
            return null;
        }

        return round(($ini - $lalu) / abs($lalu) * 100, 1);
    }

    /**
     * Menghapus cache beranda. Dipanggil setiap ada transaksi baru.
     */
    public static function bersihkanCache(): void
    {
        // Cache driver database tidak mendukung tag, jadi kunci dihapus per bulan
        // di sekitar hari ini (cukup untuk tampilan beranda).
        foreach (range(-2, 1) as $geser) {
            Cache::forget(self::PREFIKS_CACHE.':'.now()->addMonthsNoOverflow($geser)->format('Y-m'));
        }
    }
}
