<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Data awal aplikasi.
     *
     * Tahap 1 hanya membuat akun pemilik supaya aplikasi bisa dibuka.
     * Pengaturan usaha, kategori biaya, produk, pemasok, dan toko contoh
     * ditambahkan pada Tahap 2.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'pemilik'],
            [
                'name' => 'Pemilik KITAA SEGERIN',
                'email' => null,
                'password' => 'ganti-segera',
            ]
        );
    }
}
