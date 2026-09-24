<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Tagihan {{ $tagihan->number }}</title>
    <style>
        @page { margin: 16mm 15mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1D2B36; margin: 0; }
        table { width: 100%; border-collapse: collapse; }

        .kop td { vertical-align: top; padding: 0 0 10px; }
        .nama-usaha { font-size: 16pt; font-weight: bold; color: #5B2A6E; margin: 0 0 3px; }
        .kecil { font-size: 8.5pt; color: #5b6b76; }
        .judul { font-size: 15pt; font-weight: bold; letter-spacing: 1px; text-align: right; color: #5B2A6E; margin: 0; }
        .garis { border-bottom: 2px solid #5B2A6E; margin-bottom: 12px; }

        .blok-info td { padding: 2px 0; vertical-align: top; }
        .blok-info .label { color: #5b6b76; width: 95px; }

        .rincian th { background: #EEF6FA; border-bottom: 1px solid #c7dbe6; padding: 7px 6px; font-size: 9pt; text-align: left; }
        .rincian td { border-bottom: 1px solid #e6eff4; padding: 6px; }
        .kanan { text-align: right; }
        .tengah { text-align: center; }

        .ringkas td { padding: 4px 6px; }
        .ringkas .label { text-align: right; color: #5b6b76; }
        .total-akhir td { background: #5B2A6E; color: #fff; font-weight: bold; font-size: 11.5pt; padding: 8px 6px; }

        .terbilang { margin-top: 8px; font-style: italic; font-size: 9pt; }
        .kotak { border: 1px solid #c7dbe6; background: #EEF6FA; padding: 8px 10px; margin-top: 12px; font-size: 9pt; }

        .ttd { margin-top: 26px; }
        .ttd td { width: 50%; text-align: center; font-size: 9pt; }
        .kotak-ttd { height: 55px; }
        .garis-ttd { border-top: 1px solid #1D2B36; width: 65%; margin: 0 auto; padding-top: 4px; }

        .lunas { color: #12A383; font-weight: bold; }
        .belum { color: #D9435A; font-weight: bold; }
        .batal { color: #D9435A; font-weight: bold; font-size: 22pt; letter-spacing: 3px; }
    </style>
</head>
<body>

    <table class="kop">
        <tr>
            <td style="width: 62%">
                @if ($pengaturan['logo_path'] && file_exists(public_path('storage/'.$pengaturan['logo_path'])))
                    <img src="{{ public_path('storage/'.$pengaturan['logo_path']) }}" alt="" style="height: 44px; margin-bottom: 6px;">
                @endif
                <p class="nama-usaha">{{ $pengaturan['nama_usaha'] }}</p>
                @if ($pengaturan['alamat'])
                    <p class="kecil" style="margin: 0;">{{ $pengaturan['alamat'] }}</p>
                @endif
                @if ($pengaturan['telepon'])
                    <p class="kecil" style="margin: 2px 0 0;">Telp/WA: {{ $pengaturan['telepon'] }}</p>
                @endif
                @if ($pengaturan['email'])
                    <p class="kecil" style="margin: 2px 0 0;">{{ $pengaturan['email'] }}</p>
                @endif
            </td>
            <td>
                <p class="judul">TAGIHAN SETORAN</p>
                @if ($tagihan->status === 'cancelled')
                    <p class="kanan batal" style="margin: 6px 0 0;">DIBATALKAN</p>
                @elseif ($tagihan->status === 'paid')
                    <p class="kanan lunas" style="margin: 6px 0 0; font-size: 13pt;">LUNAS</p>
                @endif
            </td>
        </tr>
    </table>

    <div class="garis"></div>

    <table>
        <tr>
            <td style="width: 50%">
                <table class="blok-info">
                    <tr><td class="label">Nomor</td><td><strong>{{ $tagihan->number }}</strong></td></tr>
                    <tr><td class="label">Tanggal</td><td>{{ tanggal_indo($tagihan->invoice_date) }}</td></tr>
                    <tr><td class="label">Jatuh tempo</td><td><strong>{{ tanggal_indo($tagihan->due_date) }}</strong></td></tr>
                    <tr><td class="label">Periode</td>
                        <td>{{ tanggal_singkat($tagihan->period_start) }} &ndash; {{ tanggal_singkat($tagihan->period_end) }}</td></tr>
                </table>
            </td>
            <td style="width: 50%">
                <table class="blok-info">
                    <tr><td class="label">Kepada</td><td><strong>{{ $tagihan->store->name }}</strong></td></tr>
                    @if ($tagihan->store->address)
                        <tr><td class="label">Alamat</td><td>{{ $tagihan->store->address }}</td></tr>
                    @endif
                    @if ($tagihan->store->contact_person)
                        <tr><td class="label">Kontak</td><td>{{ $tagihan->store->contact_person }}</td></tr>
                    @endif
                    @if ($tagihan->store->phone)
                        <tr><td class="label">Telepon</td><td>{{ $tagihan->store->phone }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <table class="rincian" style="margin-top: 14px;">
        <thead>
            <tr>
                <th style="width: 26px;">No</th>
                <th>Produk</th>
                <th style="width: 52px;" class="kanan">Terjual</th>
                <th style="width: 72px;" class="kanan">Harga jual</th>
                <th style="width: 62px;" class="kanan">Fee/pcs</th>
                <th style="width: 72px;" class="kanan">Setoran/pcs</th>
                <th style="width: 82px;" class="kanan">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tagihan->items as $i => $baris)
                <tr>
                    <td class="tengah">{{ $i + 1 }}</td>
                    <td>{{ $baris->product_name }}</td>
                    <td class="kanan">{{ angka($baris->qty_sold) }}</td>
                    <td class="kanan">{{ rupiah($baris->unit_price) }}</td>
                    <td class="kanan">{{ rupiah($baris->fee_per_unit) }}</td>
                    <td class="kanan">{{ rupiah($baris->net_per_unit) }}</td>
                    <td class="kanan"><strong>{{ rupiah($baris->subtotal) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="ringkas" style="margin-top: 10px;">
        <tr>
            <td class="label" style="width: 72%">Total penjualan kotor</td>
            <td class="kanan">{{ rupiah($tagihan->gross_total) }}</td>
        </tr>
        <tr>
            <td class="label">Total fee toko</td>
            <td class="kanan">&minus; {{ rupiah($tagihan->fee_total) }}</td>
        </tr>
        <tr class="total-akhir">
            <td class="kanan">TOTAL SETORAN</td>
            <td class="kanan">{{ rupiah($tagihan->amount_due) }}</td>
        </tr>
        @if ($tagihan->amount_paid > 0)
            <tr>
                <td class="label">Sudah dibayar</td>
                <td class="kanan">{{ rupiah($tagihan->amount_paid) }}</td>
            </tr>
            <tr>
                <td class="label"><strong>Sisa tagihan</strong></td>
                <td class="kanan"><strong class="{{ $tagihan->sisaTagihan() > 0 ? 'belum' : 'lunas' }}">{{ rupiah($tagihan->sisaTagihan()) }}</strong></td>
            </tr>
        @endif
    </table>

    <p class="terbilang">Terbilang: <strong>{{ terbilang($tagihan->amount_due, kapitalAwal: true) }}</strong></p>

    @if ($pengaturan['bank_nama'] || $pengaturan['catatan_tagihan'])
        <div class="kotak">
            @if ($pengaturan['bank_nama'])
                <p style="margin: 0 0 4px;"><strong>Pembayaran dapat ditransfer ke:</strong></p>
                <p style="margin: 0;">
                    {{ $pengaturan['bank_nama'] }} &mdash; {{ $pengaturan['bank_nomor_rekening'] }}
                    @if ($pengaturan['bank_atas_nama']) <br>a.n. {{ $pengaturan['bank_atas_nama'] }} @endif
                </p>
            @endif

            @if ($pengaturan['catatan_tagihan'])
                <p style="margin: {{ $pengaturan['bank_nama'] ? '8px' : '0' }} 0 0;">{{ $pengaturan['catatan_tagihan'] }}</p>
            @endif
        </div>
    @endif

    @if ($tagihan->notes)
        <p class="kecil" style="margin-top: 10px;"><strong>Catatan:</strong> {{ $tagihan->notes }}</p>
    @endif

    <table class="ttd">
        <tr>
            <td>
                Diterima oleh<br>{{ $tagihan->store->name }}
                <div class="kotak-ttd"></div>
                <div class="garis-ttd">{{ $tagihan->store->contact_person ?: '(nama terang)' }}</div>
            </td>
            <td>
                {{ $pengaturan['kota_ttd'] ? $pengaturan['kota_ttd'].', ' : '' }}{{ tanggal_indo($tagihan->invoice_date) }}<br>
                Hormat kami
                <div class="kotak-ttd"></div>
                <div class="garis-ttd">{{ $pengaturan['nama_penandatangan'] ?: $pengaturan['nama_usaha'] }}</div>
            </td>
        </tr>
    </table>

</body>
</html>
