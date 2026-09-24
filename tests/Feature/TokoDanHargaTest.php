<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Store;
use App\Models\StorePrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokoDanHargaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_bisa_menambah_toko(): void
    {
        $this->actingAs($this->admin())
            ->post(route('toko.store'), [
                'code' => 'TK-010',
                'name' => 'Koperasi Budi Utama',
                'type' => 'koperasi',
                'contact_person' => 'Ibu Sri',
                'phone' => '081298765432',
                'payment_term_days' => 7,
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors();

        $toko = Store::where('code', 'TK-010')->firstOrFail();

        $this->assertSame('koperasi', $toko->type);
        $this->assertSame(7, $toko->tempoBayar());
    }

    public function test_tempo_bayar_kosong_memakai_default_pengaturan(): void
    {
        $toko = Store::factory()->create(['payment_term_days' => null]);

        $this->assertSame(7, $toko->tempoBayar());
    }

    public function test_nomor_whatsapp_diubah_ke_format_internasional(): void
    {
        $this->assertSame('6281298765432', Store::factory()->make(['phone' => '081298765432'])->nomorWhatsapp());
        $this->assertSame('6281298765432', Store::factory()->make(['phone' => '0812-9876-5432'])->nomorWhatsapp());
        $this->assertSame('6281298765432', Store::factory()->make(['phone' => '+62 812 9876 5432'])->nomorWhatsapp());
        $this->assertNull(Store::factory()->make(['phone' => null])->nomorWhatsapp());
    }

    /**
     * Syarat selesai Tahap 5: fee persen 10% dari Rp5.000 menampilkan
     * fee Rp500 dan setoran Rp4.500.
     */
    public function test_tab_harga_menampilkan_fee_persen_dan_setoran(): void
    {
        $toko = Store::factory()->create();
        Product::factory()->create([
            'name' => 'Sutaco Taro',
            'cost_price' => 2500,
            'default_selling_price' => 5000,
            'default_fee_type' => 'percent',
            'default_fee_value' => 10,
        ]);

        $this->actingAs($this->admin())
            ->get(route('toko.harga', $toko))
            ->assertOk()
            ->assertSee('Sutaco Taro')
            ->assertSee('Rp500')      // fee 10% dari Rp5.000
            ->assertSee('Rp4.500')    // setoran
            ->assertSee('Default');
    }

    public function test_harga_khusus_toko_bisa_disimpan(): void
    {
        $toko = Store::factory()->create();
        $produk = Product::factory()->create(['default_selling_price' => 5000]);

        $this->actingAs($this->admin())
            ->post(route('toko.harga.simpan', $toko), [
                'product_id' => $produk->id,
                'selling_price' => 6000,
                'fee_type' => 'percent',
                'fee_value' => 10,
                'effective_from' => '2026-09-01',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('toko.harga', $toko));

        $this->assertDatabaseHas('store_prices', [
            'store_id' => $toko->id,
            'product_id' => $produk->id,
            'selling_price' => 6000,
            'fee_type' => 'percent',
            'fee_value' => 10,
        ]);

        // Setelah disimpan, tabel menandai harga sebagai "Khusus toko".
        $this->actingAs($this->admin())
            ->get(route('toko.harga', $toko).'?tanggal=2026-09-23')
            ->assertSee('Khusus toko')
            ->assertSee('Rp5.400');   // setoran 6.000 − 600
    }

    public function test_fee_persen_lebih_dari_seratus_ditolak(): void
    {
        $toko = Store::factory()->create();
        $produk = Product::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('toko.harga.simpan', $toko), [
                'product_id' => $produk->id,
                'selling_price' => 5000,
                'fee_type' => 'percent',
                'fee_value' => 120,
                'effective_from' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('fee_value');
    }

    public function test_fee_nominal_melebihi_harga_jual_ditolak(): void
    {
        $toko = Store::factory()->create();
        $produk = Product::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('toko.harga.simpan', $toko), [
                'product_id' => $produk->id,
                'selling_price' => 5000,
                'fee_type' => 'nominal',
                'fee_value' => 6000,
                'effective_from' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('fee_value');
    }

    public function test_harga_khusus_bisa_dihapus(): void
    {
        $toko = Store::factory()->create();
        $produk = Product::factory()->create();

        $harga = StorePrice::create([
            'store_id' => $toko->id,
            'product_id' => $produk->id,
            'selling_price' => 6000,
            'fee_type' => 'nominal',
            'fee_value' => 600,
            'effective_from' => '2026-09-01',
        ]);

        $this->actingAs($this->admin())
            ->delete(route('toko.harga.hapus', [$toko, $harga]))
            ->assertSessionHas('sukses');

        $this->assertDatabaseMissing('store_prices', ['id' => $harga->id]);
    }

    public function test_harga_toko_lain_tidak_bisa_dihapus_dari_toko_ini(): void
    {
        $tokoA = Store::factory()->create();
        $tokoB = Store::factory()->create();
        $produk = Product::factory()->create();

        $harga = StorePrice::create([
            'store_id' => $tokoA->id,
            'product_id' => $produk->id,
            'selling_price' => 6000,
            'fee_type' => 'nominal',
            'fee_value' => 600,
            'effective_from' => '2026-09-01',
        ]);

        $this->actingAs($this->admin())
            ->delete(route('toko.harga.hapus', [$tokoB, $harga]))
            ->assertNotFound();

        $this->assertDatabaseHas('store_prices', ['id' => $harga->id]);
    }

    public function test_pemantau_tidak_bisa_mengubah_harga(): void
    {
        $toko = Store::factory()->create();
        $produk = Product::factory()->create();
        $viewer = User::factory()->viewer()->create();

        $this->actingAs($viewer)->get(route('toko.harga', $toko))->assertOk();

        $this->actingAs($viewer)
            ->post(route('toko.harga.simpan', $toko), [
                'product_id' => $produk->id,
                'selling_price' => 9000,
                'fee_type' => 'nominal',
                'fee_value' => 500,
                'effective_from' => now()->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_riwayat_harga_per_toko_tampil(): void
    {
        $toko = Store::factory()->create();
        $produk = Product::factory()->create(['name' => 'Piscok Krispi']);

        StorePrice::create([
            'store_id' => $toko->id,
            'product_id' => $produk->id,
            'selling_price' => 6500,
            'fee_type' => 'nominal',
            'fee_value' => 650,
            'effective_from' => '2026-08-01',
        ]);

        $this->actingAs($this->admin())
            ->get(route('toko.harga', $toko))
            ->assertOk()
            ->assertSee('Piscok Krispi')
            ->assertSee('Rp6.500')
            ->assertSee('1 Agustus 2026');
    }
}
