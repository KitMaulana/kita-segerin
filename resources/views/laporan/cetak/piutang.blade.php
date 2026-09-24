@extends('laporan.cetak.layout')

@section('judul', 'PIUTANG & UMUR PIUTANG')

@section('isi')
    <table style="margin-bottom: 12px;">
        <tr>
            @foreach ($piutang['umur'] as $rentang => $data)
                <td style="width: 25%; padding-right: 5px;">
                    <div class="kartu">
                        <div class="label">{{ $rentang }} hari &middot; {{ $data['jumlah'] }} tagihan</div>
                        <div class="nilai">{{ rupiah($data['nilai']) }}</div>
                    </div>
                </td>
            @endforeach
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Nomor</th>
                <th>Toko</th>
                <th>Tanggal</th>
                <th>Jatuh tempo</th>
                <th class="kanan">Tagihan</th>
                <th class="kanan">Dibayar</th>
                <th class="kanan">Sisa</th>
                <th class="kanan">Umur</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($piutang['tagihan'] as $inv)
                <tr>
                    <td>{{ $inv->number }}</td>
                    <td>{{ $inv->store->name }}</td>
                    <td>{{ tanggal_singkat($inv->invoice_date) }}</td>
                    <td class="{{ $inv->lewatJatuhTempo() ? 'strawberry tebal' : '' }}">{{ tanggal_singkat($inv->due_date) }}</td>
                    <td class="kanan">{{ rupiah($inv->amount_due) }}</td>
                    <td class="kanan mint">{{ rupiah($inv->amount_paid) }}</td>
                    <td class="kanan mango tebal">{{ rupiah($inv->sisaTagihan()) }}</td>
                    <td class="kanan">{{ max(0, $inv->umurPiutangHari()) }} hari</td>
                </tr>
            @empty
                <tr><td colspan="8" class="tengah" style="padding: 20px;">Tidak ada piutang. Semua tagihan sudah lunas.</td></tr>
            @endforelse
        </tbody>
        @if (count($piutang['tagihan']) > 0)
            <tfoot>
                <tr>
                    <td colspan="6" class="kanan">TOTAL PIUTANG</td>
                    <td class="kanan">{{ rupiah($piutang['total']) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>
@endsection
