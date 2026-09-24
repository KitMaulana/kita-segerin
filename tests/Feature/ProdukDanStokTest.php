<?php

namespace Tests\Feature;

use App\Models\CashTransaction;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdukDanStokTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_bisa_menambah_produk(): void
    {
        $this->actingAs($this->admin())
            ->post(route('produk.store'), [
                'code' => 'ES-010',
                'name' => 'Sutaco Taro',
                'variant' => 'cup',
                'unit' => 'pcs',
                'cost_price' => 2500,
                'default_selling_price' => 5000,
                'default_fee_type' => 'nominal',
                'default_fee_value' => 500,
                'min_stock' => 50,
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors();

        $produk = Product::where('code', 'ES-010')->firstOrFail();

        $this->assertSame(2500, $produk->cost_price);
        // Harga modal awal ikut tercatat di riwayat.
        $this->assertDatabaseHas('product_cost_histories', [
            'product_id' => $produk->id,
            'old_price' => 0,
            'new_price' => 2500,
            'source' => 'manual',
        ]);
    }

    public function test_perubahan_harga_modal_tercatat_di_riwayat(): void
    {
        $produk = Product::factory()->create(['cost_price' => 2500]);

        $this->actingAs($this->admin())
            ->put(route('produk.update', $produk), [
                'code' => $produk->code,
                'name' => $produk->name,
                'variant' => $produk->variant,
                'unit' => 'pcs',
                'cost_price' => 2800,
                'default_selling_price' => 5000,
                'default_fee_type' => 'nominal',
                'default_fee_value' => 500,
                'min_stock' => 20,
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2800, $produk->refresh()->cost_price);
        $this->assertDatabaseHas('product_cost_histories', [
            'product_id' => $produk->id,
            'old_price' => 2500,
            'new_price' => 2800,
        ]);
    }

    public function test_fee_persen_lebih_dari_seratus_ditolak(): void
    {
        $this->actingAs($this->admin())
            ->post(route('produk.store'), [
                'code' => 'ES-011', 'name' => 'Coba', 'variant' => 'cup', 'unit' => 'pcs',
                'cost_price' => 2500, 'default_selling_price' => 5000,
                'default_fee_type' => 'percent', 'default_fee_value' => 150,
                'min_stock' => 0, 'is_active' => '1',
            ])
            ->assertSessionHasErrors('default_fee_value');
    }

    public function test_fee_nominal_melebihi_harga_jual_ditolak(): void
    {
        $this->actingAs($this->admin())
            ->post(route('produk.store'), [
                'code' => 'ES-012', 'name' => 'Coba', 'variant' => 'cup', 'unit' => 'pcs',
                'cost_price' => 2500, 'default_selling_price' => 5000,
                'default_fee_type' => 'nominal', 'default_fee_value' => 6000,
                'min_stock' => 0, 'is_active' => '1',
            ])
            ->assertSessionHasErrors('default_fee_value');
    }

    public function test_pemantau_tidak_bisa_menambah_produk(): void
    {
        $viewer = User::factory()->viewer()->create();

        $this->actingAs($viewer)->get(route('produk.index'))->assertOk();
        $this->actingAs($viewer)->get(route('produk.create'))->assertForbidden();
        $this->actingAs($viewer)->post(route('produk.store'), [])->assertForbidden();
    }

    /**
     * Syarat selesai Tahap 4: membeli 100 pcs membuat stok gudang +100
     * dan buku kas keluar sesuai total.
     */
    public function test_pembelian_menambah_stok_dan_mencatat_kas_keluar(): void
    {
        $produk = Product::factory()->create(['cost_price' => 2500]);
        $pemasok = Supplier::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('pembelian.store'), [
                'supplier_id' => $pemasok->id,
                'purchase_date' => now()->toDateString(),
                'items' => [
                    ['product_id' => $produk->id, 'qty' => 100, 'unit_cost' => 2500],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(100, app(StockService::class)->stokGudang($produk));

        $this->assertDatabaseHas('purchases', ['total' => 250_000]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $produk->id,
            'type' => 'purchase_in',
            'qty' => 100,
        ]);

        $kas = CashTransaction::where('category', 'purchase')->firstOrFail();

        $this->assertSame('out', $kas->direction);
        $this->assertSame(250_000, $kas->amount);
    }

    public function test_pembelian_bisa_memperbarui_harga_modal(): void
    {
        $produk = Product::factory()->create(['cost_price' => 2500]);

        $this->actingAs($this->admin())
            ->post(route('pembelian.store'), [
                'purchase_date' => now()->toDateString(),
                'update_cost_price' => '1',
                'items' => [['product_id' => $produk->id, 'qty' => 50, 'unit_cost' => 2700]],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2700, $produk->refresh()->cost_price);
        $this->assertDatabaseHas('product_cost_histories', [
            'product_id' => $produk->id,
            'old_price' => 2500,
            'new_price' => 2700,
            'source' => 'purchase',
        ]);
    }

    public function test_pembelian_tanpa_opsi_tidak_mengubah_harga_modal(): void
    {
        $produk = Product::factory()->create(['cost_price' => 2500]);

        $this->actingAs($this->admin())
            ->post(route('pembelian.store'), [
                'purchase_date' => now()->toDateString(),
                'items' => [['product_id' => $produk->id, 'qty' => 50, 'unit_cost' => 2700]],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2500, $produk->refresh()->cost_price);
    }

    public function test_pembelian_tanpa_baris_produk_ditolak(): void
    {
        $this->actingAs($this->admin())
            ->post(route('pembelian.store'), [
                'purchase_date' => now()->toDateString(),
                'items' => [],
            ])
            ->assertSessionHasErrors('items');
    }

    public function test_produk_sama_dua_kali_dalam_satu_pembelian_ditolak(): void
    {
        $produk = Product::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('pembelian.store'), [
                'purchase_date' => now()->toDateString(),
                'items' => [
                    ['product_id' => $produk->id, 'qty' => 10, 'unit_cost' => 2500],
                    ['product_id' => $produk->id, 'qty' => 5, 'unit_cost' => 2500],
                ],
            ])
            ->assertSessionHasErrors('items.1.product_id');
    }

    public function test_nomor_pembelian_mengikuti_pola(): void
    {
        $produk = Product::factory()->create();

        $this->actingAs($this->admin())->post(route('pembelian.store'), [
            'purchase_date' => '2026-09-23',
            'items' => [['product_id' => $produk->id, 'qty' => 10, 'unit_cost' => 2500]],
        ]);

        $this->assertDatabaseHas('purchases', ['number' => 'BLI/2026/09/0001']);
    }

    public function test_penyesuaian_stok_wajib_disertai_alasan(): void
    {
        $produk = Product::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('stok.penyesuaian.store'), [
                'product_id' => $produk->id,
                'date' => now()->toDateString(),
                'stok_baru' => 10,
                'notes' => '',
            ])
            ->assertSessionHasErrors('notes');
    }

    public function test_penyesuaian_stok_mencatat_selisih(): void
    {
        $produk = Product::factory()->create();
        $stok = app(StockService::class);
        $stok->catat($produk, 'purchase_in', 100);

        $this->actingAs($this->admin())
            ->post(route('stok.penyesuaian.store'), [
                'product_id' => $produk->id,
                'date' => now()->toDateString(),
                'stok_baru' => 93,
                'notes' => 'Tujuh pcs meleleh karena freezer mati',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(93, $stok->stokGudang($produk));
        $this->assertDatabaseHas('stock_movements', ['type' => 'adjustment', 'qty' => -7]);
    }

    public function test_halaman_stok_menampilkan_nilai_persediaan(): void
    {
        $produk = Product::factory()->create(['cost_price' => 2500, 'name' => 'Sutaco Taro']);
        app(StockService::class)->catat($produk, 'purchase_in', 100);

        $this->actingAs($this->admin())
            ->get(route('stok.index'))
            ->assertOk()
            ->assertSee('Sutaco Taro')
            ->assertSee('Rp250.000');
    }

    public function test_produk_dengan_transaksi_tidak_bisa_dihapus(): void
    {
        $produk = Product::factory()->create();
        app(StockService::class)->catat($produk, 'purchase_in', 10);

        $this->actingAs(User::factory()->owner()->create())
            ->delete(route('produk.destroy', $produk))
            ->assertSessionHas('gagal');

        $this->assertNotNull($produk->fresh());
    }

    public function test_pemasok_dengan_produk_tidak_bisa_dihapus(): void
    {
        $pemasok = Supplier::factory()->create();
        Product::factory()->create(['supplier_id' => $pemasok->id]);

        $this->actingAs(User::factory()->owner()->create())
            ->delete(route('pemasok.destroy', $pemasok))
            ->assertSessionHas('gagal');

        $this->assertNotNull($pemasok->fresh());
    }

    public function test_pemasok_tanpa_transaksi_bisa_dihapus(): void
    {
        $pemasok = Supplier::factory()->create();

        $this->actingAs(User::factory()->owner()->create())
            ->delete(route('pemasok.destroy', $pemasok))
            ->assertSessionHas('sukses');

        $this->assertSoftDeleted($pemasok);
    }
}
