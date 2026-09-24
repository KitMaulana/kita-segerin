<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tahap 10: manifest, service worker, ikon, halaman offline, dan tombol pasang.
 */
class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_berisi_keterangan_yang_dibutuhkan(): void
    {
        $berkas = public_path('manifest.webmanifest');

        $this->assertFileExists($berkas);

        $manifest = json_decode(file_get_contents($berkas), true);

        $this->assertIsArray($manifest, 'manifest.webmanifest harus JSON yang sah.');
        $this->assertSame('KITAA SEGERIN', $manifest['name']);
        $this->assertSame('Segerin', $manifest['short_name']);
        $this->assertSame('/beranda', $manifest['start_url']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('#5B2A6E', $manifest['theme_color']);   // berry
        $this->assertSame('#EEF6FA', $manifest['background_color']); // frost
    }

    public function test_manifest_menunjuk_ikon_192_512_dan_maskable(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $ukuran = array_column($manifest['icons'], 'sizes');
        $this->assertContains('192x192', $ukuran);
        $this->assertContains('512x512', $ukuran);

        $maskable = array_filter($manifest['icons'], fn ($i) => ($i['purpose'] ?? '') === 'maskable');
        $this->assertNotEmpty($maskable, 'Harus ada ikon maskable.');

        foreach ($manifest['icons'] as $ikon) {
            $berkas = public_path(ltrim($ikon['src'], '/'));

            $this->assertFileExists($berkas);

            [$lebar, $tinggi] = getimagesize($berkas);
            [$lebarDiminta, $tinggiDiminta] = array_map('intval', explode('x', $ikon['sizes']));

            $this->assertSame([$lebarDiminta, $tinggiDiminta], [$lebar, $tinggi],
                "Ukuran {$ikon['src']} tidak sesuai keterangan di manifest.");
        }
    }

    public function test_ikon_apple_touch_tersedia(): void
    {
        $berkas = public_path('icons/apple-touch-icon.png');

        $this->assertFileExists($berkas);
        $this->assertSame([180, 180], array_slice(getimagesize($berkas), 0, 2));
    }

    public function test_halaman_offline_bisa_dibuka_tanpa_masuk(): void
    {
        $this->get('/offline')
            ->assertOk()
            ->assertSee('Tidak ada koneksi')
            ->assertSee('Coba lagi');
    }

    /**
     * Halaman offline harus berdiri sendiri: kalau ia memuat berkas CSS hasil
     * build, tampilannya bisa rusak justru saat sedang tidak ada koneksi.
     */
    public function test_halaman_offline_tidak_memuat_aset_build(): void
    {
        $isi = $this->get('/offline')->getContent();

        $this->assertStringNotContainsString('/build/', $isi);
    }

    public function test_halaman_offline_tidak_membocorkan_data_usaha(): void
    {
        $this->get('/offline')
            ->assertDontSee('Setoran')
            ->assertDontSee('Tagihan');
    }

    public function test_layout_memuat_manifest_dan_ikon(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('beranda'))
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('/manifest.webmanifest', false)
            ->assertSee('rel="apple-touch-icon"', false)
            ->assertSee('name="theme-color"', false)
            ->assertSee('name="versi-aset"', false);
    }

    public function test_halaman_masuk_juga_memuat_manifest(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('/manifest.webmanifest', false);
    }

    /**
     * Versi cache harus ikut berubah setiap Vite membangun ulang, supaya
     * pengguna tidak terjebak memakai aset lama.
     */
    public function test_versi_aset_mengikuti_hasil_build(): void
    {
        $manifest = public_path('build/manifest.json');

        if (! is_file($manifest)) {
            $this->assertSame('dev', versi_aset());

            return;
        }

        $this->assertSame(substr(md5_file($manifest), 0, 10), versi_aset());
        $this->assertMatchesRegularExpression('/^[a-f0-9]{10}$/', versi_aset());
    }

    public function test_service_worker_tersedia_dan_menolak_menyimpan_laporan(): void
    {
        $berkas = public_path('sw.js');

        $this->assertFileExists($berkas);

        $isi = file_get_contents($berkas);

        // Laporan keuangan, dokumen cetak, dan POST tidak boleh disimpan.
        $this->assertStringContainsString("'/laporan'", $isi);
        $this->assertStringContainsString('.pdf', $isi);
        $this->assertStringContainsString("permintaan.method !== 'GET'", $isi);

        // Halaman offline dan versi cache dari URL pendaftaran.
        $this->assertStringContainsString("HALAMAN_OFFLINE = '/offline'", $isi);
        $this->assertStringContainsString("searchParams.get('v')", $isi);
    }

    public function test_tombol_pasang_aplikasi_ada_di_halaman_lainnya(): void
    {
        $user = User::factory()->owner()->create();

        $this->actingAs($user)
            ->get(route('lainnya'))
            ->assertOk()
            ->assertSee('data-pasang-aplikasi', false)
            ->assertSee('Pasang aplikasi')
            ->assertSee('Tambah ke Layar Utama');
    }
}
