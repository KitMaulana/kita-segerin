<?php

namespace Tests\Feature;

use App\Models\CashTransaction;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\CashBookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BiayaDanKasTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function kategori(string $nama = 'Transportasi/BBM'): ExpenseCategory
    {
        return ExpenseCategory::firstOrCreate(['name' => $nama], ['is_active' => true]);
    }

    public function test_biaya_otomatis_masuk_buku_kas(): void
    {
        $this->actingAs($this->admin())
            ->post(route('biaya.store'), [
                'date' => now()->toDateString(),
                'expense_category_id' => $this->kategori()->id,
                'amount' => 50_000,
                'description' => 'Bensin antar ke 3 toko',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('expenses', ['amount' => 50_000]);

        $kas = CashTransaction::where('category', 'expense')->firstOrFail();

        $this->assertSame('out', $kas->direction);
        $this->assertSame(50_000, $kas->amount);
        $this->assertStringContainsString('Bensin antar ke 3 toko', $kas->description);
    }

    public function test_mengubah_biaya_memperbarui_buku_kas(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('biaya.store'), [
            'date' => now()->toDateString(),
            'expense_category_id' => $this->kategori()->id,
            'amount' => 50_000,
            'description' => 'Bensin',
        ]);

        $biaya = Expense::firstOrFail();

        $this->actingAs($admin)->put(route('biaya.update', $biaya), [
            'date' => now()->toDateString(),
            'expense_category_id' => $biaya->expense_category_id,
            'amount' => 75_000,
            'description' => 'Bensin dan tol',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, CashTransaction::where('category', 'expense')->count());
        $this->assertSame(75_000, CashTransaction::where('category', 'expense')->first()->amount);
    }

    public function test_menghapus_biaya_menghapus_baris_kas(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('biaya.store'), [
            'date' => now()->toDateString(),
            'expense_category_id' => $this->kategori()->id,
            'amount' => 50_000,
            'description' => 'Bensin',
        ]);

        $this->actingAs(User::factory()->owner()->create())
            ->delete(route('biaya.destroy', Expense::firstOrFail()))
            ->assertSessionHas('sukses');

        $this->assertSame(0, Expense::count());
        $this->assertSame(0, CashTransaction::where('category', 'expense')->count());
    }

    public function test_bukti_biaya_dibatasi_jenis_dan_ukuran(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('biaya.store'), [
                'date' => now()->toDateString(),
                'expense_category_id' => $this->kategori()->id,
                'amount' => 50_000,
                'description' => 'Bensin',
                'proof' => UploadedFile::fake()->create('bukti.exe', 100),
            ])
            ->assertSessionHasErrors('proof');
    }

    public function test_bukti_biaya_bisa_diunggah(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('biaya.store'), [
                'date' => now()->toDateString(),
                'expense_category_id' => $this->kategori()->id,
                'amount' => 50_000,
                'description' => 'Bensin',
                'proof' => UploadedFile::fake()->image('nota.jpg'),
            ])
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertExists(Expense::firstOrFail()->proof_path);
    }

    public function test_buku_kas_menampilkan_saldo_berjalan(): void
    {
        $kas = app(CashBookService::class);

        $kas->masuk('owner_capital', 1_000_000, 'Modal awal', now()->startOfMonth());
        $kas->keluar('purchase', 250_000, 'Kulakan', now()->startOfMonth()->addDay());

        $this->actingAs($this->admin())
            ->get(route('kas.index'))
            ->assertOk()
            ->assertSee('Rp1.000.000')
            ->assertSee('Rp250.000')
            ->assertSee('Rp750.000');   // saldo akhir
    }

    public function test_modal_pemilik_bisa_diinput_manual(): void
    {
        $this->actingAs($this->admin())
            ->post(route('kas.store'), [
                'date' => now()->toDateString(),
                'direction' => 'in',
                'category' => 'owner_capital',
                'amount' => 2_000_000,
                'description' => 'Tambahan modal untuk beli freezer',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('cash_transactions', [
            'category' => 'owner_capital',
            'direction' => 'in',
            'amount' => 2_000_000,
        ]);
    }

    public function test_prive_bisa_diinput_manual(): void
    {
        $this->actingAs($this->admin())
            ->post(route('kas.store'), [
                'date' => now()->toDateString(),
                'direction' => 'out',
                'category' => 'owner_withdrawal',
                'amount' => 500_000,
                'description' => 'Ambil untuk keperluan pribadi',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('cash_transactions', ['category' => 'owner_withdrawal', 'amount' => 500_000]);
    }

    public function test_kategori_otomatis_tidak_bisa_diinput_manual(): void
    {
        foreach (['sales_payment', 'purchase', 'expense'] as $kategori) {
            $this->actingAs($this->admin())
                ->post(route('kas.store'), [
                    'date' => now()->toDateString(),
                    'direction' => 'in',
                    'category' => $kategori,
                    'amount' => 100_000,
                    'description' => 'Coba input manual',
                ])
                ->assertSessionHasErrors('category');
        }
    }

    public function test_baris_kas_otomatis_tidak_bisa_dihapus_dari_buku_kas(): void
    {
        $kas = app(CashBookService::class)->keluar('purchase', 250_000, 'Kulakan', now());

        $this->actingAs(User::factory()->owner()->create())
            ->delete(route('kas.destroy', $kas))
            ->assertSessionHas('gagal');

        $this->assertNotNull($kas->fresh());
    }

    public function test_baris_kas_manual_bisa_dihapus(): void
    {
        $kas = app(CashBookService::class)->masuk('owner_capital', 100_000, 'Modal', now());

        $this->actingAs(User::factory()->owner()->create())
            ->delete(route('kas.destroy', $kas))
            ->assertSessionHas('sukses');

        $this->assertNull($kas->fresh());
    }

    public function test_pemantau_tidak_bisa_mencatat_biaya(): void
    {
        $viewer = User::factory()->viewer()->create();

        $this->actingAs($viewer)->get(route('biaya.index'))->assertOk();
        $this->actingAs($viewer)->get(route('biaya.create'))->assertForbidden();
        $this->actingAs($viewer)->post(route('biaya.store'), [])->assertForbidden();
        $this->actingAs($viewer)->post(route('kas.store'), [])->assertForbidden();
    }
}
