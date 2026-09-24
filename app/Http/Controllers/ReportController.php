<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\ReportService;
use App\Support\ExcelWriter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menu Laporan. Seluruh angka diambil dari ReportService.
 */
class ReportController extends Controller
{
    public function __construct(private readonly ReportService $laporan) {}

    /**
     * Daftar jenis laporan beserta judulnya.
     */
    public const JENIS = [
        'laba-rugi' => 'Laba Rugi',
        'produk' => 'Rekap per Produk',
        'toko' => 'Rekap per Toko',
        'piutang' => 'Piutang & Umur Piutang',
        'arus-kas' => 'Arus Kas',
        'persediaan' => 'Persediaan',
        'periode-pengiriman' => 'Rekap Periode Pengiriman',
    ];

    public function index(Request $request): View
    {
        return view('laporan.index', $this->dataLaporan($request));
    }

    public function tampil(Request $request, string $jenis): View
    {
        abort_unless(isset(self::JENIS[$jenis]), 404);

        return view('laporan.'.$jenis, $this->dataLaporan($request, $jenis));
    }

    /**
     * Cetak PDF satu laporan.
     */
    public function pdf(Request $request, string $jenis): Response
    {
        abort_unless(isset(self::JENIS[$jenis]), 404);

        $data = $this->dataLaporan($request, $jenis) + ['pengaturan' => Setting::semua()];

        $pdf = Pdf::loadView('laporan.cetak.'.$jenis, $data)
            ->setPaper('a4', in_array($jenis, ['produk', 'toko', 'periode-pengiriman'], true) ? 'landscape' : 'portrait');

        return $pdf->stream('Laporan-'.$jenis.'-'.now()->format('Ymd').'.pdf');
    }

    /**
     * Ekspor satu laporan ke Excel (.xlsx).
     */
    public function excel(Request $request, string $jenis): StreamedResponse
    {
        abort_unless(isset(self::JENIS[$jenis]), 404);

        $data = $this->dataLaporan($request, $jenis);

        [$judul, $kolom, $baris] = $this->tabelEkspor($jenis, $data);

        return ExcelWriter::unduh(
            namaBerkas: 'Laporan-'.$jenis.'-'.now()->format('Ymd').'.xlsx',
            judul: $judul,
            kolom: $kolom,
            baris: $baris,
            keterangan: 'Periode '.tanggal_indo($data['dari']).' – '.tanggal_indo($data['sampai']),
        );
    }

    /**
     * Data bersama untuk seluruh laporan.
     *
     * @return array<string, mixed>
     */
    private function dataLaporan(Request $request, ?string $jenis = null): array
    {
        [$dari, $sampai, $preset] = $this->periode($request);

        $tokoId = $request->integer('toko') ?: null;

        $data = [
            'dari' => $dari,
            'sampai' => $sampai,
            'preset' => $preset,
            'tokoId' => $tokoId,
            'daftarToko' => $this->laporan->daftarToko(),
            'jenis' => $jenis,
            'daftarJenis' => self::JENIS,
        ];

        return match ($jenis) {
            'laba-rugi' => $data + ['lr' => $this->laporan->labaRugi($dari, $sampai)],
            'produk' => $data + ['baris' => $this->laporan->rekapProduk($dari, $sampai)],
            'toko' => $data + ['baris' => $this->laporan->rekapToko($dari, $sampai)],
            'piutang' => $data + ['piutang' => $this->laporan->piutang()],
            'arus-kas' => $data + ['kas' => $this->laporan->arusKas($dari, $sampai)],
            'persediaan' => $data + ['persediaan' => $this->laporan->persediaan()],
            'periode-pengiriman' => $data + ['rekap' => $this->laporan->rekapPeriodePengiriman($dari, $sampai, $tokoId)],
            default => $data + [
                'ringkas' => $this->laporan->labaRugi($dari, $sampai),
                'totalPiutang' => $this->laporan->totalPiutang(),
                'persediaan' => $this->laporan->persediaan(),
            ],
        };
    }

    /**
     * Menerjemahkan pilihan periode menjadi rentang tanggal.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function periode(Request $request): array
    {
        $preset = $request->string('periode')->value() ?: 'bulan-ini';

        return match ($preset) {
            'hari-ini' => [now()->startOfDay(), now()->endOfDay(), $preset],
            'minggu-ini' => [now()->startOfWeek(), now()->endOfWeek(), $preset],
            'bulan-lalu' => [
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
                $preset,
            ],
            'tahun-ini' => [now()->startOfYear(), now()->endOfYear(), $preset],
            'bebas' => [
                Carbon::parse($request->date('dari') ?? now()->startOfMonth()),
                Carbon::parse($request->date('sampai') ?? now()->endOfMonth()),
                'bebas',
            ],
            default => [now()->startOfMonth(), now()->endOfMonth(), 'bulan-ini'],
        };
    }

    /**
     * Menyiapkan judul, kolom, dan baris untuk ekspor Excel.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: array<int, string>, 2: iterable<array<int, mixed>>}
     */
    private function tabelEkspor(string $jenis, array $data): array
    {
        return match ($jenis) {
            'laba-rugi' => [
                'Laporan Laba Rugi',
                ['Keterangan', 'Jumlah (Rp)'],
                $this->barisLabaRugi($data['lr']),
            ],

            'produk' => [
                'Rekap per Produk',
                ['Kode', 'Produk', 'Kirim', 'Terjual', 'Retur', 'Rusak', 'Tingkat laku (%)',
                    'Penjualan kotor', 'Fee toko', 'Setoran', 'HPP', 'Laba kotor', 'Margin (%)'],
                $data['baris']->map(fn ($b) => [
                    $b['kode'], $b['nama'], $b['qty_kirim'], $b['qty_terjual'], $b['qty_retur'], $b['qty_rusak'],
                    $b['tingkat_laku'], $b['penjualan_kotor'], $b['fee_toko'], $b['setoran'],
                    $b['hpp'], $b['laba_kotor'], $b['margin'],
                ]),
            ],

            'toko' => [
                'Rekap per Toko',
                ['Kode', 'Toko', 'Pengiriman', 'Kirim', 'Terjual', 'Tingkat laku (%)',
                    'Penjualan kotor', 'Fee toko', 'Setoran', 'Laba', 'Piutang'],
                $data['baris']->map(fn ($b) => [
                    $b['kode'], $b['nama'], $b['jumlah_pengiriman'], $b['qty_kirim'], $b['qty_terjual'],
                    $b['tingkat_laku'], $b['penjualan_kotor'], $b['fee_toko'], $b['setoran'],
                    $b['laba_kotor'], $b['piutang'],
                ]),
            ],

            'piutang' => [
                'Piutang & Umur Piutang',
                ['Nomor', 'Toko', 'Tanggal', 'Jatuh tempo', 'Tagihan', 'Dibayar', 'Sisa', 'Umur (hari)'],
                collect($data['piutang']['tagihan'])->map(fn ($i) => [
                    $i->number, $i->store->name, $i->invoice_date->toDateString(), $i->due_date->toDateString(),
                    $i->amount_due, $i->amount_paid, $i->sisaTagihan(), max(0, $i->umurPiutangHari()),
                ]),
            ],

            'arus-kas' => [
                'Laporan Arus Kas',
                ['Keterangan', 'Jumlah (Rp)'],
                collect([
                    ['Saldo awal', $data['kas']['saldo_awal']],
                    ['Total uang masuk', $data['kas']['total_masuk']],
                    ['Total uang keluar', $data['kas']['total_keluar']],
                    ['Saldo akhir', $data['kas']['saldo_akhir']],
                ])->concat(
                    collect($data['kas']['masuk_per_kategori'])->map(fn ($b) => ['Masuk — '.$b['nama'], $b['jumlah']])
                )->concat(
                    collect($data['kas']['keluar_per_kategori'])->map(fn ($b) => ['Keluar — '.$b['nama'], $b['jumlah']])
                ),
            ],

            'persediaan' => [
                'Laporan Persediaan',
                ['Kode', 'Produk', 'Stok gudang', 'Stok di toko', 'Total', 'Harga modal', 'Nilai persediaan'],
                $data['persediaan']['baris']->map(fn ($b) => [
                    $b['produk']->code, $b['produk']->name, $b['stok_gudang'], $b['stok_di_toko'],
                    $b['stok_total'], $b['produk']->cost_price, $b['nilai_total'],
                ]),
            ],

            'periode-pengiriman' => [
                'Rekap Periode Pengiriman',
                ['Kode', 'Produk', 'Kirim', 'Terjual', 'Sisa', 'Retur', 'Rusak',
                    'Tingkat laku (%)', 'Setoran', 'Keuntungan'],
                $data['rekap']['baris']->map(fn ($b) => [
                    $b['kode'], $b['nama'], $b['kirim'], $b['terjual'], $b['sisa'], $b['retur'], $b['rusak'],
                    $b['tingkat_laku'], $b['setoran'], $b['keuntungan'],
                ]),
            ],

            default => ['Laporan', ['Keterangan'], collect()],
        };
    }

    /**
     * @param  array<string, mixed>  $lr
     * @return Collection<int, array<int, mixed>>
     */
    private function barisLabaRugi(array $lr)
    {
        $baris = collect([
            ['Penjualan kotor', $lr['penjualan_kotor']],
            ['Fee toko', -$lr['fee_toko']],
            ['Setoran', $lr['setoran']],
            ['HPP (harga modal barang terjual)', -$lr['hpp']],
            ['Laba kotor', $lr['laba_kotor']],
            ['Kerugian barang rusak', -$lr['kerugian_rusak']],
        ]);

        foreach ($lr['biaya_per_kategori'] as $nama => $jumlah) {
            $baris->push(['Biaya — '.$nama, -$jumlah]);
        }

        return $baris
            ->push(['Total biaya operasional', -$lr['total_biaya']])
            ->push(['LABA BERSIH', $lr['laba_bersih']]);
    }
}
