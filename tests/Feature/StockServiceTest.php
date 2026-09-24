<?php

namespace Tests\Feature;

use App\Models\Consignment;
use App\Models\ConsignmentItem;
use App\Models\Product;
use App\Models\Store;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stok;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stok = app(StockService::class);
    }

    public function test_stok_gudang_dijumlahkan_dari_mutasi(): void
    {
        $produk = Product::factory()->create();

        $this->stok->catat($produk, 'purchase_in', 100);
        $this->stok->catat($produk, 'consignment_out', -30);
        $this->stok->catat($produk, 'return_in', 5);

        $this->assertSame(75, $this->stok->stokGudang($produk));
    }

    public function test_barang_rusak_tidak_mengurangi_stok_gudang_lagi(): void
    {
        $produk = Product::factory()->create();

        $this->stok->catat($produk, 'purchase_in', 100);
        $this->stok->catat($produk, 'consignment_out', -50);
        // Barang rusak sudah keluar gudang saat dikirim, jadi hanya dicatat.
        $this->stok->catat($produk, 'damaged', -3);

        $this->assertSame(50, $this->stok->stokGudang($produk));
    }

    public function test_penyesuaian_manual_mengubah_stok(): void
    {
        $produk = Product::factory()->create();

        $this->stok->catat($produk, 'purchase_in', 100);
        $this->stok->catat($produk, 'adjustment', -7, catatan: 'Hilang saat bongkar muat');

        $this->assertSame(93, $this->stok->stokGudang($produk));
    }

    public function test_stok_di_toko_menghitung_titipan_yang_belum_direkonsiliasi(): void
    {
        $produk = Product::factory()->create();
        $toko = Store::factory()->create();

        $kirim = Consignment::create([
            'number' => 'KRM/2026/09/0001',
            'store_id' => $toko->id,
            'sent_date' => '2026-09-20',
            'status' => 'sent',
        ]);

        ConsignmentItem::create([
            'consignment_id' => $kirim->id,
            'product_id' => $produk->id,
            'qty_sent' => 71,
            'qty_sold' => 10,
            'qty_returned' => 1,
            'qty_damaged' => 0,
            'unit_cost' => 2500,
            'unit_price' => 5000,
            'fee_per_unit' => 500,
        ]);

        $this->assertSame(60, $this->stok->stokDiToko($produk));
    }

    public function test_pengiriman_yang_sudah_direkonsiliasi_tidak_dihitung_sebagai_titipan(): void
    {
        $produk = Product::factory()->create();
        $toko = Store::factory()->create();

        $kirim = Consignment::create([
            'number' => 'KRM/2026/09/0002',
            'store_id' => $toko->id,
            'sent_date' => '2026-09-20',
            'settled_date' => '2026-09-27',
            'status' => 'settled',
        ]);

        ConsignmentItem::create([
            'consignment_id' => $kirim->id,
            'product_id' => $produk->id,
            'qty_sent' => 71, 'qty_sold' => 58, 'qty_returned' => 13, 'qty_damaged' => 0,
            'unit_cost' => 2500, 'unit_price' => 5000, 'fee_per_unit' => 500,
        ]);

        $this->assertSame(0, $this->stok->stokDiToko($produk));
    }

    public function test_nilai_persediaan_memakai_harga_modal(): void
    {
        $produk = Product::factory()->create(['cost_price' => 2500]);

        $this->stok->catat($produk, 'purchase_in', 100);

        $this->assertSame(250_000, $this->stok->nilaiPersediaan());
    }

    public function test_menandai_stok_menipis(): void
    {
        $aman = Product::factory()->create(['name' => 'Stok Aman', 'min_stock' => 10]);
        $tipis = Product::factory()->create(['name' => 'Stok Tipis', 'min_stock' => 50]);

        $this->stok->catat($aman, 'purchase_in', 100);
        $this->stok->catat($tipis, 'purchase_in', 40);

        $menipis = $this->stok->stokMenipis();

        $this->assertCount(1, $menipis);
        $this->assertSame('Stok Tipis', $menipis->first()['produk']->name);
    }

    public function test_periksa_ketersediaan_stok(): void
    {
        $produk = Product::factory()->create();
        $this->stok->catat($produk, 'purchase_in', 50);

        $this->assertTrue($this->stok->periksaKetersediaan($produk, 50)['cukup']);
        $this->assertFalse($this->stok->periksaKetersediaan($produk, 51)['cukup']);
        $this->assertSame(50, $this->stok->periksaKetersediaan($produk, 51)['tersedia']);
    }
}
