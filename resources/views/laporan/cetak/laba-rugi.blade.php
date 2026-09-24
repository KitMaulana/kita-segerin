@extends('laporan.cetak.layout')

@section('judul', 'LAPORAN LABA RUGI')

@section('isi')
    <table class="data">
        <tbody>
            @foreach ([
                ['Penjualan kotor', $lr['penjualan_kotor'], '', false],
                ['Fee toko', -$lr['fee_toko'], 'mango', false],
                ['Setoran (ditagih ke toko)', $lr['setoran'], 'berry', true],
                ['HPP — harga modal barang terjual', -$lr['hpp'], '', false],
                ['Laba kotor', $lr['laba_kotor'], 'mint', true],
                ['Kerugian barang rusak', -$lr['kerugian_rusak'], 'strawberry', false],
            ] as [$label, $nilai, $warna, $tebal])
                <tr>
                    <td class="{{ $tebal ? 'tebal' : '' }}">{{ $label }}</td>
                    <td class="kanan {{ $warna }} {{ $tebal ? 'tebal' : '' }}" style="width: 130px;">
                        {{ $nilai < 0 ? '- '.rupiah(abs($nilai)) : rupiah($nilai) }}
                    </td>
                </tr>
            @endforeach

            @foreach ($lr['biaya_per_kategori'] as $nama => $jumlah)
                <tr>
                    <td style="padding-left: 18px;">Biaya &mdash; {{ $nama }}</td>
                    <td class="kanan strawberry">- {{ rupiah($jumlah) }}</td>
                </tr>
            @endforeach

            <tr>
                <td>Total biaya operasional</td>
                <td class="kanan strawberry">- {{ rupiah($lr['total_biaya']) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td style="font-size: 10pt;">LABA BERSIH</td>
                <td class="kanan" style="font-size: 11pt;">{{ rupiah($lr['laba_bersih']) }}</td>
            </tr>
        </tfoot>
    </table>

    <p style="margin-top: 8px; font-style: italic; font-size: 8.5pt;">
        Terbilang: {{ terbilang($lr['laba_bersih'], kapitalAwal: true) }}
    </p>

    <table style="margin-top: 14px;">
        <tr>
            @foreach ([
                ['Dikirim', angka($lr['qty_kirim']).' pcs'],
                ['Terjual', angka($lr['qty_terjual']).' pcs'],
                ['Retur', angka($lr['qty_retur']).' pcs'],
                ['Rusak', angka($lr['qty_rusak']).' pcs'],
                ['Tingkat laku', persen($lr['tingkat_laku'])],
            ] as [$label, $nilai])
                <td style="width: 20%; padding-right: 5px;">
                    <div class="kartu">
                        <div class="label">{{ $label }}</div>
                        <div class="nilai">{{ $nilai }}</div>
                    </div>
                </td>
            @endforeach
        </tr>
    </table>
@endsection
