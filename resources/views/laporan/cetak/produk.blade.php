@extends('laporan.cetak.layout')

@section('judul', 'REKAP PER PRODUK')

@section('isi')
    <table class="data">
        <thead>
            <tr>
                <th style="width: 60px;">Kode</th>
                <th>Produk</th>
                <th class="kanan">Kirim</th>
                <th class="kanan">Terjual</th>
                <th class="kanan">Retur</th>
                <th class="kanan">Rusak</th>
                <th class="kanan">Laku</th>
                <th class="kanan">Penjualan kotor</th>
                <th class="kanan">Fee toko</th>
                <th class="kanan">Setoran</th>
                <th class="kanan">HPP</th>
                <th class="kanan">Laba kotor</th>
                <th class="kanan">Margin</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($baris as $b)
                <tr>
                    <td>{{ $b['kode'] }}</td>
                    <td>{{ $b['nama'] }}</td>
                    <td class="kanan">{{ angka($b['qty_kirim']) }}</td>
                    <td class="kanan tebal">{{ angka($b['qty_terjual']) }}</td>
                    <td class="kanan">{{ angka($b['qty_retur']) }}</td>
                    <td class="kanan">{{ angka($b['qty_rusak']) }}</td>
                    <td class="kanan">{{ persen($b['tingkat_laku']) }}</td>
                    <td class="kanan">{{ rupiah($b['penjualan_kotor']) }}</td>
                    <td class="kanan mango">{{ rupiah($b['fee_toko']) }}</td>
                    <td class="kanan berry">{{ rupiah($b['setoran']) }}</td>
                    <td class="kanan">{{ rupiah($b['hpp']) }}</td>
                    <td class="kanan mint tebal">{{ rupiah($b['laba_kotor']) }}</td>
                    <td class="kanan">{{ persen($b['margin']) }}</td>
                </tr>
            @empty
                <tr><td colspan="13" class="tengah" style="padding: 20px;">Belum ada penjualan pada periode ini.</td></tr>
            @endforelse
        </tbody>
        @if ($baris->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="2">TOTAL</td>
                    <td class="kanan">{{ angka($baris->sum('qty_kirim')) }}</td>
                    <td class="kanan">{{ angka($baris->sum('qty_terjual')) }}</td>
                    <td class="kanan">{{ angka($baris->sum('qty_retur')) }}</td>
                    <td class="kanan">{{ angka($baris->sum('qty_rusak')) }}</td>
                    <td></td>
                    <td class="kanan">{{ rupiah($baris->sum('penjualan_kotor')) }}</td>
                    <td class="kanan">{{ rupiah($baris->sum('fee_toko')) }}</td>
                    <td class="kanan">{{ rupiah($baris->sum('setoran')) }}</td>
                    <td class="kanan">{{ rupiah($baris->sum('hpp')) }}</td>
                    <td class="kanan">{{ rupiah($baris->sum('laba_kotor')) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>
@endsection
