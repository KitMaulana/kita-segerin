<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>@yield('judul') — {{ $pengaturan['nama_usaha'] }}</title>
    <style>
        @page { margin: 14mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1D2B36; margin: 0; }
        table { width: 100%; border-collapse: collapse; }

        .kop { border-bottom: 2px solid #5B2A6E; padding-bottom: 8px; margin-bottom: 10px; }
        .kop td { vertical-align: bottom; }
        .nama-usaha { font-size: 14pt; font-weight: bold; color: #5B2A6E; margin: 0; }
        .kecil { font-size: 8pt; color: #5b6b76; }
        .judul { font-size: 12pt; font-weight: bold; text-align: right; color: #5B2A6E; margin: 0; }

        .data th { background: #EEF6FA; border-bottom: 1px solid #c7dbe6; padding: 6px 5px; text-align: left; font-size: 8.5pt; }
        .data td { border-bottom: 1px solid #e6eff4; padding: 5px; }
        .data tfoot td { background: #EEF6FA; font-weight: bold; border-bottom: none; }

        .kanan { text-align: right; }
        .tengah { text-align: center; }
        .tebal { font-weight: bold; }
        .mint { color: #12A383; }
        .strawberry { color: #D9435A; }
        .berry { color: #5B2A6E; }
        .mango { color: #E89B2D; }

        .kartu { border: 1px solid #c7dbe6; background: #EEF6FA; padding: 7px 9px; }
        .kartu .label { font-size: 8pt; color: #5b6b76; }
        .kartu .nilai { font-size: 12pt; font-weight: bold; }

        .kaki { margin-top: 14px; font-size: 7.5pt; color: #5b6b76; text-align: right; }
    </style>
</head>
<body>

    <table class="kop">
        <tr>
            <td style="width: 60%">
                <p class="nama-usaha">{{ $pengaturan['nama_usaha'] }}</p>
                @if ($pengaturan['alamat'])
                    <p class="kecil" style="margin: 2px 0 0;">{{ $pengaturan['alamat'] }}</p>
                @endif
            </td>
            <td>
                <p class="judul">@yield('judul')</p>
                <p class="kecil" style="text-align: right; margin: 3px 0 0;">
                    Periode {{ tanggal_indo($dari) }} &ndash; {{ tanggal_indo($sampai) }}
                </p>
            </td>
        </tr>
    </table>

    @yield('isi')

    <p class="kaki">Dicetak {{ tanggal_indo(now(), true) }} &middot; {{ $pengaturan['nama_usaha'] }}</p>

</body>
</html>
