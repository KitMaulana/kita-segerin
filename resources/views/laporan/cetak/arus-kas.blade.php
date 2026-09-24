@extends('laporan.cetak.layout')

@section('judul', 'LAPORAN ARUS KAS')

@section('isi')
    <table style="margin-bottom: 12px;">
        <tr>
            @foreach ([
                ['Saldo awal', rupiah($kas['saldo_awal'])],
                ['Total masuk', rupiah($kas['total_masuk'])],
                ['Total keluar', rupiah($kas['total_keluar'])],
                ['Saldo akhir', rupiah($kas['saldo_akhir'])],
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

    <table class="data" style="margin-bottom: 12px;">
        <thead>
            <tr><th colspan="2">UANG MASUK</th></tr>
        </thead>
        <tbody>
            @forelse ($kas['masuk_per_kategori'] as $b)
                <tr>
                    <td>{{ $b['nama'] }}</td>
                    <td class="kanan mint" style="width: 140px;">{{ rupiah($b['jumlah']) }}</td>
                </tr>
            @empty
                <tr><td colspan="2" class="tengah">Tidak ada uang masuk pada periode ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr><td>Total masuk</td><td class="kanan">{{ rupiah($kas['total_masuk']) }}</td></tr>
        </tfoot>
    </table>

    <table class="data">
        <thead>
            <tr><th colspan="2">UANG KELUAR</th></tr>
        </thead>
        <tbody>
            @forelse ($kas['keluar_per_kategori'] as $b)
                <tr>
                    <td>{{ $b['nama'] }}</td>
                    <td class="kanan strawberry" style="width: 140px;">{{ rupiah($b['jumlah']) }}</td>
                </tr>
            @empty
                <tr><td colspan="2" class="tengah">Tidak ada uang keluar pada periode ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr><td>Total keluar</td><td class="kanan">{{ rupiah($kas['total_keluar']) }}</td></tr>
            <tr><td style="font-size: 10pt;">SALDO AKHIR</td><td class="kanan" style="font-size: 10pt;">{{ rupiah($kas['saldo_akhir']) }}</td></tr>
        </tfoot>
    </table>
@endsection
