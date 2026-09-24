<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_profil_bisa_dibuka(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('profile.edit'))->assertOk();
    }

    public function test_data_diri_bisa_diperbarui(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Pemilik Baru',
            'email' => 'pemilik@contoh.test',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('Pemilik Baru', $user->name);
        $this->assertSame('pemilik@contoh.test', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_boleh_dikosongkan(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), ['name' => 'Tanpa Email', 'email' => null])
            ->assertSessionHasNoErrors();

        $this->assertNull($user->refresh()->email);
    }

    public function test_status_verifikasi_tidak_berubah_bila_email_sama(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Nama Sama',
            'email' => $user->email,
        ])->assertSessionHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_pengguna_tidak_bisa_menghapus_akunnya_sendiri(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->delete('/profil')->assertStatus(405);

        $this->assertNotNull($user->fresh());
    }
}
