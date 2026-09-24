<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Jalan {{ $pengiriman->number }}</title>
    <style>
        /* DomPDF hanya mengenal CSS sederhana, jadi gaya ditulis inline di sini. */
        @page { margin: 18mm 15mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1D2B36; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        .kop td { vertical-align: top; padding: 0 0 12px; }
        .nama-usaha { font-size: 16pt; font-weight: bold; color: #5B2A6E; margin: 0 0 3px; }
        .kecil { font-size: 8.5pt; color: #5b6b76; }
        .judul { font-size: 13pt; font-weight: bold; letter-spacing: 1px; text-align: right; color: #5B2A6E; }
        .garis { border-bottom: 2px solid #5B2A6E; margin-bottom: 12px; }
        .blok-info td { padding: 2px 0; vertical-align: top; }
        .blok-info .label { color: #5b6b76; width: 90px; }
        .rincian th { background: #EEF6FA; border-bottom: 1px solid #c7dbe6; padding: 7px 6px;
                      font-size: 9pt; text-align: left; }
        .rincian td { border-bottom: 1px solid #e6eff4; padding: 7px 6px; }
        .kanan { text-align: right; }
        .tengah { text-align: center; }
        .total-row td { background: #EEF6FA; font-weight: bold; border-bottom: none; padding: 8px 6px; }
        .ttd { margin-top: 30px; }
        .ttd td { width: 50%; text-align: center; font-size: 9pt; padding-top: 6px; }
        .kotak-ttd { height: 55px; }
        .garis-ttd { border-top: 1px solid #1D2B36; width: 65%; margin: 0 auto; padding-top: 4px; }
        .catatan { margin-top: 18px; font-size: 8.5pt; color: #5b6b76; }
    </style>
</head>
<body>

    {{-- Kop --}}
    <table class="kop">
        <tr>
            <td style="width: 60%">
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
            </td>
            <td class="judul">
                SURAT JALAN<br>
                <span style="font-size: 9pt; font-weight: normal; color: #1D2B36;">Nota Titip Jual</span>
            </td>
        </tr>
    </table>

    <div class="garis"></div>

    {{-- Info --}}
    <table>
        <tr>
            <td style="width: 50%">
                <table class="blok-info">
                    <tr><td class="label">Nomor</td><td><strong>{{ $pengiriman->number }}</strong></td></tr>
                    <tr><td class="label">Tanggal kirim</td><td>{{ tanggal_indo($pengiriman->sent_date) }}</td></tr>
                </table>
            </td>
            <td style="width: 50%">
                <table class="blok-info">
                    <tr><td class="label">Dititipkan ke</td><td><strong>{{ $pengiriman->store->name }}</strong></td></tr>
                    @if ($pengiriman->store->address)
                        <tr><td class="label">Alamat</td><td>{{ $pengiriman->store->address }}</td></tr>
                    @endif
                    @if ($pengiriman->store->contact_person)
                        <tr><td class="label">Kontak</td><td>{{ $pengiriman->store->contact_person }}
                            @if ($pengiriman->store->phone) &middot; {{ $pengiriman->store->phone }} @endif
                        </td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- Rincian --}}
    <table class="rincian" style="margin-top: 14px;">
        <thead>
            <tr>
                <th style="width: 28px;">No</th>
                <th>Produk</th>
                <th style="width: 60px;" class="kanan">Jumlah</th>
                <th style="width: 50px;">Satuan</th>
                <th style="width: 80px;" class="kanan">Harga jual</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pengiriman->items as $i => $baris)
                <tr>
                    <td class="tengah">{{ $i + 1 }}</td>
                    <td>
                        {{ $baris->product->name }}
                        <span class="kecil">({{ $baris->product->code }})</span>
                    </td>
                    <td class="kanan">{{ angka($baris->qty_sent) }}</td>
                    <td>{{ $baris->product->unit }}</td>
                    <td class="kanan">{{ rupiah($baris->unit_price) }}</td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td colspan="2">TOTAL DITITIPKAN</td>
                <td class="kanan">{{ angka($pengiriman->items->sum('qty_sent')) }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    @if ($pengiriman->notes)
        <p class="catatan"><strong>Catatan:</strong> {{ $pengiriman->notes }}</p>
    @endif

    <p class="catatan">
        Barang di atas dititipkan untuk dijual. Barang yang tidak laku dapat ditarik kembali saat rekonsiliasi.
        Setoran dihitung dari jumlah yang terjual dikurangi fee toko.
    </p>

    {{-- Tanda tangan --}}
    <table class="ttd">
        <tr>
            <td>
                {{ $pengaturan['kota_ttd'] ? $pengaturan['kota_ttd'].', ' : '' }}{{ tanggal_indo($pengiriman->sent_date) }}<br>
                Pengirim
                <div class="kotak-ttd"></div>
                <div class="garis-ttd">{{ $pengaturan['nama_penandatangan'] ?: $pengiriman->user?->name ?: '' }}</div>
            </td>
            <td>
                Diterima oleh<br>
                {{ $pengiriman->store->name }}
                <div class="kotak-ttd"></div>
                <div class="garis-ttd">{{ $pengiriman->store->contact_person ?: '(nama terang)' }}</div>
            </td>
        </tr>
    </table>

</body>
</html>
