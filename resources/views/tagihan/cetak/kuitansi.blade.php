<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kuitansi {{ $pembayaran->number }}</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #1D2B36; margin: 0; }
        table { width: 100%; border-collapse: collapse; }

        .bingkai { border: 2px solid #5B2A6E; padding: 14px 16px; }
        .nama-usaha { font-size: 15pt; font-weight: bold; color: #5B2A6E; margin: 0; }
        .kecil { font-size: 8.5pt; color: #5b6b76; }
        .judul { font-size: 17pt; font-weight: bold; letter-spacing: 3px; color: #5B2A6E; text-align: right; margin: 0; }

        .baris td { padding: 6px 0; vertical-align: top; }
        .baris .label { width: 130px; color: #5b6b76; }
        .isi { border-bottom: 1px dotted #5b6b76; padding-bottom: 3px; }

        .nominal { display: inline-block; background: #EEF6FA; border: 1px solid #5B2A6E;
                   padding: 8px 14px; font-size: 15pt; font-weight: bold; color: #5B2A6E; }

        .ttd { margin-top: 18px; }
        .ttd td { text-align: center; font-size: 9pt; }
        .kotak-ttd { height: 45px; }
        .garis-ttd { border-top: 1px solid #1D2B36; width: 70%; margin: 0 auto; padding-top: 3px; }
    </style>
</head>
<body>

    <div class="bingkai">
        <table>
            <tr>
                <td style="width: 60%">
                    <p class="nama-usaha">{{ $pengaturan['nama_usaha'] }}</p>
                    @if ($pengaturan['alamat'])
                        <p class="kecil" style="margin: 2px 0 0;">{{ $pengaturan['alamat'] }}</p>
                    @endif
                    @if ($pengaturan['telepon'])
                        <p class="kecil" style="margin: 1px 0 0;">Telp/WA: {{ $pengaturan['telepon'] }}</p>
                    @endif
                </td>
                <td>
                    <p class="judul">KUITANSI</p>
                    <p class="kecil" style="text-align: right; margin: 3px 0 0;">No. {{ $pembayaran->number }}</p>
                </td>
            </tr>
        </table>

        <table class="baris" style="margin-top: 14px;">
            <tr>
                <td class="label">Sudah terima dari</td>
                <td class="isi"><strong>{{ $pembayaran->invoice->store->name }}</strong></td>
            </tr>
            <tr>
                <td class="label">Uang sejumlah</td>
                <td class="isi" style="font-style: italic;">{{ terbilang($pembayaran->amount, kapitalAwal: true) }}</td>
            </tr>
            <tr>
                <td class="label">Untuk pembayaran</td>
                <td class="isi">
                    Setoran titip jual es krim, tagihan {{ $pembayaran->invoice->number }}
                    (periode {{ tanggal_singkat($pembayaran->invoice->period_start) }}
                    &ndash; {{ tanggal_singkat($pembayaran->invoice->period_end) }})
                </td>
            </tr>
            <tr>
                <td class="label">Cara pembayaran</td>
                <td class="isi">
                    {{ $pembayaran->namaMetode() }}
                    @if ($pembayaran->reference) &mdash; {{ $pembayaran->reference }} @endif
                </td>
            </tr>
        </table>

        <table style="margin-top: 14px;">
            <tr>
                <td style="width: 55%">
                    <span class="nominal">{{ rupiah($pembayaran->amount) }}</span>
                    <p class="kecil" style="margin: 6px 0 0;">
                        Sisa tagihan setelah pembayaran ini:
                        <strong>{{ rupiah($pembayaran->invoice->sisaTagihan()) }}</strong>
                    </p>
                </td>
                <td>
                    <table class="ttd">
                        <tr>
                            <td>
                                {{ $pengaturan['kota_ttd'] ? $pengaturan['kota_ttd'].', ' : '' }}{{ tanggal_indo($pembayaran->paid_at) }}<br>
                                Penerima
                                <div class="kotak-ttd"></div>
                                <div class="garis-ttd">{{ $pengaturan['nama_penandatangan'] ?: $pembayaran->user?->name ?: '' }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
