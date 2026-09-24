<?php

namespace Tests\Feature;

use App\Models\Consignment;
use App\Models\Product;
use App\Models\Store;
use App\Models\StorePrice;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengirimanTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function produkSiap(int $stok = 100): Product
    {
        $produk = Product::factory()->create([
            'name' => 'Sutaco Taro',
            'cost_price' => 2500,
            'default_selling_price' => 5000,
            'default_fee_type' => 'nominal',
            'default_fee_value' => 500,
        ]);

        app(StockService::class)->catat($produk, 'purchase_in', $stok);

        return $produk;
    }

    public function test_pengiriman_mengurangi_stok_gudang(): void
    {
        $produk = $this->produkSiap(100);
        $toko = Store::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('pengiriman.store'), [
                'store_id' => $toko->id,
                'sent_date' => now()->toDateString(),
                'items' => [['product_id' => $produk->id, 'qty_sent' => 71]],
            ])
            ->assertSessionHasNoErrors();

        $stok = app(StockService::class);

        $this->assertSame(29, $stok->stokGudang($produk));
        $this->assertSame(71, $stok->stokDiToko($produk));
        $this->assertDatabaseHas('consignments', ['number' => 'KRM/'.now()->format('Y/m').'/0001', 'status' => 'sent']);
    }

    public function test_harga_disalin_saat_pengiriman_dibuat(): void
    {
        $produk = $this->produkSiap();
        $toko = Store::factory()->create();

        $this->actingAs($this->admin())->post(route('pengiriman.store'), [
            'store_id' => $toko->id,
            'sent_date' => now()->toDateString(),
            'items' => [['product_id' => $produk->id, 'qty_sent' => 10]],
        ]);

        $item = Consignment::first()->items->first();

        $this->assertSame(2500, $item->unit_cost);
        $this->assertSame(5000, $item->unit_price);
        $this->assertSame(500, $item->fee_per_unit);

        // Mengubah harga master TIDAK mengubah kiriman lama.
        $produk->update(['cost_price' => 9999, 'default_selling_price' => 8888]);

        $this->assertSame(2500, $item->refresh()->unit_cost);
        $this->assertSame(5000, $item->unit_price);
    }

    public function test_harga_khusus_toko_dipakai_saat_pengiriman(): void
    {
        $produk = $this->produkSiap();
        $toko = Store::factory()->create();

        StorePrice::create([
            'store_id' => $toko->id,
            'product_id' => $produk->id,
            'selling_price' => 6000,
            'fee_type' => 'percent',
            'fee_value' => 10,
            'effective_from' => now()->subDay()->toDateString(),
        ]);

        $this->actingAs($this->admin())->post(route('pengiriman.store'), [
            'store_id' => $toko->id,
            'sent_date' => now()->toDateString(),
            'items' => [['product_id' => $produk->id, 'qty_sent' => 10]],
        ]);

        $item = Consignment::first()->items->first();

        $this->assertSame(6000, $item->unit_price);
        $this->assertSame(600, $item->fee_per_unit);
    }

    public function test_qty_melebihi_stok_gudang_ditolak(): void
    {
        $produk = $this->produkSiap(50);
        $toko = Store::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('pengiriman.store'), [
                'store_id' => $toko->id,
                'sent_date' => now()->toDateString(),
                'items' => [['product_id' => $produk->id, 'qty_sent' => 51]],
            ])
            ->assertSessionHasErrors('items.0.qty_sent');

        $this->assertDatabaseCount('consignments', 0);
    }

    /**
     * Syarat selesai Tahap 6: contoh uji bagian B menghasilkan angka
     * yang sama di layar.
     */
    public function test_rekonsiliasi_menghasilkan_angka_contoh_uji(): void
    {
        $produk = $this->produkSiap(100);
        $toko = Store::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('pengiriman.store'), [
            'store_id' => $toko->id,
            'sent_date' => now()->subWeek()->toDateString(),
            'items' => [['product_id' => $produk->id, 'qty_sent' => 71]],
        ]);

        $pengiriman = Consignment::firstOrFail();
        $item = $pengiriman->items->first();

        $this->actingAs($admin)
            ->post(route('pengiriman.rekonsiliasi', $pengiriman), [
                'settled_date' => now()->toDateString(),
                'items' => [[
                    'id' => $item->id,
                    'qty_sold' => 58,
                    'qty_returned' => 13,
                    'qty_damaged' => 0,
                ]],
            ])
            ->assertSessionHasNoErrors();

        $pengiriman->refresh();

        $this->assertSame('settled', $pengiriman->status);
        $this->assertNotNull($pengiriman->settled_date);

        // Retur kembali ke gudang: 29 sisa + 13 retur = 42.
        $this->assertSame(42, app(StockService::class)->stokGudang($produk));

        // Angka contoh uji bagian B tampil di layar.
        $this->actingAs($admin)
            ->get(route('pengiriman.show', $pengiriman))
            ->assertOk()
            ->assertSee('Rp290.000')   // penjualan kotor
            ->assertSee('Rp29.000')    // fee toko
            ->assertSee('Rp261.000')   // setoran
            ->assertSee('Rp145.000')   // HPP
            ->assertSee('Rp116.000')   // laba kotor
            ->assertSee('81,7%');      // tingkat laku
    }

    public function test_barang_rusak_dicatat_tanpa_mengurangi_stok_gudang_lagi(): void
    {
        $produk = $this->produkSiap(100);
        $toko = Store::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('pengiriman.store'), [
            'store_id' => $toko->id,
            'sent_date' => now()->toDateString(),
            'items' => [['product_id' => $produk->id, 'qty_sent' => 50]],
        ]);

        $pengiriman = Consignment::firstOrFail();
        $item = $pengiriman->items->first();

        $this->actingAs($admin)->post(route('pengiriman.rekonsiliasi', $pengiriman), [
            'settled_date' => now()->toDateString(),
            'items' => [['id' => $item->id, 'qty_sold' => 40, 'qty_returned' => 5, 'qty_damaged' => 5]],
        ])->assertSessionHasNoErrors();

        // 100 − 50 kirim + 5 retur = 55. Rusak tidak mengurangi lagi.
        $this->assertSame(55, app(StockService::class)->stokGudang($produk));
        $this->assertDatabaseHas('stock_movements', ['type' => 'damaged', 'qty' => -5]);
    }

    public function test_jumlah_melebihi_kiriman_ditolak(): void
    {
        $produk = $this->produkSiap();
        $toko = Store::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('pengiriman.store'), [
            'store_id' => $toko->id,
            'sent_date' => now()->toDateString(),
            'items' => [['product_id' => $produk->id, 'qty_sent' => 71]],
        ]);

        $pengiriman = Consignment::firstOrFail();
        $item = $pengiriman->items->first();

        $this->actingAs($admin)
            ->post(route('pengiriman.rekonsiliasi', $pengiriman), [
                'settled_date' => now()->toDateString(),
                'items' => [['id' => $item->id, 'qty_sold' => 58, 'qty_returned' => 20, 'qty_damaged' => 0]],
            ])
            ->assertSessionHasErrors('items.0.qty_sold');

        $this->assertSame('sent', $pengiriman->refresh()->status);
    }

    public function test_rekonsiliasi_ulang_tidak_menggandakan_mutasi_stok(): void
    {
        $produk = $this->produkSiap(100);
        $toko = Store::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('pengiriman.store'), [
            'store_id' => $toko->id,
            'sent_date' => now()->toDateString(),
            'items' => [['product_id' => $produk->id, 'qty_sent' => 50]],
        ]);

        $pengiriman = Consignment::firstOrFail();
        $item = $pengiriman->items->first();

        foreach ([[40, 10, 0], [35, 15, 0]] as [$jual, $retur, $rusak]) {
            $this->actingAs($admin)->post(route('pengiriman.rekonsiliasi', $pengiriman), [
                'settled_date' => now()->toDateString(),
                'items' => [['id' => $item->id, 'qty_sold' => $jual, 'qty_returned' => $retur, 'qty_damaged' => $rusak]],
            ])->assertSessionHasNoErrors();
        }

        // 100 − 50 + 15 = 65, bukan 75.
        $this->assertSame(65, app(StockService::class)->stokGudang($produk));
        $this->assertSame(1, $pengiriman->stockMovements()->where('type', 'return_in')->count());
    }

    public function test_surat_jalan_menghasilkan_pdf(): void
    {
        $produk = $this->produkSiap();
        $toko = Store::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('pengiriman.store'), [
            'store_id' => $toko->id,
            'sent_date' => now()->toDateString(),
            'items' => [['product_id' => $produk->id, 'qty_sent' => 10]],
        ]);

        $pengiriman = Consignment::firstOrFail();

        $this->actingAs($admin)
            ->get(route('pengiriman.surat-jalan', $pengiriman))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_struk_58mm_bisa_dibuka(): void
    {
        $produk = $this->produkSiap();
        $toko = Store::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('pengiriman.store'), [
            'store_id' => $toko->id,
            'sent_date' => now()->toDateString(),
            'items' => [['product_id' => $produk->id, 'qty_sent' => 10]],
        ]);

        $this->actingAs($admin)
            ->get(route('pengiriman.struk', Consignment::firstOrFail()))
            ->assertOk()
            ->assertSee('NOTA TITIP JUAL')
            ->assertSee('58mm', false);
    }

    public function test_pemantau_tidak_bisa_membuat_pengiriman(): void
    {
        $viewer = User::factory()->viewer()->create();

        $this->actingAs($viewer)->get(route('pengiriman.index'))->assertOk();
        $this->actingAs($viewer)->get(route('pengiriman.create'))->assertForbidden();
        $this->actingAs($viewer)->post(route('pengiriman.store'), [])->assertForbidden();
    }

    public function test_daftar_pengiriman_bisa_disaring(): void
    {
        $produk = $this->produkSiap();
        $tokoA = Store::factory()->create(['name' => 'Koperasi Alfa']);
        $tokoB = Store::factory()->create(['name' => 'Kantin Beta']);
        $admin = $this->admin();

        foreach ([$tokoA, $tokoB] as $t) {
            $this->actingAs($admin)->post(route('pengiriman.store'), [
                'store_id' => $t->id,
                'sent_date' => now()->toDateString(),
                'items' => [['product_id' => $produk->id, 'qty_sent' => 5]],
            ]);
        }

        [$kirimA, $kirimB] = Consignment::orderBy('id')->get();

        // Pesan sukses dari POST terakhir masih tersimpan di sesi dan memuat
        // nomor pengiriman, jadi dikonsumsi dulu supaya tidak mengotori halaman.
        $this->actingAs($admin)->get(route('beranda'));

        // Nama kedua toko tetap muncul di kotak saringan, jadi yang diperiksa
        // adalah nomor pengiriman yang tampil di daftar.
        $this->actingAs($admin)
            ->get(route('pengiriman.index', ['toko' => $tokoA->id]))
            ->assertOk()
            ->assertSee($kirimA->number)
            ->assertDontSee($kirimB->number);
    }
}
