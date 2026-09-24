<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->akunPemilik();
        $this->pengaturanUsaha();
        $this->kategoriBiaya();
        $pemasok = $this->pemasok();
        $this->produk($pemasok);
        $this->toko();
    }

    private function akunPemilik(): void
    {
        User::updateOrCreate(
            ['username' => 'pemilik'],
            [
                'name' => 'Pemilik KITAA SEGERIN',
                'email' => null,
                'password' => 'ganti-segera',
                'role' => 'owner',
                'is_active' => true,
            ]
        );
    }

    private function pengaturanUsaha(): void
    {
        Setting::simpan([
            'nama_usaha' => 'KITAA SEGERIN',
            'alamat' => 'Jl. Contoh No. 1, Segerin',
            'telepon' => '081234567890',
            'email' => '',
            'logo_path' => '',
            'bank_nama' => 'BRI',
            'bank_nomor_rekening' => '0000-01-000000-00-0',
            'bank_atas_nama' => 'KITAA SEGERIN',
            'kota_ttd' => 'Segerin',
            'nama_penandatangan' => 'Pemilik KITAA SEGERIN',
            'jatuh_tempo_hari' => '7',
            'catatan_tagihan' => 'Terima kasih atas kerja samanya. Setoran dapat ditransfer ke rekening di atas atau dibayar tunai kepada petugas pengantar.',
        ]);
    }

    private function kategoriBiaya(): void
    {
        $kategori = [
            'Transportasi/BBM',
            'Dry ice & es batu',
            'Listrik freezer',
            'Kemasan & plastik',
            'Upah/gaji',
            'Perawatan freezer',
            'Lain-lain',
        ];

        foreach ($kategori as $nama) {
            ExpenseCategory::updateOrCreate(['name' => $nama], ['is_active' => true]);
        }
    }

    private function pemasok(): Supplier
    {
        return Supplier::updateOrCreate(
            ['name' => 'PT Es Krim Nusantara'],
            [
                'contact_person' => 'Bapak Andi',
                'phone' => '0811000111',
                'address' => 'Kawasan Industri Segerin Blok C',
                'notes' => 'Kulakan minimal 10 dus. Pengambilan hari Senin dan Kamis.',
                'is_active' => true,
            ]
        );
    }

    private function produk(Supplier $pemasok): void
    {
        // Harga di bawah hanya contoh; pengguna mengubahnya lewat aplikasi.
        $daftar = [
            ['ES-001', 'Sutaco Taro', 'cup', 2500, 5000, 'nominal', 500, 50],
            ['ES-002', 'Sumico Millo', 'cup', 2500, 5000, 'nominal', 500, 50],
            ['ES-003', '143 Cup Strawberry', 'cup', 2000, 4000, 'percent', 10, 40],
            ['ES-004', 'Semangka Nanas', 'stik', 1500, 3000, 'nominal', 300, 60],
            ['ES-005', 'Piscok Krispi', 'lainnya', 3000, 6000, 'percent', 10, 30],
        ];

        foreach ($daftar as [$kode, $nama, $varian, $modal, $jual, $jenisFee, $nilaiFee, $stokMin]) {
            Product::updateOrCreate(
                ['code' => $kode],
                [
                    'name' => $nama,
                    'supplier_id' => $pemasok->id,
                    'variant' => $varian,
                    'unit' => 'pcs',
                    'cost_price' => $modal,
                    'default_selling_price' => $jual,
                    'default_fee_type' => $jenisFee,
                    'default_fee_value' => $nilaiFee,
                    'min_stock' => $stokMin,
                    'is_active' => true,
                ]
            );
        }
    }

    private function toko(): void
    {
        Store::updateOrCreate(
            ['code' => 'TK-001'],
            [
                'name' => 'Koperasi Budi Utama',
                'type' => 'koperasi',
                'contact_person' => 'Ibu Sri',
                'phone' => '081298765432',
                'address' => 'SMP Budi Utama, Jl. Pendidikan No. 7',
                'payment_term_days' => 7,
                'is_active' => true,
                'notes' => 'Pengiriman setiap Senin, rekonsiliasi setiap Jumat.',
            ]
        );
    }
}
