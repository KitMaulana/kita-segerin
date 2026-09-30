<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $tagihan->number }}</title>
    <style>
        /* ── Reset & Base ── */
        @page { margin: 14mm 16mm 18mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9.5pt;
            color: #1D2B36;
            margin: 0;
            line-height: 1.45;
        }

        /* ── Header / KOP ── */
        .kop { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .kop td { vertical-align: top; padding: 0; }

        .nama-usaha {
            font-size: 17pt;
            font-weight: bold;
            color: #5B2A6E;
            margin: 0 0 2px;
            letter-spacing: -0.3px;
        }
        .sub-usaha { font-size: 8.5pt; color: #6B7A85; margin: 1px 0 0; }

        .judul-invoice {
            text-align: right;
        }
        .judul-invoice p.label {
            font-size: 9pt;
            color: #8B9BAA;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin: 0;
        }
        .judul-invoice p.nomor {
            font-size: 13pt;
            font-weight: bold;
            color: #5B2A6E;
            margin: 2px 0 0;
        }

        /* ── Garis pemisah header ── */
        .accent-bar {
            width: 100%;
            height: 3px;
            background: linear-gradient(to right, #5B2A6E, #A855C4, #5B2A6E);
            margin: 10px 0 12px;
            border-radius: 2px;
        }

        /* ── Badge status watermark ── */
        .badge-status {
            display: inline-block;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 2px 9px;
            border-radius: 20px;
            margin-top: 5px;
        }
        .badge-lunas { background: #D1FAF0; color: #0E7A5F; border: 1px solid #A7F3D0; }
        .badge-belum { background: #FEF3C7; color: #B45309; border: 1px solid #FDE68A; }
        .badge-batal { background: #FEE2E2; color: #B91C1C; border: 1px solid #FCA5A5; }

        /* ── Panel info (kepada / detail tagihan) ── */
        .panel-row { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .panel-row td { vertical-align: top; padding: 0; }

        .panel {
            border: 1px solid #E0EBF3;
            border-radius: 6px;
            padding: 9px 12px;
            background: #F8FBFD;
        }
        .panel-title {
            font-size: 7.5pt;
            font-weight: bold;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #9AABBA;
            margin: 0 0 6px;
        }
        .panel dl { margin: 0; }
        .panel dt { font-size: 7.5pt; color: #7A8FA0; margin: 3px 0 0; }
        .panel dd { font-size: 9pt; font-weight: bold; margin: 0; color: #1D2B36; }
        .panel dd.normal { font-weight: normal; }
        .panel dd.berry { color: #5B2A6E; }

        /* ── Tabel rincian ── */
        .tbl-rincian { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .tbl-rincian thead tr {
            background: #5B2A6E;
            color: #fff;
        }
        .tbl-rincian thead th {
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            padding: 7px 8px;
            text-align: left;
        }
        .tbl-rincian thead th.kanan { text-align: right; }

        .tbl-rincian tbody tr:nth-child(even) { background: #F5F0F8; }
        .tbl-rincian tbody tr:nth-child(odd)  { background: #FFFFFF; }
        .tbl-rincian tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #EDE8F2;
            font-size: 9pt;
        }
        .tbl-rincian tbody td.kanan { text-align: right; }
        .tbl-rincian tbody td.tengah { text-align: center; }
        .tbl-rincian tbody td.nama { font-weight: 600; }
        .tbl-rincian tbody td.fee  { color: #D97706; }

        /* ── Ringkasan total ── */
        .ringkasan { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .ringkasan td { padding: 3.5px 8px; font-size: 9pt; }
        .ringkasan td.label { text-align: right; color: #6B7A85; width: 72%; }
        .ringkasan td.nilai { text-align: right; }

        .baris-total {
            background: #5B2A6E;
            color: #fff;
        }
        .baris-total td {
            padding: 8px 10px !important;
            font-size: 11.5pt !important;
            font-weight: bold !important;
        }
        .baris-total td.label { color: #E9D8F5 !important; }

        .baris-sisa td { border-top: 1px solid #EDE8F2; }
        .baris-sisa .nilai-sisa { color: #D97706; font-weight: bold; }
        .baris-lunas-nilai { color: #0E7A5F; font-weight: bold; }

        /* ── Terbilang ── */
        .terbilang-box {
            margin-top: 10px;
            background: #F5F0F8;
            border-left: 3px solid #5B2A6E;
            padding: 6px 10px;
            font-size: 8.5pt;
            font-style: italic;
            color: #4A3A56;
            border-radius: 0 4px 4px 0;
        }

        /* ── Info pembayaran / catatan ── */
        .kotak-info {
            border: 1px solid #DDE8F0;
            border-radius: 6px;
            padding: 8px 12px;
            margin-top: 12px;
            font-size: 8.5pt;
            background: #F8FBFD;
        }
        .kotak-info strong { color: #5B2A6E; }

        /* ── Riwayat pembayaran ── */
        .riwayat-title {
            font-size: 7.5pt;
            font-weight: bold;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #9AABBA;
            margin: 14px 0 5px;
        }
        .tbl-bayar { width: 100%; border-collapse: collapse; }
        .tbl-bayar th {
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #7A8FA0;
            border-bottom: 1px solid #DDE8F0;
            padding: 3px 6px;
            text-align: left;
        }
        .tbl-bayar th.kanan { text-align: right; }
        .tbl-bayar td { font-size: 8.5pt; padding: 4px 6px; border-bottom: 1px solid #F0F4F8; }
        .tbl-bayar td.kanan { text-align: right; }

        /* ── Area tanda tangan ── */
        .ttd { width: 100%; border-collapse: collapse; margin-top: 28px; }
        .ttd td { width: 50%; text-align: center; font-size: 8.5pt; vertical-align: bottom; }
        .kotak-ttd { height: 52px; }
        .garis-ttd {
            border-top: 1px solid #5B2A6E;
            width: 70%;
            margin: 0 auto;
            padding-top: 4px;
            font-size: 8.5pt;
            font-weight: bold;
            color: #5B2A6E;
        }

        /* ── Footer halaman ── */
        .footer {
            position: fixed;
            bottom: -14mm;
            left: 0; right: 0;
            text-align: center;
            font-size: 7.5pt;
            color: #B0BEC8;
            border-top: 1px solid #EDE8F2;
            padding-top: 4px;
        }

        /* ── Utility ── */
        .kanan { text-align: right; }
        .lunas { color: #0E7A5F; }
        .belum { color: #D97706; }
        .batal { color: #B91C1C; }
        .section-gap { margin-top: 14px; }
    </style>
</head>
<body>

    {{-- ═══════════════════════════════════════════════
         KOP SURAT
    ═══════════════════════════════════════════════ --}}
    <table class="kop">
        <tr>
            {{-- Identitas usaha --}}
            <td style="width: 60%">
                @if ($pengaturan['logo_path'] && file_exists(public_path('storage/'.$pengaturan['logo_path'])))
                    <img src="{{ public_path('storage/'.$pengaturan['logo_path']) }}"
                         alt="Logo"
                         style="height: 40px; margin-bottom: 5px; display: block;">
                @endif
                <p class="nama-usaha">{{ $pengaturan['nama_usaha'] }}</p>
                @if ($pengaturan['alamat'])
                    <p class="sub-usaha">{{ $pengaturan['alamat'] }}</p>
                @endif
                @if ($pengaturan['telepon'])
                    <p class="sub-usaha">Telp/WA: {{ $pengaturan['telepon'] }}</p>
                @endif
                @if ($pengaturan['email'])
                    <p class="sub-usaha">{{ $pengaturan['email'] }}</p>
                @endif
            </td>

            {{-- Judul & status --}}
            <td class="judul-invoice">
                <p class="label">TAGIHAN SETORAN</p>
                <p class="nomor">{{ $tagihan->number }}</p>

                @if ($tagihan->status === 'cancelled')
                    <span class="badge-status badge-batal">&#x2715; Dibatalkan</span>
                @elseif ($tagihan->status === 'paid')
                    <span class="badge-status badge-lunas">&#x2714; Lunas</span>
                @else
                    <span class="badge-status badge-belum">&#x23F3; Belum Dibayar</span>
                @endif
            </td>
        </tr>
    </table>

    {{-- Accent bar --}}
    <div class="accent-bar"></div>

    {{-- ═══════════════════════════════════════════════
         PANEL INFO: Kepada | Detail Tagihan
    ═══════════════════════════════════════════════ --}}
    <table class="panel-row">
        <tr>
            {{-- Kepada --}}
            <td style="width: 50%; padding-right: 6px;">
                <div class="panel">
                    <p class="panel-title">Kepada</p>
                    <dl>
                        <dd class="berry" style="font-size: 10.5pt;">{{ $tagihan->store->name }}</dd>
                        @if ($tagihan->store->address)
                            <dt>Alamat</dt>
                            <dd class="normal">{{ $tagihan->store->address }}</dd>
                        @endif
                        @if ($tagihan->store->contact_person)
                            <dt>Kontak</dt>
                            <dd class="normal">{{ $tagihan->store->contact_person }}</dd>
                        @endif
                        @if ($tagihan->store->phone)
                            <dt>Telepon</dt>
                            <dd class="normal">{{ $tagihan->store->phone }}</dd>
                        @endif
                    </dl>
                </div>
            </td>

            {{-- Detail tagihan --}}
            <td style="width: 50%; padding-left: 6px;">
                <div class="panel">
                    <p class="panel-title">Detail Tagihan</p>
                    <dl>
                        <dt>Tanggal Tagihan</dt>
                        <dd>{{ tanggal_indo($tagihan->invoice_date) }}</dd>

                        <dt>Jatuh Tempo</dt>
                        <dd class="{{ $tagihan->lewatJatuhTempo() && $tagihan->status !== 'paid' ? 'batal' : '' }}">
                            {{ tanggal_indo($tagihan->due_date) }}
                            @if ($tagihan->lewatJatuhTempo() && $tagihan->status !== 'paid' && $tagihan->status !== 'cancelled')
                                <span style="font-size: 7.5pt; font-weight: normal;">(lewat {{ abs($tagihan->umurPiutangHari()) }} hari)</span>
                            @endif
                        </dd>

                        <dt>Periode</dt>
                        <dd class="normal">{{ tanggal_singkat($tagihan->period_start) }} &ndash; {{ tanggal_singkat($tagihan->period_end) }}</dd>
                    </dl>
                </div>
            </td>
        </tr>
    </table>

    {{-- ═══════════════════════════════════════════════
         TABEL RINCIAN PRODUK
    ═══════════════════════════════════════════════ --}}
    <table class="tbl-rincian">
        <thead>
            <tr>
                <th style="width: 24px;">#</th>
                <th>Produk</th>
                <th class="kanan" style="width: 52px;">Terjual</th>
                <th class="kanan" style="width: 74px;">Harga Jual</th>
                <th class="kanan" style="width: 62px;">Fee/pcs</th>
                <th class="kanan" style="width: 74px;">Setoran/pcs</th>
                <th class="kanan" style="width: 82px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tagihan->items as $i => $baris)
                <tr>
                    <td class="tengah" style="color: #9AABBA;">{{ $i + 1 }}</td>
                    <td class="nama">{{ $baris->product_name }}</td>
                    <td class="kanan">{{ angka($baris->qty_sold) }}</td>
                    <td class="kanan">{{ rupiah($baris->unit_price) }}</td>
                    <td class="kanan fee">{{ rupiah($baris->fee_per_unit) }}</td>
                    <td class="kanan">{{ rupiah($baris->net_per_unit) }}</td>
                    <td class="kanan"><strong>{{ rupiah($baris->subtotal) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ═══════════════════════════════════════════════
         RINGKASAN TOTAL
    ═══════════════════════════════════════════════ --}}
    <table class="ringkasan">
        <tr>
            <td class="label">Total penjualan kotor</td>
            <td class="nilai">{{ rupiah($tagihan->gross_total) }}</td>
        </tr>
        <tr>
            <td class="label" style="color: #D97706;">Total fee toko</td>
            <td class="nilai" style="color: #D97706;">&minus; {{ rupiah($tagihan->fee_total) }}</td>
        </tr>
        <tr class="baris-total">
            <td class="label">TOTAL SETORAN</td>
            <td class="nilai kanan">{{ rupiah($tagihan->amount_due) }}</td>
        </tr>
        @if ($tagihan->amount_paid > 0)
            <tr>
                <td class="label">Sudah dibayar</td>
                <td class="nilai baris-lunas-nilai">{{ rupiah($tagihan->amount_paid) }}</td>
            </tr>
            <tr class="baris-sisa">
                <td class="label"><strong>Sisa tagihan</strong></td>
                <td class="nilai">
                    <span class="{{ $tagihan->sisaTagihan() > 0 ? 'nilai-sisa' : 'baris-lunas-nilai' }}">
                        {{ rupiah($tagihan->sisaTagihan()) }}
                    </span>
                </td>
            </tr>
        @endif
    </table>

    {{-- Terbilang --}}
    <div class="terbilang-box">
        Terbilang: <strong>{{ terbilang($tagihan->amount_due, kapitalAwal: true) }}</strong>
    </div>

    {{-- ═══════════════════════════════════════════════
         INFO BANK / CATATAN
    ═══════════════════════════════════════════════ --}}
    @if ($pengaturan['bank_nama'] || $pengaturan['catatan_tagihan'])
        <div class="kotak-info">
            @if ($pengaturan['bank_nama'])
                <p style="margin: 0 0 4px;">
                    <strong>Pembayaran dapat ditransfer ke:</strong>
                </p>
                <p style="margin: 0;">
                    <strong>{{ $pengaturan['bank_nama'] }}</strong>
                    &mdash; {{ $pengaturan['bank_nomor_rekening'] }}
                    @if ($pengaturan['bank_atas_nama'])
                        &nbsp;&nbsp;a.n. <strong>{{ $pengaturan['bank_atas_nama'] }}</strong>
                    @endif
                </p>
            @endif
            @if ($pengaturan['catatan_tagihan'])
                <p style="margin: {{ $pengaturan['bank_nama'] ? '7px' : '0' }} 0 0;">
                    {{ $pengaturan['catatan_tagihan'] }}
                </p>
            @endif
        </div>
    @endif

    @if ($tagihan->notes)
        <p style="margin-top: 8px; font-size: 8.5pt; color: #6B7A85;">
            <strong>Catatan:</strong> {{ $tagihan->notes }}
        </p>
    @endif

    {{-- ═══════════════════════════════════════════════
         RIWAYAT PEMBAYARAN (jika ada)
    ═══════════════════════════════════════════════ --}}
    @if ($tagihan->payments && $tagihan->payments->count() > 0)
        <p class="riwayat-title">Riwayat Pembayaran</p>
        <table class="tbl-bayar">
            <thead>
                <tr>
                    <th>No. Pembayaran</th>
                    <th>Tanggal</th>
                    <th>Metode</th>
                    <th class="kanan">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tagihan->payments as $bayar)
                    <tr>
                        <td>{{ $bayar->number }}</td>
                        <td>{{ tanggal_indo($bayar->paid_at) }}</td>
                        <td>{{ $bayar->namaMetode() }}</td>
                        <td class="kanan">{{ rupiah($bayar->amount) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- ═══════════════════════════════════════════════
         AREA TANDA TANGAN
    ═══════════════════════════════════════════════ --}}
    <table class="ttd">
        <tr>
            <td>
                Diterima oleh,<br>
                <em style="font-size: 8pt; color: #6B7A85;">{{ $tagihan->store->name }}</em>
                <div class="kotak-ttd"></div>
                <div class="garis-ttd">
                    {{ $tagihan->store->contact_person ?: '( nama terang )' }}
                </div>
            </td>
            <td>
                {{ $pengaturan['kota_ttd'] ? $pengaturan['kota_ttd'].', ' : '' }}{{ tanggal_indo($tagihan->invoice_date) }}<br>
                Hormat kami,
                <div class="kotak-ttd"></div>
                <div class="garis-ttd">
                    {{ $pengaturan['nama_penandatangan'] ?: $pengaturan['nama_usaha'] }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Footer --}}
    <div class="footer">
        {{ $pengaturan['nama_usaha'] }} &nbsp;&bull;&nbsp; {{ $tagihan->number }}
        &nbsp;&bull;&nbsp; Dicetak {{ tanggal_indo(now()) }}
    </div>

</body>
</html>
