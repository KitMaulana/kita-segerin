<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Store;
use App\Models\StorePrice;
use App\Services\FinanceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji pengambilan harga: harga khusus toko bila ada dan sudah berlaku,
 * selain itu harga default produk.
 */
class ResolvePriceTest extends TestCase
{
    use RefreshDatabase;

    private FinanceCalculator $hitung;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hitung = app(FinanceCalculator::class);
    }

    public function test_memakai_harga_default_produk_bila_tidak_ada_harga_khusus(): void
    {
        $toko = Store::factory()->create();
        $produk = Product::factory()->create([
            'cost_price' => 2500,
            'default_selling_price' => 5000,
            'default_fee_type' => 'nominal',
            'default_fee_value' => 500,
        ]);

        $harga = $this->hitung->resolvePrice($toko, $produk, '2026-09-23');

        $this->assertSame('default', $harga['sumber']);
        $this->assertSame(2500, $harga['unit_cost']);
        $this->assertSame(5000, $harga['unit_price']);
        $this->assertSame(500, $harga['fee_per_unit']);
        $this->assertSame(4500, $harga['setoran_per_pcs']);
        $this->assertSame(2000, $harga['laba_per_pcs']);
    }

    public function test_memakai_harga_khusus_toko_bila_sudah_berlaku(): void
    {
        $toko = Store::factory()->create();
        $produk = Product::factory()->create([
            'cost_price' => 2500,
            'default_selling_price' => 5000,
            'default_fee_type' => 'nominal',
            'default_fee_value' => 500,
        ]);

        StorePrice::create([
            'store_id' => $toko->id,
            'product_id' => $produk->id,
            'selling_price' => 6000,
            'fee_type' => 'percent',
            'fee_value' => 10,
            'effective_from' => '2026-09-01',
        ]);

        $harga = $this->hitung->resolvePrice($toko, $produk, '2026-09-23');

        $this->assertSame('khusus', $harga['sumber']);
        $this->assertSame(6000, $harga['unit_price']);
        $this->assertSame(600, $harga['fee_per_unit']);    // 10% dari 6.000
        $this->assertSame(5400, $harga['setoran_per_pcs']);
        $this->assertSame(2900, $harga['laba_per_pcs']);
    }

    public function test_harga_khusus_yang_belum_berlaku_diabaikan(): void
    {
        $toko = Store::factory()->create();
        $produk = Product::factory()->create(['default_selling_price' => 5000]);

        StorePrice::create([
            'store_id' => $toko->id,
            'product_id' => $produk->id,
            'selling_price' => 7000,
            'fee_type' => 'nominal',
            'fee_value' => 700,
            'effective_from' => '2026-10-01',
        ]);

        $harga = $this->hitung->resolvePrice($toko, $produk, '2026-09-23');

        $this->assertSame('default', $harga['sumber']);
        $this->assertSame(5000, $harga['unit_price']);
    }

    public function test_memakai_harga_khusus_terbaru_yang_sudah_berlaku(): void
    {
        $toko = Store::factory()->create();
        $produk = Product::factory()->create(['default_selling_price' => 5000]);

        foreach ([['2026-08-01', 5500], ['2026-09-10', 6000], ['2026-11-01', 7000]] as [$mulai, $harga]) {
            StorePrice::create([
                'store_id' => $toko->id,
                'product_id' => $produk->id,
                'selling_price' => $harga,
                'fee_type' => 'nominal',
                'fee_value' => 500,
                'effective_from' => $mulai,
            ]);
        }

        $this->assertSame(6000, $this->hitung->resolvePrice($toko, $produk, '2026-09-23')['unit_price']);
        $this->assertSame(5500, $this->hitung->resolvePrice($toko, $produk, '2026-08-15')['unit_price']);
        $this->assertSame(7000, $this->hitung->resolvePrice($toko, $produk, '2026-12-01')['unit_price']);
    }

    public function test_harga_khusus_toko_lain_tidak_terpakai(): void
    {
        $tokoA = Store::factory()->create();
        $tokoB = Store::factory()->create();
        $produk = Product::factory()->create(['default_selling_price' => 5000]);

        StorePrice::create([
            'store_id' => $tokoA->id,
            'product_id' => $produk->id,
            'selling_price' => 9000,
            'fee_type' => 'nominal',
            'fee_value' => 500,
            'effective_from' => '2026-01-01',
        ]);

        $this->assertSame(5000, $this->hitung->resolvePrice($tokoB, $produk, '2026-09-23')['unit_price']);
    }
}
