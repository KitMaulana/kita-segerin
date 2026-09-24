<?php

namespace Tests\Feature;

use App\Models\Consignment;
use App\Models\ConsignmentItem;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BerandaTest extends TestCase
{
    use RefreshDatabase;

    private Store $toko;

    private Product $produk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->toko = Store::factory()->create(['name' => 'Koperasi Budi Utama']);
        $this->produk = Product::factory()->create([
            'name' => 'Sutaco Taro',
            'cost_price' => 2500,
            'default_selling_price' => 5000,
            'min_stock' => 50,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function penjualanBulanIni(): Consignment
    {
        $pengiriman = Consignment::create([
            'number' => 'KRM/'.now()->format('Y/m').'/0001',
            'store_id' => $this->toko->id,
            'sent_date' => now()->startOfMonth()->toDateString(),
            'settled_date' => now()->toDateString(),
            'status' => 'settled',
        ]);

        ConsignmentItem::create([
            'consignment_id' => $pengiriman->id,
            'product_id' => $this->produk->id,
            'qty_sent' => 71,
            'qty_sold' => 58,
            'qty_returned' => 13,
            'qty_damaged' => 0,
            'unit_cost' => 2500,
            'unit_price' => 5000,
            'fee_per_unit' => 500,
        ]);

        return $pengiriman;
    }

    public function test_beranda_menampilkan_kartu_ringkasan(): void
    {
        $this->penjualanBulanIni();

        $this->actingAs($this->admin())
            ->get(route('beranda'))
            ->assertOk()
            ->assertSee('Setoran bulan ini')
            ->assertSee('Rp261.000')          // setoran
            ->assertSee('Laba bersih')
            ->assertSee('Rp116.000');         // laba bersih (tanpa biaya)
    }

    public function test_beranda_menampilkan_tingkat_laku(): void
    {
        $this->penjualanBulanIni();

        $this->actingAs($this->admin())
            ->get(route('beranda'))
            ->assertOk()
            ->assertSee('Tingkat laku per produk')
            ->assertSee('Sutaco Taro')
            ->assertSee('81,7%');
    }

    public function test_beranda_bisa_memilih_bulan(): void
    {
        $this->actingAs($this->admin())
            ->get(route('beranda', ['bulan' => now()->subMonth()->format('Y-m')]))
            ->assertOk()
            ->assertSee(now()->subMonth()->locale('id')->translatedFormat('F Y'));
    }

    public function test_bulan_tidak_valid_jatuh_ke_bulan_ini(): void
    {
        $this->actingAs($this->admin())
            ->get(route('beranda', ['bulan' => 'bukan-tanggal']))
            ->assertOk()
            ->assertSee(now()->locale('id')->translatedFormat('F Y'));
    }

    public function test_panel_perhatian_menampilkan_tagihan_jatuh_tempo(): void
    {
        Invoice::create([
            'number' => 'INV/KS/2026/09/0001',
            'store_id' => $this->toko->id,
            'invoice_date' => now()->subDays(20)->toDateString(),
            'due_date' => now()->subDays(10)->toDateString(),
            'period_start' => now()->subDays(25)->toDateString(),
            'period_end' => now()->subDays(20)->toDateString(),
            'gross_total' => 290_000,
            'fee_total' => 29_000,
            'amount_due' => 261_000,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        $this->actingAs($this->admin())
            ->get(route('beranda'))
            ->assertOk()
            ->assertSee('Perlu perhatian')
            ->assertSee('Tagihan lewat jatuh tempo')
            ->assertSee('Koperasi Budi Utama');
    }

    public function test_panel_perhatian_menampilkan_stok_menipis(): void
    {
        app(StockService::class)->catat($this->produk, 'purchase_in', 10);

        $this->actingAs($this->admin())
            ->get(route('beranda'))
            ->assertOk()
            ->assertSee('Stok menipis')
            ->assertSee('Sutaco Taro');
    }

    public function test_panel_perhatian_menampilkan_pengiriman_lama_belum_direkonsiliasi(): void
    {
        Consignment::create([
            'number' => 'KRM/'.now()->format('Y/m').'/0009',
            'store_id' => $this->toko->id,
            'sent_date' => now()->subDays(10)->toDateString(),
            'status' => 'sent',
        ]);

        $this->actingAs($this->admin())
            ->get(route('beranda'))
            ->assertOk()
            ->assertSee('Belum direkonsiliasi')
            ->assertSee('10 hari');
    }

    public function test_cetak_infografis_bisa_dibuka(): void
    {
        $this->penjualanBulanIni();

        $this->actingAs($this->admin())
            ->get(route('beranda.cetak'))
            ->assertOk()
            ->assertSee('INFOGRAFIS BULANAN')
            ->assertSee('landscape', false);
    }

    public function test_data_beranda_dicache(): void
    {
        $this->penjualanBulanIni();

        $beranda = app(DashboardService::class);

        $beranda->data(now()->startOfMonth());

        $this->assertTrue(Cache::has(DashboardService::PREFIKS_CACHE.':'.now()->format('Y-m')));
    }

    public function test_cache_dihapus_saat_ada_transaksi_baru(): void
    {
        $beranda = app(DashboardService::class);
        $kunci = DashboardService::PREFIKS_CACHE.':'.now()->format('Y-m');

        $beranda->data(now()->startOfMonth());
        $this->assertTrue(Cache::has($kunci));

        // Transaksi baru membuat cache beranda dihapus.
        $this->penjualanBulanIni();

        $this->assertFalse(Cache::has($kunci));
    }

    public function test_beranda_tidak_melakukan_query_berlebihan(): void
    {
        $this->penjualanBulanIni();

        DB::enableQueryLog();

        $this->actingAs($this->admin())->get(route('beranda'))->assertOk();

        $jumlah = count(DB::getQueryLog());

        DB::disableQueryLog();

        // Batas longgar; yang dijaga adalah tidak ada N+1 yang meledak.
        $this->assertLessThan(60, $jumlah, "Beranda menjalankan {$jumlah} query, terlalu banyak.");
    }

    public function test_pemantau_bisa_membuka_beranda(): void
    {
        $this->actingAs(User::factory()->viewer()->create())
            ->get(route('beranda'))
            ->assertOk()
            ->assertDontSee('Aksi cepat');
    }

    public function test_admin_melihat_tombol_aksi_cepat(): void
    {
        $this->actingAs($this->admin())
            ->get(route('beranda'))
            ->assertOk()
            ->assertSee('Aksi cepat')
            ->assertSee('Catat pengiriman');
    }
}
