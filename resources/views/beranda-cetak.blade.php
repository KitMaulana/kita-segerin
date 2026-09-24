<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Infografis {{ $bulan->locale('id')->translatedFormat('F Y') }} — {{ $pengaturan['nama_usaha'] }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Dirancang untuk A4 landscape lewat cetak peramban. */
        @page { size: A4 landscape; margin: 10mm; }

        @media print {
            .tanpa-cetak { display: none !important; }
            body { background: #fff !important; }
            .lembar { box-shadow: none !important; margin: 0 !important; width: auto !important; }
            .pecah-hindari { break-inside: avoid; page-break-inside: avoid; }
        }

        @media screen {
            .lembar { width: 297mm; min-height: 200mm; margin: 16px auto; box-shadow: 0 2px 16px rgba(29,43,54,.12); }
        }
    </style>
</head>
<body class="bg-frost font-sans text-ink antialiased">

    <div class="tanpa-cetak mx-auto flex max-w-[297mm] gap-2 px-4 pt-4">
        <button type="button" onclick="window.print()"
                class="inline-flex min-h-[44px] flex-1 items-center justify-center rounded-xl bg-berry px-5 text-sm font-bold text-white">
            Cetak infografis
        </button>
        <a href="{{ route('beranda', ['bulan' => $bulan->format('Y-m')]) }}"
           class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-berry/20 bg-white px-5 text-sm font-bold text-ink">
            Kembali
        </a>
    </div>

    <div class="lembar bg-white p-8">

        {{-- Kop --}}
        <header class="flex items-end justify-between gap-6 border-b-2 border-berry pb-4">
            <div>
                <h1 class="text-2xl font-extrabold text-berry">{{ $pengaturan['nama_usaha'] }}</h1>
                @if ($pengaturan['alamat'])
                    <p class="mt-0.5 text-xs text-ink/55">{{ $pengaturan['alamat'] }}</p>
                @endif
            </div>
            <div class="text-right">
                <p class="text-lg font-extrabold text-berry">INFOGRAFIS BULANAN</p>
                <p class="text-sm font-semibold">{{ $bulan->locale('id')->translatedFormat('F Y') }}</p>
            </div>
        </header>

        {{-- Kartu ringkasan --}}
        <section class="pecah-hindari mt-5 grid grid-cols-5 gap-3">
            @foreach ([
                ['Setoran', rupiah($ringkas['setoran']), 'text-berry'],
                ['Laba bersih', rupiah($ringkas['laba_bersih']), $ringkas['laba_bersih'] >= 0 ? 'text-mint' : 'text-strawberry'],
                ['Piutang', rupiah($piutang), 'text-mango'],
                ['Nilai persediaan', rupiah($persediaan['nilai_total']), 'text-ink'],
                ['Terjual', angka($ringkas['qty_terjual']).' pcs', 'text-ink'],
            ] as [$label, $nilai, $warna])
                <div class="rounded-xl border border-berry/15 bg-frost px-3 py-3">
                    <p class="text-[10px] font-semibold text-ink/55">{{ $label }}</p>
                    <p class="mt-1 text-base font-extrabold tabular-nums {{ $warna }}">{{ $nilai }}</p>
                </div>
            @endforeach
        </section>

        {{-- Grafik --}}
        <section class="pecah-hindari mt-5 grid grid-cols-3 gap-4">
            <div class="col-span-2 rounded-xl border border-berry/15 p-3">
                <h2 class="text-xs font-bold text-ink/70">Tren setoran &amp; laba (6 bulan)</h2>
                <div class="mt-2 h-[52mm]"><canvas id="grafikTren"></canvas></div>
            </div>

            <div class="rounded-xl border border-berry/15 p-3">
                <h2 class="text-xs font-bold text-ink/70">Komposisi penjualan</h2>
                <div class="mt-2 h-[52mm]"><canvas id="grafikDonat"></canvas></div>
            </div>
        </section>

        <section class="pecah-hindari mt-4 grid grid-cols-2 gap-4">
            {{-- Tingkat laku --}}
            <div class="rounded-xl border border-berry/15 p-3">
                <h2 class="text-xs font-bold text-ink/70">Tingkat laku per produk</h2>

                @if ($per_produk->isEmpty())
                    <p class="py-6 text-center text-xs text-ink/50">Belum ada penjualan bulan ini.</p>
                @else
                    <ul class="mt-2 space-y-2">
                        @foreach ($per_produk->take(6) as $b)
                            <li>
                                <div class="flex items-baseline justify-between gap-2 text-[11px]">
                                    <span class="truncate font-semibold">{{ $b['nama'] }}</span>
                                    <span class="shrink-0 font-extrabold tabular-nums text-berry">{{ persen($b['tingkat_laku']) }}</span>
                                </div>
                                <div class="mt-1 flex items-center gap-1">
                                    <span class="h-3 w-1 shrink-0 rounded-full bg-[#C9A227]"></span>
                                    <div class="h-3 flex-1 overflow-hidden rounded-full bg-frost">
                                        <div class="h-full rounded-full bg-berry" style="width: {{ min(100, $b['tingkat_laku']) }}%"></div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Toko teratas --}}
            <div class="rounded-xl border border-berry/15 p-3">
                <h2 class="text-xs font-bold text-ink/70">5 toko dengan setoran terbesar</h2>
                <div class="mt-2 h-[42mm]"><canvas id="grafikToko"></canvas></div>
            </div>
        </section>

        {{-- Ringkasan laba rugi --}}
        <section class="pecah-hindari mt-4 rounded-xl border border-berry/15 p-3">
            <h2 class="text-xs font-bold text-ink/70">Ringkasan laba rugi</h2>

            <table class="mt-2 w-full text-[11px]">
                <tbody>
                    @foreach ([
                        ['Penjualan kotor', $ringkas['penjualan_kotor']],
                        ['Fee toko', -$ringkas['fee_toko']],
                        ['Setoran', $ringkas['setoran']],
                        ['HPP', -$ringkas['hpp']],
                        ['Laba kotor', $ringkas['laba_kotor']],
                        ['Kerugian rusak', -$ringkas['kerugian_rusak']],
                        ['Biaya operasional', -$ringkas['total_biaya']],
                    ] as [$label, $nilai])
                        <tr class="border-b border-berry/5">
                            <td class="py-1">{{ $label }}</td>
                            <td class="py-1 text-right tabular-nums">
                                {{ $nilai < 0 ? '− '.rupiah(abs($nilai)) : rupiah($nilai) }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-berry text-white">
                        <td class="px-2 py-1.5 font-extrabold">LABA BERSIH</td>
                        <td class="px-2 py-1.5 text-right font-extrabold tabular-nums">{{ rupiah($ringkas['laba_bersih']) }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <footer class="mt-4 text-right text-[10px] text-ink/45">
            Dicetak {{ tanggal_indo(now(), true) }} &middot; {{ $pengaturan['nama_usaha'] }}
        </footer>
    </div>

    @include('beranda.grafik')

    @stack('scripts')

</body>
</html>
