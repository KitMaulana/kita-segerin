<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\ConsignmentItem;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Store;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Seluruh angka laporan diambil dari query agregat di sini, bukan dihitung
 * ulang di Blade (bagian G Tahap 8 poin 5 CLAUDE.md).
 *
 * Laporan laba rugi memakai TANGGAL REKONSILIASI (saat penjualan tercatat),
 * sedangkan laporan kas memakai tanggal uang benar-benar masuk/keluar.
 */
class ReportService
{
    public function __construct(
        private readonly FinanceCalculator $hitung,
        private readonly StockService $stok,
        private readonly CashBookService $kas,
    ) {}

    /**
     * Baris pengiriman yang sudah direkonsiliasi dalam satu periode.
     * Dipakai sebagai dasar hampir semua laporan penjualan.
     */
    private function itemTerekonsiliasi(Carbon|string $dari, Carbon|string $sampai)
    {
        return ConsignmentItem::query()
            ->join('consignments', 'consignments.id', '=', 'consignment_items.consignment_id')
            ->whereIn('consignments.status', ['settled', 'invoiced'])
            ->whereNotNull('consignments.settled_date')
            ->whereBetween('consignments.settled_date', [
                Carbon::parse($dari)->toDateString(),
                Carbon::parse($sampai)->toDateString(),
            ]);
    }

    /**
     * a. Laporan laba rugi.
     *
     * @return array<string, mixed>
     */
    public function labaRugi(Carbon|string $dari, Carbon|string $sampai): array
    {
        $penjualan = $this->itemTerekonsiliasi($dari, $sampai)
            ->selectRaw('
                COALESCE(SUM(consignment_items.qty_sent), 0) as qty_kirim,
                COALESCE(SUM(consignment_items.qty_sold), 0) as qty_terjual,
                COALESCE(SUM(consignment_items.qty_returned), 0) as qty_retur,
                COALESCE(SUM(consignment_items.qty_damaged), 0) as qty_rusak,
                COALESCE(SUM(consignment_items.qty_sold * consignment_items.unit_price), 0) as penjualan_kotor,
                COALESCE(SUM(consignment_items.qty_sold * consignment_items.fee_per_unit), 0) as fee_toko,
                COALESCE(SUM(consignment_items.qty_sold * consignment_items.unit_cost), 0) as hpp,
                COALESCE(SUM(consignment_items.qty_damaged * consignment_items.unit_cost), 0) as kerugian_rusak
            ')
            ->first();

        $kotor = (int) $penjualan->penjualan_kotor;
        $fee = (int) $penjualan->fee_toko;
        $setoran = $kotor - $fee;
        $hpp = (int) $penjualan->hpp;
        $labaKotor = $setoran - $hpp;
        $rusak = (int) $penjualan->kerugian_rusak;

        $biayaPerKategori = Expense::query()
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->whereBetween('expenses.date', [
                Carbon::parse($dari)->toDateString(),
                Carbon::parse($sampai)->toDateString(),
            ])
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->selectRaw('expense_categories.name as nama, SUM(expenses.amount) as jumlah')
            ->orderByDesc('jumlah')
            ->pluck('jumlah', 'nama')
            ->map(fn ($v) => (int) $v);

        $totalBiaya = (int) $biayaPerKategori->sum();

        return [
            'qty_kirim' => (int) $penjualan->qty_kirim,
            'qty_terjual' => (int) $penjualan->qty_terjual,
            'qty_retur' => (int) $penjualan->qty_retur,
            'qty_rusak' => (int) $penjualan->qty_rusak,
            'penjualan_kotor' => $kotor,
            'fee_toko' => $fee,
            'setoran' => $setoran,
            'hpp' => $hpp,
            'laba_kotor' => $labaKotor,
            'kerugian_rusak' => $rusak,
            'biaya_per_kategori' => $biayaPerKategori,
            'total_biaya' => $totalBiaya,
            'laba_bersih' => $this->hitung->labaBersih($labaKotor, $rusak, $totalBiaya),
            'tingkat_laku' => $this->hitung->tingkatLaku((int) $penjualan->qty_kirim, (int) $penjualan->qty_terjual),
            'margin' => $setoran > 0 ? round($labaKotor / $setoran * 100, 2) : 0.0,
        ];
    }

    /**
     * b. Rekap per produk.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function rekapProduk(Carbon|string $dari, Carbon|string $sampai): Collection
    {
        return $this->itemTerekonsiliasi($dari, $sampai)
            ->join('products', 'products.id', '=', 'consignment_items.product_id')
            ->groupBy('products.id', 'products.code', 'products.name')
            ->selectRaw('
                products.id as produk_id,
                products.code as kode,
                products.name as nama,
                SUM(consignment_items.qty_sent) as qty_kirim,
                SUM(consignment_items.qty_sold) as qty_terjual,
                SUM(consignment_items.qty_returned) as qty_retur,
                SUM(consignment_items.qty_damaged) as qty_rusak,
                SUM(consignment_items.qty_sold * consignment_items.unit_price) as penjualan_kotor,
                SUM(consignment_items.qty_sold * consignment_items.fee_per_unit) as fee_toko,
                SUM(consignment_items.qty_sold * consignment_items.unit_cost) as hpp,
                SUM(consignment_items.qty_damaged * consignment_items.unit_cost) as kerugian_rusak
            ')
            ->orderByDesc('penjualan_kotor')
            ->get()
            ->map(function ($b) {
                $setoran = (int) $b->penjualan_kotor - (int) $b->fee_toko;
                $laba = $setoran - (int) $b->hpp;

                return [
                    'produk_id' => (int) $b->produk_id,
                    'kode' => $b->kode,
                    'nama' => $b->nama,
                    'qty_kirim' => (int) $b->qty_kirim,
                    'qty_terjual' => (int) $b->qty_terjual,
                    'qty_retur' => (int) $b->qty_retur,
                    'qty_rusak' => (int) $b->qty_rusak,
                    'penjualan_kotor' => (int) $b->penjualan_kotor,
                    'fee_toko' => (int) $b->fee_toko,
                    'setoran' => $setoran,
                    'hpp' => (int) $b->hpp,
                    'laba_kotor' => $laba,
                    'kerugian_rusak' => (int) $b->kerugian_rusak,
                    'tingkat_laku' => $this->hitung->tingkatLaku((int) $b->qty_kirim, (int) $b->qty_terjual),
                    'margin' => $setoran > 0 ? round($laba / $setoran * 100, 2) : 0.0,
                ];
            });
    }

    /**
     * c. Rekap per toko.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function rekapToko(Carbon|string $dari, Carbon|string $sampai): Collection
    {
        $penjualan = $this->itemTerekonsiliasi($dari, $sampai)
            ->join('stores', 'stores.id', '=', 'consignments.store_id')
            ->groupBy('stores.id', 'stores.code', 'stores.name')
            ->selectRaw('
                stores.id as toko_id,
                stores.code as kode,
                stores.name as nama,
                COUNT(DISTINCT consignments.id) as jumlah_pengiriman,
                SUM(consignment_items.qty_sent) as qty_kirim,
                SUM(consignment_items.qty_sold) as qty_terjual,
                SUM(consignment_items.qty_sold * consignment_items.unit_price) as penjualan_kotor,
                SUM(consignment_items.qty_sold * consignment_items.fee_per_unit) as fee_toko,
                SUM(consignment_items.qty_sold * consignment_items.unit_cost) as hpp,
                SUM(consignment_items.qty_damaged * consignment_items.unit_cost) as kerugian_rusak
            ')
            ->orderByDesc('penjualan_kotor')
            ->get();

        // Piutang dihitung dari tagihan yang belum lunas, bukan dari periode.
        $piutang = Invoice::query()
            ->belumLunas()
            ->groupBy('store_id')
            ->selectRaw('store_id, SUM(amount_due - amount_paid) as sisa')
            ->pluck('sisa', 'store_id');

        return $penjualan->map(function ($b) use ($piutang) {
            $setoran = (int) $b->penjualan_kotor - (int) $b->fee_toko;
            $laba = $setoran - (int) $b->hpp;

            return [
                'toko_id' => (int) $b->toko_id,
                'kode' => $b->kode,
                'nama' => $b->nama,
                'jumlah_pengiriman' => (int) $b->jumlah_pengiriman,
                'qty_kirim' => (int) $b->qty_kirim,
                'qty_terjual' => (int) $b->qty_terjual,
                'penjualan_kotor' => (int) $b->penjualan_kotor,
                'fee_toko' => (int) $b->fee_toko,
                'setoran' => $setoran,
                'laba_kotor' => $laba - (int) $b->kerugian_rusak,
                'piutang' => (int) ($piutang[$b->toko_id] ?? 0),
                'tingkat_laku' => $this->hitung->tingkatLaku((int) $b->qty_kirim, (int) $b->qty_terjual),
            ];
        });
    }

    /**
     * d. Piutang dan umur piutang.
     *
     * @return array<string, mixed>
     */
    public function piutang(): array
    {
        $tagihan = Invoice::belumLunas()->with('store:id,name')->orderBy('due_date')->get();

        return [
            'tagihan' => $tagihan,
            'total' => (int) $tagihan->sum(fn (Invoice $i) => $i->sisaTagihan()),
            'umur' => $this->hitung->umurPiutang($tagihan),
            'per_toko' => $tagihan->groupBy('store_id')->map(fn ($g) => [
                'toko' => $g->first()->store,
                'jumlah' => $g->count(),
                'nilai' => (int) $g->sum(fn (Invoice $i) => $i->sisaTagihan()),
            ])->sortByDesc('nilai')->values(),
        ];
    }

    /**
     * e. Arus kas.
     *
     * @return array<string, mixed>
     */
    public function arusKas(Carbon|string $dari, Carbon|string $sampai): array
    {
        $ringkasan = $this->kas->ringkasanPeriode($dari, $sampai);
        $saldoAwal = $this->kas->saldoSebelum($dari);

        $perKategori = CashTransaction::query()
            ->whereBetween('date', [
                Carbon::parse($dari)->toDateString(),
                Carbon::parse($sampai)->toDateString(),
            ])
            ->groupBy('category', 'direction')
            ->selectRaw('category, direction, SUM(amount) as jumlah')
            ->get()
            ->map(fn ($b) => [
                'kategori' => $b->category,
                'nama' => CashTransaction::KATEGORI[$b->category] ?? $b->category,
                'arah' => $b->direction,
                'jumlah' => (int) $b->jumlah,
            ]);

        return [
            'saldo_awal' => $saldoAwal,
            'total_masuk' => $ringkasan['masuk'],
            'total_keluar' => $ringkasan['keluar'],
            'saldo_akhir' => $saldoAwal + $ringkasan['selisih'],
            'masuk_per_kategori' => $perKategori->where('arah', 'in')->sortByDesc('jumlah')->values(),
            'keluar_per_kategori' => $perKategori->where('arah', 'out')->sortByDesc('jumlah')->values(),
        ];
    }

    /**
     * f. Persediaan.
     *
     * @return array<string, mixed>
     */
    public function persediaan(): array
    {
        $ringkasan = $this->stok->ringkasanPersediaan(termasukNonaktif: true);

        return [
            'baris' => $ringkasan,
            'total_gudang' => (int) $ringkasan->sum('stok_gudang'),
            'total_di_toko' => (int) $ringkasan->sum('stok_di_toko'),
            'nilai_gudang' => (int) $ringkasan->sum('nilai_gudang'),
            'nilai_di_toko' => (int) $ringkasan->sum('nilai_di_toko'),
            'nilai_total' => (int) $ringkasan->sum('nilai_total'),
            'jumlah_menipis' => $ringkasan->where('menipis', true)->count(),
        ];
    }

    /**
     * g. Rekap periode pengiriman (format mingguan): per produk
     *    kirim – terjual – sisa – keuntungan.
     *
     * @return array<string, mixed>
     */
    public function rekapPeriodePengiriman(Carbon|string $dari, Carbon|string $sampai, ?int $tokoId = null): array
    {
        $query = $this->itemTerekonsiliasi($dari, $sampai)
            ->join('products', 'products.id', '=', 'consignment_items.product_id')
            ->when($tokoId, fn ($q) => $q->where('consignments.store_id', $tokoId))
            ->groupBy('products.id', 'products.code', 'products.name')
            ->selectRaw('
                products.code as kode,
                products.name as nama,
                SUM(consignment_items.qty_sent) as kirim,
                SUM(consignment_items.qty_sold) as terjual,
                SUM(consignment_items.qty_returned) as retur,
                SUM(consignment_items.qty_damaged) as rusak,
                SUM(consignment_items.qty_sold * consignment_items.unit_price) as penjualan_kotor,
                SUM(consignment_items.qty_sold * consignment_items.fee_per_unit) as fee_toko,
                SUM(consignment_items.qty_sold * consignment_items.unit_cost) as hpp,
                SUM(consignment_items.qty_damaged * consignment_items.unit_cost) as kerugian_rusak
            ')
            ->orderBy('products.name');

        $baris = $query->get()->map(function ($b) {
            $setoran = (int) $b->penjualan_kotor - (int) $b->fee_toko;

            return [
                'kode' => $b->kode,
                'nama' => $b->nama,
                'kirim' => (int) $b->kirim,
                'terjual' => (int) $b->terjual,
                'retur' => (int) $b->retur,
                'rusak' => (int) $b->rusak,
                'sisa' => (int) $b->kirim - (int) $b->terjual,
                'penjualan_kotor' => (int) $b->penjualan_kotor,
                'fee_toko' => (int) $b->fee_toko,
                'setoran' => $setoran,
                'keuntungan' => $setoran - (int) $b->hpp - (int) $b->kerugian_rusak,
                'tingkat_laku' => $this->hitung->tingkatLaku((int) $b->kirim, (int) $b->terjual),
            ];
        });

        return [
            'baris' => $baris,
            'total' => [
                'kirim' => (int) $baris->sum('kirim'),
                'terjual' => (int) $baris->sum('terjual'),
                'retur' => (int) $baris->sum('retur'),
                'rusak' => (int) $baris->sum('rusak'),
                'sisa' => (int) $baris->sum('sisa'),
                'penjualan_kotor' => (int) $baris->sum('penjualan_kotor'),
                'fee_toko' => (int) $baris->sum('fee_toko'),
                'setoran' => (int) $baris->sum('setoran'),
                'keuntungan' => (int) $baris->sum('keuntungan'),
            ],
        ];
    }

    /**
     * Tren setoran dan laba beberapa bulan terakhir, untuk grafik beranda.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function trenBulanan(int $jumlahBulan = 6): Collection
    {
        return collect(range($jumlahBulan - 1, 0))->map(function (int $mundur) {
            $bulan = now()->startOfMonth()->subMonths($mundur);
            $lr = $this->labaRugi($bulan->copy()->startOfMonth(), $bulan->copy()->endOfMonth());

            return [
                'bulan' => $bulan->locale('id')->translatedFormat('M Y'),
                'setoran' => $lr['setoran'],
                'laba_bersih' => $lr['laba_bersih'],
                'laba_kotor' => $lr['laba_kotor'],
            ];
        });
    }

    /**
     * Daftar toko dengan setoran terbesar dalam satu periode.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function tokoTeratas(Carbon|string $dari, Carbon|string $sampai, int $batas = 5): Collection
    {
        return $this->rekapToko($dari, $sampai)->take($batas);
    }

    /**
     * Total piutang seluruh toko.
     */
    public function totalPiutang(): int
    {
        return (int) Invoice::belumLunas()
            ->selectRaw('COALESCE(SUM(amount_due - amount_paid), 0) as sisa')
            ->value('sisa');
    }

    /**
     * Daftar toko untuk saringan laporan.
     */
    public function daftarToko(): Collection
    {
        return Store::orderBy('name')->get(['id', 'name']);
    }
}
