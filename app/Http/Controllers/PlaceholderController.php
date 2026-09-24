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
