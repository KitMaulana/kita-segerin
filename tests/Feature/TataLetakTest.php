<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Memastikan rangka aplikasi berjalan: pengalihan halaman depan, beranda,
 * halaman "Lainnya", dan seluruh menu navigasi bisa dibuka.
 */
class TataLetakTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_depan_dialihkan_ke_beranda(): void
    {
        $this->get('/')->assertRedirect('/beranda');
    }

    public function test_beranda_bisa_dibuka_setelah_masuk(): void
    {
        $user = User::factory()->create(['name' => 'Pemilik KITAA SEGERIN']);

        $this->actingAs($user)
            ->get(route('beranda'))
            ->assertOk()
            ->assertSee('KITAA SEGERIN')
            ->assertSee('Ringkasan bulan ini')
            ->assertSee('Setoran bulan ini');
    }

    public function test_halaman_lainnya_menampilkan_seluruh_menu(): void
    {
        $user = User::factory()->owner()->create();

        $this->actingAs($user)
            ->get(route('lainnya'))
            ->assertOk()
            ->assertSee('Produk')
            ->assertSee('Buku Kas')
            ->assertSee('Pengaturan Usaha');
    }

    /**
     * Setiap menu di config/navigation.php harus punya route yang bisa dibuka,
     * supaya tidak ada tautan mati di sidebar maupun navigasi bawah.
     */
    public function test_semua_menu_navigasi_punya_route(): void
    {
        $user = User::factory()->owner()->create();

        $menu = collect(config('navigation.groups'))->flatMap(fn ($grup) => $grup['items']);

        $this->assertNotEmpty($menu);

        foreach ($menu as $item) {
            $this->assertTrue(
                Route::has($item['route']),
                "Route {$item['route']} untuk menu {$item['label']} belum terdaftar."
            );

            $this->actingAs($user)->get(route($item['route']))->assertOk();
        }
    }
}
