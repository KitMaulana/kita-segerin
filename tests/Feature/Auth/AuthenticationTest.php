<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_masuk_bisa_dibuka(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_pengguna_bisa_masuk_memakai_username(): void
    {
        $user = User::factory()->create(['username' => 'pemilik']);

        $response = $this->post(route('login'), [
            'login' => 'pemilik',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('beranda', absolute: false));
    }

    public function test_pengguna_bisa_masuk_memakai_email(): void
    {
        $user = User::factory()->create(['email' => 'pemilik@contoh.test']);

        $response = $this->post(route('login'), [
            'login' => 'pemilik@contoh.test',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('beranda', absolute: false));
    }

    public function test_pengguna_tidak_bisa_masuk_dengan_kata_sandi_salah(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), [
            'login' => $user->username,
            'password' => 'kata-sandi-salah',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_pengguna_bisa_keluar(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_pendaftaran_mandiri_tidak_tersedia(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }

    public function test_halaman_wajib_login(): void
    {
        $this->get(route('beranda'))->assertRedirect(route('login'));
        $this->get(route('produk.index'))->assertRedirect(route('login'));
    }
}
