<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AkunAdminTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->owner()->create();
    }

    public function test_pemilik_bisa_membuka_menu_akun(): void
    {
        $this->actingAs($this->owner())->get(route('akun.index'))->assertOk();
    }

    public function test_admin_tidak_bisa_membuka_menu_akun(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('akun.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('akun.create'))->assertForbidden();
        $this->actingAs($admin)->get(route('pengaturan.edit'))->assertForbidden();
        $this->actingAs($admin)->get(route('log-aktivitas.index'))->assertForbidden();
    }

    public function test_pemantau_tidak_bisa_membuka_menu_akun(): void
    {
        $this->actingAs(User::factory()->viewer()->create())
            ->get(route('akun.index'))
            ->assertForbidden();
    }

    public function test_pemilik_bisa_menambah_akun(): void
    {
        $this->actingAs($this->owner())
            ->post(route('akun.store'), [
                'name' => 'Siti Admin',
                'username' => 'siti',
                'email' => 'siti@contoh.test',
                'role' => 'admin',
                'is_active' => '1',
                'password' => 'rahasia12345',
                'password_confirmation' => 'rahasia12345',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('akun.index'));

        $akun = User::where('username', 'siti')->firstOrFail();

        $this->assertSame('admin', $akun->role);
        $this->assertTrue($akun->is_active);
        $this->assertDatabaseHas('activity_logs', ['action' => 'membuat', 'subject_id' => $akun->id]);
    }

    public function test_username_harus_unik(): void
    {
        User::factory()->create(['username' => 'siti']);

        $this->actingAs($this->owner())
            ->post(route('akun.store'), [
                'name' => 'Siti Lain',
                'username' => 'siti',
                'role' => 'admin',
                'password' => 'rahasia12345',
                'password_confirmation' => 'rahasia12345',
            ])
            ->assertSessionHasErrors('username');
    }

    public function test_pemilik_bisa_mengubah_peran_akun(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($this->owner())
            ->put(route('akun.update', $admin), [
                'name' => $admin->name,
                'username' => $admin->username,
                'email' => $admin->email,
                'role' => 'viewer',
                'is_active' => '1',
                'password' => '',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('viewer', $admin->refresh()->role);
    }

    public function test_pemilik_tidak_bisa_menonaktifkan_diri_sendiri(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->patch(route('akun.toggle', $owner))
            ->assertSessionHasErrors('role');

        $this->assertTrue($owner->refresh()->is_active);
    }

    public function test_pemilik_terakhir_tidak_bisa_diturunkan_perannya(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->put(route('akun.update', $owner), [
                'name' => $owner->name,
                'username' => $owner->username,
                'role' => 'admin',
                'is_active' => '1',
                'password' => '',
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame('owner', $owner->refresh()->role);
    }

    public function test_pemilik_bisa_dinonaktifkan_bila_masih_ada_pemilik_aktif_lain(): void
    {
        $owner = $this->owner();
        $ownerLain = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->patch(route('akun.toggle', $ownerLain))
            ->assertSessionHasNoErrors();

        $this->assertFalse($ownerLain->refresh()->is_active);
    }

    public function test_pemilik_bisa_menonaktifkan_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($this->owner())
            ->patch(route('akun.toggle', $admin))
            ->assertSessionHasNoErrors();

        $this->assertFalse($admin->refresh()->is_active);
    }

    public function test_reset_kata_sandi_membuat_sandi_baru(): void
    {
        $admin = User::factory()->admin()->create();
        $sandiLama = $admin->password;

        $this->actingAs($this->owner())
            ->patch(route('akun.reset-password', $admin))
            ->assertSessionHas('sukses');

        $this->assertNotSame($sandiLama, $admin->refresh()->password);
        $this->assertDatabaseHas('activity_logs', ['action' => 'mengubah', 'subject_id' => $admin->id]);
    }

    public function test_akun_nonaktif_tidak_bisa_masuk(): void
    {
        $akun = User::factory()->nonaktif()->create(['username' => 'nonaktif']);

        $this->post(route('login'), ['login' => 'nonaktif', 'password' => 'password'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_akun_yang_dinonaktifkan_saat_sesi_berjalan_langsung_dikeluarkan(): void
    {
        $akun = User::factory()->admin()->create();

        $this->actingAs($akun)->get(route('beranda'))->assertOk();

        $akun->update(['is_active' => false]);

        $this->actingAs($akun)->get(route('beranda'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_waktu_masuk_terakhir_dicatat(): void
    {
        $akun = User::factory()->create(['username' => 'pemilik', 'last_login_at' => null]);

        $this->post(route('login'), ['login' => 'pemilik', 'password' => 'password']);

        $this->assertNotNull($akun->refresh()->last_login_at);
    }

    public function test_log_aktivitas_bisa_dibuka_pemilik(): void
    {
        ActivityLog::catat('membuat', 'Contoh aktivitas untuk pengujian.');

        $this->actingAs($this->owner())
            ->get(route('log-aktivitas.index'))
            ->assertOk()
            ->assertSee('Contoh aktivitas untuk pengujian.');
    }
}
