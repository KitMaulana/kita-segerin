@extends('laporan.cetak.layout')

@section('judul', 'REKAP PERIODE PENGIRIMAN')

@section('isi')
    <table class="data">
        <thead>
            <tr>
                <th style="width: 60px;">Kode</th>
                <th>Produk</th>
                <th class="kanan">Kirim</th>
                <th class="kanan">Terjual</th>
                <th class="kanan">Sisa</th>
                <th class="kanan">Retur</th>
                <th class="kanan">Rusak</th>
                <th class="kanan">Laku</th>
                <th class="kanan">Penjualan kotor</th>
                <th class="kanan">Fee toko</th>
                <th class="kanan">Setoran</th>
                <th class="kanan">Keuntungan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rekap['baris'] as $b)
                <tr>
                    <td>{{ $b['kode'] }}</td>
                    <td>{{ $b['nama'] }}</td>
                    <td class="kanan">{{ angka($b['kirim']) }}</td>
                    <td class="kanan tebal">{{ angka($b['terjual']) }}</td>
                    <td class="kanan">{{ angka($b['sisa']) }}</td>
                    <td class="kanan">{{ angka($b['retur']) }}</td>
                    <td class="kanan">{{ angka($b['rusak']) }}</td>
                    <td class="kanan">{{ persen($b['tingkat_laku']) }}</td>
                    <td class="kanan">{{ rupiah($b['penjualan_kotor']) }}</td>
                    <td class="kanan mango">{{ rupiah($b['fee_toko']) }}</td>
                    <td class="kanan berry">{{ rupiah($b['setoran']) }}</td>
                    <td class="kanan mint tebal">{{ rupiah($b['keuntungan']) }}</td>
                </tr>
            @empty
                <tr><td colspan="12" class="tengah" style="padding: 20px;">Belum ada pengiriman direkonsiliasi pada periode ini.</td></tr>
            @endforelse
        </tbody>
        @if ($rekap['baris']->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="2">TOTAL</td>
                    <td class="kanan">{{ angka($rekap['total']['kirim']) }}</td>
                    <td class="kanan">{{ angka($rekap['total']['terjual']) }}</td>
                    <td class="kanan">{{ angka($rekap['total']['sisa']) }}</td>
                    <td class="kanan">{{ angka($rekap['total']['retur']) }}</td>
                    <td class="kanan">{{ angka($rekap['total']['rusak']) }}</td>
                    <td></td>
                    <td class="kanan">{{ rupiah($rekap['total']['penjualan_kotor']) }}</td>
                    <td class="kanan">{{ rupiah($rekap['total']['fee_toko']) }}</td>
                    <td class="kanan">{{ rupiah($rekap['total']['setoran']) }}</td>
                    <td class="kanan">{{ rupiah($rekap['total']['keuntungan']) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <p style="margin-top: 10px; font-size: 8pt; color: #5b6b76;">
        Sisa = jumlah kirim dikurangi jumlah terjual (termasuk yang diretur dan rusak).
    </p>
@endsection
