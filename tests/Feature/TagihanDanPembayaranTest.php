<?php

namespace Tests\Feature;

use App\Models\CashTransaction;
use App\Models\Consignment;
use App\Models\ConsignmentItem;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagihanDanPembayaranTest extends TestCase
{
    use RefreshDatabase;

    private Store $toko;

    private Product $produk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->toko = Store::factory()->create(['name' => 'Koperasi Budi Utama', 'payment_term_days' => 7]);
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
     * Membuat satu pengiriman yang sudah direkonsiliasi memakai angka
     * contoh uji bagian B: kirim 71, terjual 58, retur 13.
     */
    private function pengirimanSettled(int $terjual = 58, int $retur = 13, int $rusak = 0): Consignment
    {
        $pengiriman = Consignment::create([
            'number' => 'KRM/2026/09/'.str_pad((string) (Consignment::count() + 1), 4, '0', STR_PAD_LEFT),
            'store_id' => $this->toko->id,
            'sent_date' => '2026-09-15',
            'settled_date' => '2026-09-22',
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

    private function buatTagihan(Consignment ...$pengiriman): Invoice
    {
        $this->actingAs($this->admin())
            ->post(route('tagihan.store'), [
                'store_id' => $this->toko->id,
                'invoice_date' => '2026-09-23',
                'due_date' => '2026-09-30',
                'consignment_ids' => collect($pengiriman)->pluck('id')->all(),
            ])
            ->assertSessionHasNoErrors();

        return Invoice::latest('id')->firstOrFail();
    }

    public function test_tagihan_menghitung_total_dari_pengiriman(): void
    {
        $tagihan = $this->buatTagihan($this->pengirimanSettled());

        $this->assertSame('INV/KS/2026/09/0001', $tagihan->number);
        $this->assertSame(290_000, $tagihan->gross_total);
        $this->assertSame(29_000, $tagihan->fee_total);
        $this->assertSame(261_000, $tagihan->amount_due);
        $this->assertSame('unpaid', $tagihan->status);
    }

    public function test_pengiriman_terkunci_setelah_ditagih(): void
    {
        $pengiriman = $this->pengirimanSettled();
        $tagihan = $this->buatTagihan($pengiriman);

        $pengiriman->refresh();

        $this->assertSame('invoiced', $pengiriman->status);
        $this->assertSame($tagihan->id, $pengiriman->invoice_id);
        $this->assertTrue($pengiriman->terkunci());

        // Rekonsiliasi ulang ditolak.
        $this->actingAs($this->admin())
            ->post(route('pengiriman.rekonsiliasi', $pengiriman), [
                'settled_date' => '2026-09-25',
                'items' => [['id' => $pengiriman->items->first()->id, 'qty_sold' => 71, 'qty_returned' => 0, 'qty_damaged' => 0]],
            ])
            ->assertSessionHas('gagal');
    }

    public function test_rincian_tagihan_adalah_salinan(): void
    {
        $tagihan = $this->buatTagihan($this->pengirimanSettled());
        $baris = $tagihan->items->first();

        $this->assertSame('Sutaco Taro', $baris->product_name);
        $this->assertSame(58, $baris->qty_sold);
        $this->assertSame(5000, $baris->unit_price);
        $this->assertSame(500, $baris->fee_per_unit);
        $this->assertSame(4500, $baris->net_per_unit);
        $this->assertSame(261_000, $baris->subtotal);

        // Ubah nama dan harga produk: rincian tagihan tidak ikut berubah.
        $this->produk->update(['name' => 'Nama Baru', 'default_selling_price' => 9999]);

        $this->assertSame('Sutaco Taro', $baris->refresh()->product_name);
        $this->assertSame(5000, $baris->unit_price);
    }

    public function test_tagihan_bisa_menggabungkan_beberapa_pengiriman(): void
    {
        $tagihan = $this->buatTagihan($this->pengirimanSettled(), $this->pengirimanSettled(40, 31));

        $this->assertSame(2, $tagihan->consignments()->count());
        $this->assertSame(490_000, $tagihan->gross_total);   // 290.000 + 200.000
        $this->assertSame(49_000, $tagihan->fee_total);
        $this->assertSame(441_000, $tagihan->amount_due);
    }

    public function test_pengiriman_yang_sudah_ditagih_tidak_bisa_ditagih_lagi(): void
    {
        $pengiriman = $this->pengirimanSettled();
        $this->buatTagihan($pengiriman);

        $this->actingAs($this->admin())
            ->post(route('tagihan.store'), [
                'store_id' => $this->toko->id,
                'invoice_date' => '2026-09-24',
                'due_date' => '2026-10-01',
                'consignment_ids' => [$pengiriman->id],
            ])
            ->assertSessionHasErrors('consignment_ids');

        $this->assertSame(1, Invoice::count());
    }

    public function test_tagihan_tanpa_pengiriman_ditolak(): void
    {
        $this->actingAs($this->admin())
            ->post(route('tagihan.store'), [
                'store_id' => $this->toko->id,
                'invoice_date' => '2026-09-23',
                'due_date' => '2026-09-30',
                'consignment_ids' => [],
            ])
            ->assertSessionHasErrors('consignment_ids');
    }

    public function test_jatuh_tempo_sebelum_tanggal_tagihan_ditolak(): void
    {
        $pengiriman = $this->pengirimanSettled();

        $this->actingAs($this->admin())
            ->post(route('tagihan.store'), [
                'store_id' => $this->toko->id,
                'invoice_date' => '2026-09-23',
                'due_date' => '2026-09-20',
                'consignment_ids' => [$pengiriman->id],
            ])
            ->assertSessionHasErrors('due_date');
    }

    /**
     * Syarat selesai Tahap 7: pembayaran sebagian membuat status "Sebagian".
     */
    public function test_pembayaran_sebagian_membuat_status_sebagian(): void
    {
        $tagihan = $this->buatTagihan($this->pengirimanSettled());

        $this->actingAs($this->admin())
            ->post(route('pembayaran.store', $tagihan), [
                'paid_at' => now()->toDateString(),
                'amount' => 100_000,
                'method' => 'transfer',
            ])
            ->assertSessionHasNoErrors();

        $tagihan->refresh();

        $this->assertSame('partial', $tagihan->status);
        $this->assertSame('Sebagian', $tagihan->namaStatus());
        $this->assertSame(100_000, $tagihan->amount_paid);
        $this->assertSame(161_000, $tagihan->sisaTagihan());
    }

    public function test_pembayaran_penuh_membuat_status_lunas(): void
    {
        $tagihan = $this->buatTagihan($this->pengirimanSettled());

        $this->actingAs($this->admin())->post(route('pembayaran.store', $tagihan), [
            'paid_at' => now()->toDateString(),
            'amount' => 261_000,
            'method' => 'cash',
        ])->assertSessionHasNoErrors();

        $this->assertSame('paid', $tagihan->refresh()->status);
        $this->assertSame(0, $tagihan->sisaTagihan());
    }

    public function test_pembayaran_bertahap_sampai_lunas(): void
    {
        $tagihan = $this->buatTagihan($this->pengirimanSettled());

        foreach ([100_000, 161_000] as $jumlah) {
            $this->actingAs($this->admin())->post(route('pembayaran.store', $tagihan), [
                'paid_at' => now()->toDateString(),
                'amount' => $jumlah,
                'method' => 'transfer',
            ])->assertSessionHasNoErrors();
        }

        $this->assertSame('paid', $tagihan->refresh()->status);
        $this->assertSame(2, $tagihan->payments()->count());
    }

    public function test_pembayaran_melebihi_sisa_ditolak(): void
    {
        $tagihan = $this->buatTagihan($this->pengirimanSettled());

        $this->actingAs($this->admin())
            ->post(route('pembayaran.store', $tagihan), [
                'paid_at' => now()->toDateString(),
                'amount' => 300_000,
                'method' => 'cash',
            ])
            ->assertSessionHasErrors('amount');
    }

    public function test_pembayaran_otomatis_masuk_buku_kas(): void
    {
        $tagihan = $this->buatTagihan($this->pengirimanSettled());

        $this->actingAs($this->admin())->post(route('pembayaran.store', $tagihan), [
            'paid_at' => now()->toDateString(),
            'amount' => 261_000,
            'method' => 'transfer',
        ]);

        $kas = CashTransaction::where('category', 'sales_payment')->firstOrFail();

        $this->assertSame('in', $kas->direction);
        $this->assertSame(261_000, $kas->amount);
    }

    public function test_nomor_pembayaran_mengikuti_pola(): void
    {
        $tagihan = $this->buatTagihan($this->pengirimanSettled());

        $this->actingAs($this->admin())->post(route('pembayaran.store', $tagihan), [
            'paid_at' => now()->toDateString(),
            'amount' => 50_000,
            'method' => 'cash',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payments', [
            'number' => 'BYR/'.now()->format('Y/m').'/0001',
        ]);
    }

    public function test_tagihan_pdf_memuat_terbilang_dan_total(): void
    {
        $tagihan = $this->buatTagihan($this->pengirimanSettled());

        $this->actingAs($this->admin())
            ->get(route('tagihan.cetak', $tagihan))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        // Isi terbilang diuji lewat versi struk yang berupa HTML.
        $this->actingAs($this->admin())
            ->get(route('tagihan.struk', $tagihan))
            ->assertOk()
            ->assertSee('Rp261.000')
            ->assertSee('dua ratus enam puluh satu ribu rupiah');
    }

    public function test_pemilik_bisa_membatalkan_tagihan_tanpa_pembayaran(): void
    {
        $pengiriman = $this->pengirimanSettled();
        $tagihan = $this->buatTagihan($pengiriman);

        $this->actingAs(User::factory()->owner()->create())
            ->patch(route('tagihan.batalkan', $tagihan), ['cancel_reason' => 'Salah pilih pengiriman'])
            ->assertSessionHasNoErrors();

        $tagihan->refresh();
        $pengiriman->refresh();

        $this->assertSame('cancelled', $tagihan->status);
        $this->assertSame('settled', $pengiriman->status);
        $this->assertNull($pengiriman->invoice_id);
    }

    public function test_pembatalan_wajib_disertai_alasan(): void
    {
        $tagihan = $this->buatTagihan($this->pengirimanSettled());

        $this->actingAs(User::factory()->owner()->create())
            ->patch(route('tagihan.batalkan', $tagihan), ['cancel_reason' => ''])
            ->assertSessionHasErrors('cancel_reason');

        $this->assertSame('unpaid', $tagihan->refresh()->status);
    }

    public function test_tagihan_yang_sudah_dibayar_tidak_bisa_dibatalkan(): void
    {
        $tagihan = $this->buatTagihan($this->pengirimanSettled());

        $this->actingAs($this->admin())->post(route('pembayaran.store', $tagihan), [
            'paid_at' => now()->toDateString(),
            'amount' => 50_000,
            'method' => 'cash',
        ]);

        $this->actingAs(User::factory()->owner()->create())
            ->patch(route('tagihan.batalkan', $tagihan), ['cancel_reason' => 'Mau dikoreksi'])
            ->assertSessionHas('gagal');

        $this->assertNotSame('cancelled', $tagihan->refresh()->status);
    }

    public function test_admin_tidak_bisa_membatalkan_tagihan(): void
    {
        $tagihan = $this->buatTagihan($this->pengirimanSettled());

        $this->actingAs($this->admin())
            ->patch(route('tagihan.batalkan', $tagihan), ['cancel_reason' => 'Coba batalkan'])
            ->assertForbidden();
    }

    public function test_tagihan_yang_dibatalkan_bisa_ditagih_ulang(): void
    {
        $pengiriman = $this->pengirimanSettled();
        $tagihan = $this->buatTagihan($pengiriman);

        $this->actingAs(User::factory()->owner()->create())
            ->patch(route('tagihan.batalkan', $tagihan), ['cancel_reason' => 'Salah tanggal']);

        $tagihanBaru = $this->buatTagihan($pengiriman->refresh());

        $this->assertSame(261_000, $tagihanBaru->amount_due);
        $this->assertSame('invoiced', $pengiriman->refresh()->status);
    }

    public function test_menghapus_pembayaran_mengembalikan_status(): void
    {
        $tagihan = $this->buatTagihan($this->pengirimanSettled());

        $this->actingAs($this->admin())->post(route('pembayaran.store', $tagihan), [
            'paid_at' => now()->toDateString(),
            'amount' => 261_000,
            'method' => 'cash',
        ]);

        $this->assertSame('paid', $tagihan->refresh()->status);

        $pembayaran = Payment::firstOrFail();

        $this->actingAs(User::factory()->owner()->create())
            ->delete(route('pembayaran.destroy', $pembayaran))
            ->assertSessionHas('sukses');

        $tagihan->refresh();

        $this->assertSame('unpaid', $tagihan->status);
        $this->assertSame(0, $tagihan->amount_paid);
        $this->assertSame(0, CashTransaction::where('category', 'sales_payment')->count());
    }

    public function test_tagihan_lewat_jatuh_tempo_ditandai(): void
    {
        $tagihan = $this->buatTagihan($this->pengirimanSettled());
        $tagihan->update(['due_date' => now()->subDays(5)->toDateString()]);

        $this->assertTrue($tagihan->refresh()->lewatJatuhTempo());
        $this->assertSame('strawberry', $tagihan->warnaStatus());

        $this->actingAs($this->admin())
            ->get(route('tagihan.index'))
            ->assertOk()
            ->assertSee('Lewat tempo');
    }

    public function test_tombol_whatsapp_memuat_nomor_toko(): void
    {
        $this->toko->update(['phone' => '081298765432']);
        $tagihan = $this->buatTagihan($this->pengirimanSettled());

        $this->actingAs($this->admin())
            ->get(route('tagihan.show', $tagihan))
            ->assertOk()
            ->assertSee('wa.me/6281298765432', false)
            ->assertSee('Kirim via WhatsApp');
    }

    public function test_kuitansi_pembayaran_bisa_dicetak(): void
    {
        $tagihan = $this->buatTagihan($this->pengirimanSettled());

        $this->actingAs($this->admin())->post(route('pembayaran.store', $tagihan), [
            'paid_at' => now()->toDateString(),
            'amount' => 261_000,
            'method' => 'cash',
        ]);

        $this->actingAs($this->admin())
            ->get(route('pembayaran.kuitansi', Payment::firstOrFail()))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_pemantau_tidak_bisa_membuat_tagihan(): void
    {
        $viewer = User::factory()->viewer()->create();

        $this->actingAs($viewer)->get(route('tagihan.index'))->assertOk();
        $this->actingAs($viewer)->get(route('tagihan.create'))->assertForbidden();
        $this->actingAs($viewer)->post(route('tagihan.store'), [])->assertForbidden();
    }
}
