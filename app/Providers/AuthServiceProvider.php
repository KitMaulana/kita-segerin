<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Hak akses per peran, sesuai tabel di bagian E CLAUDE.md.
 *
 * | Fitur                                    | owner | admin | viewer |
 * |------------------------------------------|-------|-------|--------|
 * | Beranda & laporan                        |   ✓   |   ✓   |   ✓    |
 * | Input transaksi & data master            |   ✓   |   ✓   |   –    |
 * | Buat tagihan & catat pembayaran          |   ✓   |   ✓   |   –    |
 * | Ubah harga modal / harga jual / fee      |   ✓   |   ✓   |   –    |
 * | Batalkan tagihan, hapus transaksi        |   ✓   |   –   |   –    |
 * | Kelola akun admin, pengaturan usaha      |   ✓   |   –   |   –    |
 */
class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Semua peran boleh melihat beranda dan laporan.
        Gate::define('lihat-laporan', fn (User $u) => true);

        // Pemilik dan admin boleh menginput transaksi dan data master.
        Gate::define('input-transaksi', fn (User $u) => $u->bisaInput());

        // Membuat tagihan dan mencatat pembayaran.
        Gate::define('kelola-tagihan', fn (User $u) => $u->bisaInput());

        // Mengubah harga modal, harga jual, dan fee (perubahan admin tercatat di log).
        Gate::define('ubah-harga', fn (User $u) => $u->bisaInput());

        // Hanya pemilik: pembatalan tagihan dan penghapusan transaksi.
        Gate::define('batalkan-tagihan', fn (User $u) => $u->isOwner());
        Gate::define('hapus-transaksi', fn (User $u) => $u->isOwner());

        // Hanya pemilik: kelola akun, pengaturan usaha, log aktivitas, dan backup.
        Gate::define('kelola-akun', fn (User $u) => $u->isOwner());
        Gate::define('kelola-pengaturan', fn (User $u) => $u->isOwner());
        Gate::define('lihat-log', fn (User $u) => $u->isOwner());
    }
}
