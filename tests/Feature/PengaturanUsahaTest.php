<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PengaturanUsahaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pemilik_bisa_menyimpan_pengaturan(): void
    {
        $this->actingAs(User::factory()->owner()->create())
            ->put(route('pengaturan.update'), [
                'nama_usaha' => 'KITAA SEGERIN',
                'alamat' => 'Jl. Melati No. 9',
                'telepon' => '081234567890',
                'bank_nama' => 'BCA',
                'bank_nomor_rekening' => '1234567890',
                'bank_atas_nama' => 'Budi',
                'kota_ttd' => 'Segerin',
                'nama_penandatangan' => 'Budi',
                'jatuh_tempo_hari' => 10,
                'catatan_tagihan' => 'Terima kasih.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('pengaturan.edit'));

        Setting::bersihkanCache();

        $this->assertSame('Jl. Melati No. 9', Setting::ambil('alamat'));
        $this->assertSame('10', Setting::ambil('jatuh_tempo_hari'));
        $this->assertDatabaseHas('activity_logs', ['action' => 'mengubah']);
    }

    public function test_jatuh_tempo_wajib_angka(): void
    {
        $this->actingAs(User::factory()->owner()->create())
            ->put(route('pengaturan.update'), [
                'nama_usaha' => 'KITAA SEGERIN',
                'jatuh_tempo_hari' => 'tujuh',
            ])
            ->assertSessionHasErrors('jatuh_tempo_hari');
    }

    public function test_logo_dibatasi_jenis_dan_ukuran(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->owner()->create())
            ->put(route('pengaturan.update'), [
                'nama_usaha' => 'KITAA SEGERIN',
                'jatuh_tempo_hari' => 7,
                'logo' => UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('logo');
    }

    public function test_logo_bisa_diunggah(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->owner()->create())
            ->put(route('pengaturan.update'), [
                'nama_usaha' => 'KITAA SEGERIN',
                'jatuh_tempo_hari' => 7,
                'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
            ])
            ->assertSessionHasNoErrors();

        Setting::bersihkanCache();

        $path = Setting::ambil('logo_path');

        $this->assertNotEmpty($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_admin_tidak_bisa_menyimpan_pengaturan(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('pengaturan.update'), ['nama_usaha' => 'Diubah', 'jatuh_tempo_hari' => 7])
            ->assertForbidden();
    }

    public function test_nilai_bawaan_dipakai_bila_belum_ada_pengaturan(): void
    {
        Setting::query()->delete();
        Setting::bersihkanCache();

        $this->assertSame('KITAA SEGERIN', Setting::ambil('nama_usaha'));
        $this->assertSame('7', Setting::ambil('jatuh_tempo_hari'));
    }
}
