<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportController;
use App\Models\Consignment;
use App\Models\ConsignmentItem;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\CashBookService;
use App\Services\ReportService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $laporan;

    private Store $toko;

    private Product $produk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->laporan = app(ReportService::class);
        $this->toko = Store::factory()->create(['name' => 'Koperasi Budi Utama']);
        $this->produk = Product::factory()->create([
            'name' => 'Sutaco Taro',
            'cost_price' => 2500,
            'default_selling_price' => 5000,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /**
     * Pengiriman terekonsiliasi dengan angka contoh uji bagian B.
     */
    private function pengiriman(string $settledDate = '2026-09-22', int $terjual = 58, int $retur = 13, int $rusak = 0): Consignment
    {
        $pengiriman = Consignment::create([
            'number' => 'KRM/2026/09/'.str_pad((string) (Consignment::count() + 1), 4, '0', STR_PAD_LEFT),
            'store_id' => $this->toko->id,
            'sent_date' => '2026-09-15',
            'settled_date' => $settledDate,
            'status' => 'settled',
        ]);

        ConsignmentItem::create([
            'consignment_id' => $pengiriman->id,
            'product_id' => $this->produk->id,
            'qty_sent' => 71,
            'qty_sold' => $terjual,
            'qty_returned' => $retur,
            'qty_damaged' => $rusak,
            'unit_cost' => 2500,
            'unit_price' => 5000,
            'fee_per_unit' => 500,
        ]);

        return $pengiriman;
    }

    private function biaya(int $jumlah, string $tanggal = '2026-09-20', string $kategori = 'Transportasi/BBM'): Expense
    {
        return Expense::create([
            'date' => $tanggal,
            'expense_category_id' => ExpenseCategory::firstOrCreate(['name' => $kategori])->id,
            'amount' => $jumlah,
            'description' => 'Biaya uji '.$kategori,
        ]);
    }

    /**
     * Syarat selesai Tahap 8: laba bersih = Σ laba kotor − rusak − biaya,
     * dan cocok dengan hitungan manual.
     */
    public function test_laba_rugi_cocok_dengan_hitungan_manual(): void
    {
        $this->pengiriman();
        $this->biaya(50_000);

        $lr = $this->laporan->labaRugi('2026-09-01', '2026-09-30');

        // Penjualan kotor 58 × 5.000 = 290.000
        $this->assertSame(290_000, $lr['penjualan_kotor']);
        // Fee 58 × 500 = 29.000
        $this->assertSame(29_000, $lr['fee_toko']);
        // Setoran 290.000 − 29.000 = 261.000
        $this->assertSame(261_000, $lr['setoran']);
        // HPP 58 × 2.500 = 145.000
        $this->assertSame(145_000, $lr['hpp']);
        // Laba kotor 261.000 − 145.000 = 116.000
        $this->assertSame(116_000, $lr['laba_kotor']);
        $this->assertSame(0, $lr['kerugian_rusak']);
        $this->assertSame(50_000, $lr['total_biaya']);
        // Laba bersih 116.000 − 0 − 50.000 = 66.000
        $this->assertSame(66_000, $lr['laba_bersih']);

        $this->assertSame(
            $lr['laba_kotor'] - $lr['kerugian_rusak'] - $lr['total_biaya'],
            $lr['laba_bersih']
        );
    }

    public function test_laba_rugi_memperhitungkan_barang_rusak(): void
    {
        $this->pengiriman(terjual: 58, retur: 8, rusak: 5);
        $this->biaya(20_000);

        $lr = $this->laporan->labaRugi('2026-09-01', '2026-09-30');

        $this->assertSame(12_500, $lr['kerugian_rusak']);           // 5 × 2.500
        $this->assertSame(116_000 - 12_500 - 20_000, $lr['laba_bersih']);
    }

    public function test_laporan_memakai_tanggal_rekonsiliasi(): void
    {
        // Dikirim September, direkonsiliasi Oktober.
        $this->pengiriman(settledDate: '2026-10-02');

        $september = $this->laporan->labaRugi('2026-09-01', '2026-09-30');
        $oktober = $this->laporan->labaRugi('2026-10-01', '2026-10-31');

        $this->assertSame(0, $september['penjualan_kotor']);
        $this->assertSame(290_000, $oktober['penjualan_kotor']);
    }

    public function test_biaya_per_kategori_dikelompokkan(): void
    {
        $this->pengiriman();
        $this->biaya(30_000, kategori: 'Transportasi/BBM');
        $this->biaya(20_000, kategori: 'Dry ice & es batu');
        $this->biaya(10_000, kategori: 'Transportasi/BBM');

        $lr = $this->laporan->labaRugi('2026-09-01', '2026-09-30');

        $this->assertSame(60_000, $lr['total_biaya']);
        $this->assertSame(40_000, $lr['biaya_per_kategori']['Transportasi/BBM']);
        $this->assertSame(20_000, $lr['biaya_per_kategori']['Dry ice & es batu']);
    }

    public function test_rekap_produk(): void
    {
        $this->pengiriman();

        $baris = $this->laporan->rekapProduk('2026-09-01', '2026-09-30');

        $this->assertCount(1, $baris);

        $b = $baris->first();

        $this->assertSame('Sutaco Taro', $b['nama']);
        $this->assertSame(71, $b['qty_kirim']);
        $this->assertSame(58, $b['qty_terjual']);
        $this->assertSame(261_000, $b['setoran']);
        $this->assertSame(116_000, $b['laba_kotor']);
        $this->assertSame(81.69, $b['tingkat_laku']);
    }

    public function test_rekap_toko(): void
    {
        $this->pengiriman();

        $b = $this->laporan->rekapToko('2026-09-01', '2026-09-30')->first();

        $this->assertSame('Koperasi Budi Utama', $b['nama']);
        $this->assertSame(1, $b['jumlah_pengiriman']);
        $this->assertSame(261_000, $b['setoran']);
        $this->assertSame(29_000, $b['fee_toko']);
        $this->assertSame(0, $b['piutang']);
    }

    public function test_arus_kas(): void
    {
        $kas = app(CashBookService::class);

        $kas->masuk('owner_capital', 1_000_000, 'Modal awal', '2026-09-01');
        $kas->keluar('purchase', 250_000, 'Kulakan', '2026-09-05');
        $kas->keluar('expense', 50_000, 'Bensin', '2026-09-10');

        $ak = $this->laporan->arusKas('2026-09-01', '2026-09-30');

        $this->assertSame(0, $ak['saldo_awal']);
        $this->assertSame(1_000_000, $ak['total_masuk']);
        $this->assertSame(300_000, $ak['total_keluar']);
        $this->assertSame(700_000, $ak['saldo_akhir']);
    }

    public function test_saldo_awal_periode_berikutnya_sama_dengan_saldo_akhir(): void
    {
        $kas = app(CashBookService::class);
        $kas->masuk('owner_capital', 500_000, 'Modal', '2026-09-01');

        $september = $this->laporan->arusKas('2026-09-01', '2026-09-30');
        $oktober = $this->laporan->arusKas('2026-10-01', '2026-10-31');

        $this->assertSame($september['saldo_akhir'], $oktober['saldo_awal']);
    }

    public function test_persediaan(): void
    {
        app(StockService::class)->catat($this->produk, 'purchase_in', 100);

        $p = $this->laporan->persediaan();

        $this->assertSame(100, $p['total_gudang']);
        $this->assertSame(250_000, $p['nilai_total']);
    }

    public function test_rekap_periode_pengiriman(): void
    {
        $this->pengiriman();

        $rekap = $this->laporan->rekapPeriodePengiriman('2026-09-01', '2026-09-30');
        $b = $rekap['baris']->first();

        $this->assertSame(71, $b['kirim']);
        $this->assertSame(58, $b['terjual']);
        $this->assertSame(13, $b['sisa']);            // 71 − 58
        $this->assertSame(261_000, $b['setoran']);
        $this->assertSame(116_000, $b['keuntungan']); // setoran − HPP − rusak
        $this->assertSame(71, $rekap['total']['kirim']);
    }

    public function test_semua_halaman_laporan_bisa_dibuka(): void
    {
        $this->pengiriman();
        $this->biaya(50_000);

        $admin = $this->admin();

        $this->actingAs($admin)->get(route('laporan.index'))->assertOk();

        foreach (array_keys(ReportController::JENIS) as $jenis) {
            $this->actingAs($admin)
                ->get(route('laporan.tampil', $jenis).'?periode=bebas&dari=2026-09-01&sampai=2026-09-30')
                ->assertOk();
        }
    }

    public function test_halaman_laba_rugi_menampilkan_angka_benar(): void
    {
        $this->pengiriman();
        $this->biaya(50_000);

        $this->actingAs($this->admin())
            ->get(route('laporan.tampil', 'laba-rugi').'?periode=bebas&dari=2026-09-01&sampai=2026-09-30')
            ->assertOk()
            ->assertSee('Rp290.000')
            ->assertSee('Rp261.000')
            ->assertSee('Rp116.000')
            ->assertSee('Rp66.000');
    }

    public function test_laporan_bisa_dicetak_pdf(): void
    {
        $this->pengiriman();

        foreach (array_keys(ReportController::JENIS) as $jenis) {
            $this->actingAs($this->admin())
                ->get(route('laporan.pdf', $jenis).'?periode=bebas&dari=2026-09-01&sampai=2026-09-30')
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }
    }

    public function test_laporan_bisa_diekspor_excel(): void
    {
        $this->pengiriman();

        $respons = $this->actingAs($this->admin())
            ->get(route('laporan.excel', 'produk').'?periode=bebas&dari=2026-09-01&sampai=2026-09-30');

        $respons->assertOk()->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        // Berkas xlsx sebenarnya adalah zip, jadi diawali "PK".
        $isi = $respons->streamedContent();
        $this->assertStringStartsWith('PK', $isi);
    }

    public function test_jenis_laporan_tidak_dikenal_menghasilkan_404(): void
    {
        $this->actingAs($this->admin())->get(route('laporan.tampil', 'entah-apa'))->assertNotFound();
    }

    public function test_pemantau_bisa_melihat_laporan(): void
    {
        $this->pengiriman();

        $this->actingAs(User::factory()->viewer()->create())
            ->get(route('laporan.tampil', 'laba-rugi'))
            ->assertOk();
    }
}
