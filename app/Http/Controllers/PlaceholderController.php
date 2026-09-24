<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Menampilkan halaman sementara untuk menu yang belum dikerjakan.
 * Setiap entri dihapus begitu tahap yang bersangkutan selesai.
 */
class PlaceholderController extends Controller
{
    /**
     * @var array<string, array{judul: string, ikon: string, tahap: string, keterangan: string}>
     */
    private const HALAMAN = [
        'pengiriman.index' => [
            'judul' => 'Pengiriman Titip Jual',
            'ikon' => 'pengiriman',
            'tahap' => 'Tahap 6',
            'keterangan' => 'Pencatatan pengiriman es krim ke toko, surat jalan, dan rekonsiliasi terjual/retur/rusak.',
        ],
        'tagihan.index' => [
            'judul' => 'Tagihan Setoran',
            'ikon' => 'tagihan',
            'tahap' => 'Tahap 7',
            'keterangan' => 'Pembuatan tagihan dari pengiriman yang sudah direkonsiliasi, cetak PDF, dan catat pembayaran.',
        ],
        'toko.index' => [
            'judul' => 'Toko / Mitra',
            'ikon' => 'toko',
            'tahap' => 'Tahap 5',
            'keterangan' => 'Daftar koperasi sekolah, kantin, dan toko beserta harga jual dan fee khusus tiap mitra.',
        ],
        'produk.index' => [
            'judul' => 'Produk',
            'ikon' => 'produk',
            'tahap' => 'Tahap 4',
            'keterangan' => 'Daftar es krim beserta harga modal, harga jual default, dan fee toko default.',
        ],
        'stok.index' => [
            'judul' => 'Stok',
            'ikon' => 'stok',
            'tahap' => 'Tahap 4',
            'keterangan' => 'Stok gudang, stok yang sedang dititipkan di toko, dan nilai persediaan.',
        ],
        'pembelian.index' => [
            'judul' => 'Pembelian (Kulakan)',
            'ikon' => 'pembelian',
            'tahap' => 'Tahap 4',
            'keterangan' => 'Pencatatan pembelian es krim dari pemasok yang otomatis menambah stok dan buku kas.',
        ],
        'pemasok.index' => [
            'judul' => 'Pemasok',
            'ikon' => 'pemasok',
            'tahap' => 'Tahap 4',
            'keterangan' => 'Daftar perusahaan atau distributor tempat es krim diambil.',
        ],
        'biaya.index' => [
            'judul' => 'Biaya Operasional',
            'ikon' => 'biaya',
            'tahap' => 'Tahap 8',
            'keterangan' => 'Pencatatan bensin, dry ice, listrik freezer, kemasan, dan biaya lain beserta buktinya.',
        ],
        'kas.index' => [
            'judul' => 'Buku Kas',
            'ikon' => 'kas',
            'tahap' => 'Tahap 8',
            'keterangan' => 'Seluruh uang masuk dan keluar dengan saldo berjalan.',
        ],
        'laporan.index' => [
            'judul' => 'Laporan',
            'ikon' => 'laporan',
            'tahap' => 'Tahap 8',
            'keterangan' => 'Laba rugi, rekap per produk dan per toko, piutang, arus kas, dan persediaan.',
        ],
        'akun.index' => [
            'judul' => 'Akun Admin',
            'ikon' => 'akun',
            'tahap' => 'Tahap 3',
            'keterangan' => 'Pengelolaan pengguna aplikasi beserta perannya: pemilik, admin, dan pemantau.',
        ],
        'pengaturan.edit' => [
            'judul' => 'Pengaturan Usaha',
            'ikon' => 'pengaturan',
            'tahap' => 'Tahap 3',
            'keterangan' => 'Nama usaha, alamat, logo, rekening bank, jatuh tempo default, dan catatan kaki tagihan.',
        ],
        'log-aktivitas.index' => [
            'judul' => 'Log Aktivitas',
            'ikon' => 'log',
            'tahap' => 'Tahap 3',
            'keterangan' => 'Riwayat siapa mengubah apa dan kapan, untuk penelusuran bila ada selisih.',
        ],
    ];

    public function __invoke(Request $request): View
    {
        $nama = $request->route()->getName();

        $data = self::HALAMAN[$nama] ?? [
            'judul' => 'Halaman belum tersedia',
            'ikon' => 'lainnya',
            'tahap' => 'tahap berikutnya',
            'keterangan' => 'Menu ini sedang disiapkan.',
        ];

        return view('placeholder', [
            'judul' => $data['judul'],
            'ikon' => $data['ikon'],
            'tahap' => $data['tahap'],
            'keterangan' => $data['keterangan'],
            'subjudul' => 'Belum tersedia',
        ]);
    }
}
