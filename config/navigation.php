<?php

/*
|--------------------------------------------------------------------------
| Struktur menu aplikasi
|--------------------------------------------------------------------------
|
| Dipakai oleh sidebar (desktop), navigasi bawah (mobile), dan halaman
| "Lainnya". Kunci "roles" menyiapkan penyaringan hak akses; penegakannya
| dipasang pada Tahap 3 bersama Gate/Policy.
|
*/

return [

    // Lima menu di navigasi bawah (mobile).
    'bottom' => ['beranda', 'pengiriman', 'tagihan', 'laporan', 'lainnya'],

    'groups' => [
        [
            'label' => null,
            'items' => [
                [
                    'key' => 'beranda',
                    'label' => 'Beranda',
                    'route' => 'beranda',
                    'icon' => 'beranda',
                    'roles' => ['owner', 'admin', 'viewer'],
                ],
            ],
        ],
        [
            'label' => 'Titip jual',
            'items' => [
                [
                    'key' => 'pengiriman',
                    'label' => 'Pengiriman',
                    'route' => 'pengiriman.index',
                    'icon' => 'pengiriman',
                    'roles' => ['owner', 'admin', 'viewer'],
                ],
                [
                    'key' => 'tagihan',
                    'label' => 'Tagihan',
                    'route' => 'tagihan.index',
                    'icon' => 'tagihan',
                    'roles' => ['owner', 'admin', 'viewer'],
                ],
                [
                    'key' => 'toko',
                    'label' => 'Toko / Mitra',
                    'route' => 'toko.index',
                    'icon' => 'toko',
                    'roles' => ['owner', 'admin', 'viewer'],
                ],
            ],
        ],
        [
            'label' => 'Barang',
            'items' => [
                [
                    'key' => 'produk',
                    'label' => 'Produk',
                    'route' => 'produk.index',
                    'icon' => 'produk',
                    'roles' => ['owner', 'admin', 'viewer'],
                ],
                [
                    'key' => 'stok',
                    'label' => 'Stok',
                    'route' => 'stok.index',
                    'icon' => 'stok',
                    'roles' => ['owner', 'admin', 'viewer'],
                ],
                [
                    'key' => 'pembelian',
                    'label' => 'Pembelian',
                    'route' => 'pembelian.index',
                    'icon' => 'pembelian',
                    'roles' => ['owner', 'admin', 'viewer'],
                ],
                [
                    'key' => 'pemasok',
                    'label' => 'Pemasok',
                    'route' => 'pemasok.index',
                    'icon' => 'pemasok',
                    'roles' => ['owner', 'admin', 'viewer'],
                ],
            ],
        ],
        [
            'label' => 'Keuangan',
            'items' => [
                [
                    'key' => 'biaya',
                    'label' => 'Biaya Operasional',
                    'route' => 'biaya.index',
                    'icon' => 'biaya',
                    'roles' => ['owner', 'admin', 'viewer'],
                ],
                [
                    'key' => 'kas',
                    'label' => 'Buku Kas',
                    'route' => 'kas.index',
                    'icon' => 'kas',
                    'roles' => ['owner', 'admin', 'viewer'],
                ],
                [
                    'key' => 'laporan',
                    'label' => 'Laporan',
                    'route' => 'laporan.index',
                    'icon' => 'laporan',
                    'roles' => ['owner', 'admin', 'viewer'],
                ],
            ],
        ],
        [
            'label' => 'Pengaturan',
            'items' => [
                [
                    'key' => 'akun',
                    'label' => 'Akun Admin',
                    'route' => 'akun.index',
                    'icon' => 'akun',
                    'roles' => ['owner'],
                ],
                [
                    'key' => 'pengaturan',
                    'label' => 'Pengaturan Usaha',
                    'route' => 'pengaturan.edit',
                    'icon' => 'pengaturan',
                    'roles' => ['owner'],
                ],
                [
                    'key' => 'log-aktivitas',
                    'label' => 'Log Aktivitas',
                    'route' => 'log-aktivitas.index',
                    'icon' => 'log',
                    'roles' => ['owner'],
                ],
            ],
        ],
    ],
];
