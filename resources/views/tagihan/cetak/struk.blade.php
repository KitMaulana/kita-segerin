<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk Tagihan {{ $tagihan->number }}</title>
    <style>
        body { margin: 0; background: #EEF6FA; font-family: "Courier New", ui-monospace, monospace;
               font-size: 11px; line-height: 1.45; color: #1D2B36; }
        .kertas { width: 48mm; margin: 16px auto; padding: 6mm 3mm; background: #fff;
                  box-shadow: 0 2px 12px rgba(29,43,54,.12); }
        .tengah { text-align: center; }
        .kanan { text-align: right; }
        .tebal { font-weight: bold; }
        .kecil { font-size: 10px; }
        hr { border: 0; border-top: 1px dashed #1D2B36; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 1px 0; vertical-align: top; }
        .judul { font-size: 13px; font-weight: bold; letter-spacing: .5px; }
        .total { font-size: 13px; font-weight: bold; }
        .ttd { margin-top: 16px; }
        .ttd div { height: 32px; border-bottom: 1px solid #1D2B36; margin-bottom: 3px; }
        .aksi { max-width: 48mm; margin: 0 auto 24px; display: flex; gap: 8px; }
        .aksi button, .aksi a { flex: 1; min-height: 40px; display: inline-flex; align-items: center;
            justify-content: center; border: 0; border-radius: 10px; background: #5B2A6E; color: #fff;
            font-family: system-ui, sans-serif; font-size: 13px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .aksi a { background: #fff; color: #1D2B36; border: 1px solid rgba(91,42,110,.25); }
        @media print {
            @page { size: 58mm auto; margin: 0; }
            body { background: #fff; }
            .kertas { width: auto; margin: 0; padding: 2mm; box-shadow: none; }
            .aksi { display: none; }
        }
    </style>
</head>
<body>

    <div class="kertas">
        <p class="tengah judul" style="margin: 0 0 2px;">{{ $pengaturan['nama_usaha'] }}</p>
        @if ($pengaturan['alamat'])
            <p class="tengah kecil" style="margin: 0;">{{ $pengaturan['alamat'] }}</p>
        @endif
        @if ($pengaturan['telepon'])
            <p class="tengah kecil" style="margin: 0;">{{ $pengaturan['telepon'] }}</p>
        @endif

        <hr>

        <p class="tengah tebal" style="margin: 0 0 4px;">TAGIHAN SETORAN</p>

        <table class="kecil">
            <tr><td>No</td><td class="kanan tebal">{{ $tagihan->number }}</td></tr>
            <tr><td>Tanggal</td><td class="kanan">{{ tanggal_singkat($tagihan->invoice_date) }}</td></tr>
            <tr><td>Tempo</td><td class="kanan tebal">{{ tanggal_singkat($tagihan->due_date) }}</td></tr>
            <tr><td>Toko</td><td class="kanan">{{ $tagihan->store->name }}</td></tr>
        </table>

        <hr>

        <table>
            @foreach ($tagihan->items as $baris)
                <tr><td colspan="2" class="tebal">{{ $baris->product_name }}</td></tr>
                <tr class="kecil">
                    <td>{{ angka($baris->qty_sold) }} &times; {{ rupiah($baris->net_per_unit) }}</td>
                    <td class="kanan">{{ rupiah($baris->subtotal) }}</td>
                </tr>
            @endforeach
        </table>

        <hr>

        <table class="kecil">
            <tr><td>Penjualan kotor</td><td class="kanan">{{ rupiah($tagihan->gross_total) }}</td></tr>
            <tr><td>Fee toko</td><td class="kanan">- {{ rupiah($tagihan->fee_total) }}</td></tr>
        </table>

        <hr>

        <table class="total">
            <tr><td>SETORAN</td><td class="kanan">{{ rupiah($tagihan->amount_due) }}</td></tr>
        </table>

        @if ($tagihan->amount_paid > 0)
            <table class="kecil" style="margin-top: 4px;">
                <tr><td>Dibayar</td><td class="kanan">{{ rupiah($tagihan->amount_paid) }}</td></tr>
                <tr class="tebal"><td>Sisa</td><td class="kanan">{{ rupiah($tagihan->sisaTagihan()) }}</td></tr>
            </table>
        @endif

        <hr>

        <p class="kecil" style="margin: 0; font-style: italic;">
            Terbilang: {{ terbilang($tagihan->amount_due) }}
        </p>

        @if ($pengaturan['bank_nama'])
            <hr>
            <p class="kecil" style="margin: 0;">
                Transfer ke:<br>
                <span class="tebal">{{ $pengaturan['bank_nama'] }} {{ $pengaturan['bank_nomor_rekening'] }}</span><br>
                a.n. {{ $pengaturan['bank_atas_nama'] }}
            </p>
        @endif

        <table class="ttd kecil">
            <tr>
                <td class="tengah" style="width: 50%; padding-right: 3px;">
                    Penerima<div></div>{{ $tagihan->store->contact_person ?: '' }}
                </td>
                <td class="tengah" style="width: 50%; padding-left: 3px;">
                    Hormat kami<div></div>{{ $pengaturan['nama_penandatangan'] ?: '' }}
                </td>
            </tr>
        </table>

        <p class="tengah kecil" style="margin: 8px 0 0;">Terima kasih</p>
    </div>

    <div class="aksi">
        <button type="button" onclick="window.print()">Cetak</button>
        <a href="{{ route('tagihan.show', $tagihan) }}">Kembali</a>
    </div>

</body>
</html>
