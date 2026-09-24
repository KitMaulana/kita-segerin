@extends('laporan.cetak.layout')

@section('judul', 'LAPORAN PERSEDIAAN')

@section('isi')
    <table style="margin-bottom: 12px;">
        <tr>
            @foreach ([
                ['Stok gudang', angka($persediaan['total_gudang']).' pcs'],
                ['Dititipkan di toko', angka($persediaan['total_di_toko']).' pcs'],
                ['Nilai persediaan', rupiah($persediaan['nilai_total'])],
                ['Produk menipis', angka($persediaan['jumlah_menipis'])],
            ] as [$label, $nilai])
                <td style="width: 25%; padding-right: 5px;">
                    <div class="kartu">
                        <div class="label">{{ $label }}</div>
                        <div class="nilai">{{ $nilai }}</div>
                    </div>
                </td>
            @endforeach
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 60px;">Kode</th>
                <th>Produk</th>
                <th class="kanan">Gudang</th>
                <th class="kanan">Di toko</th>
                <th class="kanan">Total</th>
                <th class="kanan">Harga modal</th>
                <th class="kanan">Nilai gudang</th>
                <th class="kanan">Nilai total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($persediaan['baris'] as $b)
                <tr>
                    <td>{{ $b['produk']->code }}</td>
                    <td>{{ $b['produk']->name }}</td>
                    <td class="kanan {{ $b['menipis'] ? 'mango tebal' : '' }}">{{ angka($b['stok_gudang']) }}</td>
                    <td class="kanan">{{ angka($b['stok_di_toko']) }}</td>
                    <td class="kanan tebal">{{ angka($b['stok_total']) }}</td>
                    <td class="kanan">{{ rupiah($b['produk']->cost_price) }}</td>
                    <td class="kanan">{{ rupiah($b['nilai_gudang']) }}</td>
                    <td class="kanan berry tebal">{{ rupiah($b['nilai_total']) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="tengah" style="padding: 20px;">Belum ada produk.</td></tr>
            @endforelse
        </tbody>
        @if ($persediaan['baris']->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="2">TOTAL</td>
                    <td class="kanan">{{ angka($persediaan['total_gudang']) }}</td>
                    <td class="kanan">{{ angka($persediaan['total_di_toko']) }}</td>
                    <td class="kanan">{{ angka($persediaan['total_gudang'] + $persediaan['total_di_toko']) }}</td>
                    <td></td>
                    <td class="kanan">{{ rupiah($persediaan['nilai_gudang']) }}</td>
                    <td class="kanan">{{ rupiah($persediaan['nilai_total']) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
@endsection
