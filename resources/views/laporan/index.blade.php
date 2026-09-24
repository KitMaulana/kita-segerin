<x-app-layout title="Laporan" subtitle="Ringkasan keuangan KITAA SEGERIN">

    <div class="space-y-5">

        @include('laporan.partials.saringan')

        {{-- Ringkasan cepat --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([
                ['Setoran periode ini', rupiah($ringkas['setoran']), 'text-berry'],
                ['Laba bersih', rupiah($ringkas['laba_bersih']), $ringkas['laba_bersih'] >= 0 ? 'text-mint' : 'text-strawberry'],
                ['Piutang belum dibayar', rupiah($totalPiutang), $totalPiutang > 0 ? 'text-mango' : 'text-ink/50'],
                ['Nilai persediaan', rupiah($persediaan['nilai_total']), 'text-ink'],
            ] as [$label, $nilai, $warna])
                <div class="rounded-2xl border border-berry/10 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold leading-snug text-ink/55">{{ $label }}</p>
                    <p class="mt-2 text-lg font-extrabold tabular-nums {{ $warna }} sm:text-xl">{{ $nilai }}</p>
                </div>
            @endforeach
        </div>

        {{-- Daftar laporan --}}
        <x-kartu judul="Pilih laporan" padat>
            <ul class="divide-y divide-berry/5">
                @foreach ([
                    'laba-rugi' => ['Laba Rugi', 'Penjualan kotor, fee toko, setoran, HPP, biaya, sampai laba bersih.', 'laporan'],
                    'produk' => ['Rekap per Produk', 'Kirim, terjual, retur, rusak, tingkat laku, setoran, laba, dan margin.', 'produk'],
                    'toko' => ['Rekap per Toko', 'Jumlah pengiriman, setoran, fee dibayar ke toko, laba, dan piutang.', 'toko'],
                    'piutang' => ['Piutang & Umur Piutang', 'Tagihan belum lunas dikelompokkan 0–7, 8–14, 15–30, dan lebih dari 30 hari.', 'tagihan'],
                    'arus-kas' => ['Arus Kas', 'Saldo awal, total masuk, total keluar, dan saldo akhir.', 'kas'],
                    'persediaan' => ['Persediaan', 'Stok gudang, stok di toko, dan nilai persediaan.', 'stok'],
                    'periode-pengiriman' => ['Rekap Periode Pengiriman', 'Format mingguan: per produk kirim, terjual, sisa, dan keuntungan.', 'pengiriman'],
                ] as $kunci => [$judul, $keterangan, $ikon])
                    <li>
                        <a href="{{ route('laporan.tampil', array_merge([$kunci], request()->query())) }}"
                           class="flex items-center gap-3 px-4 py-3.5 transition hover:bg-frost sm:px-5">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-berry/10 text-berry">
                                <x-icon :name="$ikon" class="h-5 w-5" />
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block font-bold">{{ $judul }}</span>
                                <span class="mt-0.5 block text-xs leading-relaxed text-ink/55">{{ $keterangan }}</span>
                            </span>

                            <span class="shrink-0 text-ink/30" aria-hidden="true">&rsaquo;</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-kartu>

        <p class="px-1 text-xs leading-relaxed text-ink/45">
            Laporan penjualan memakai <span class="font-semibold">tanggal rekonsiliasi</span> (saat penjualan tercatat),
            sedangkan laporan kas memakai tanggal uang benar-benar masuk atau keluar.
        </p>
    </div>

</x-app-layout>
