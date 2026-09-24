<x-app-layout title="Laporan Laba Rugi" :subtitle="tanggal_indo($dari).' – '.tanggal_indo($sampai)">

    <div class="mx-auto max-w-3xl space-y-5">

        @include('laporan.partials.saringan')

        {{-- Laba bersih --}}
        <div class="rounded-2xl border border-berry/10 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold text-ink/55">Laba bersih periode ini</p>
            <p class="mt-1 text-3xl font-extrabold tabular-nums {{ $lr['laba_bersih'] >= 0 ? 'text-mint' : 'text-strawberry' }}">
                {{ rupiah($lr['laba_bersih']) }}
            </p>
            <p class="mt-1 text-xs capitalize text-ink/50">{{ terbilang($lr['laba_bersih']) }}</p>
        </div>

        <x-kartu judul="Perhitungan" padat>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-berry/5">
                    @foreach ([
                        ['Penjualan kotor', $lr['penjualan_kotor'], 'ink', false],
                        ['Fee toko', -$lr['fee_toko'], 'mango', false],
                        ['Setoran (ditagih ke toko)', $lr['setoran'], 'berry', true],
                        ['HPP — harga modal barang terjual', -$lr['hpp'], 'ink', false],
                        ['Laba kotor', $lr['laba_kotor'], 'mint', true],
                        ['Kerugian barang rusak', -$lr['kerugian_rusak'], 'strawberry', false],
                    ] as [$label, $nilai, $warna, $tebal])
                        <tr class="{{ $tebal ? 'bg-frost/60 font-bold' : '' }}">
                            <td class="px-4 py-3 sm:px-5">{{ $label }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-{{ $warna }} sm:px-5">
                                {{ $nilai < 0 ? '− '.rupiah(abs($nilai)) : rupiah($nilai) }}
                            </td>
                        </tr>
                    @endforeach

                    @foreach ($lr['biaya_per_kategori'] as $nama => $jumlah)
                        <tr>
                            <td class="px-4 py-2.5 pl-8 text-ink/70 sm:px-5 sm:pl-9">Biaya — {{ $nama }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums text-strawberry sm:px-5">− {{ rupiah($jumlah) }}</td>
                        </tr>
                    @endforeach

                    <tr>
                        <td class="px-4 py-3 sm:px-5">Total biaya operasional</td>
                        <td class="px-4 py-3 text-right tabular-nums text-strawberry sm:px-5">− {{ rupiah($lr['total_biaya']) }}</td>
                    </tr>

                    <tr class="bg-berry text-white">
                        <td class="px-4 py-3.5 font-extrabold sm:px-5">LABA BERSIH</td>
                        <td class="px-4 py-3.5 text-right text-base font-extrabold tabular-nums sm:px-5">
                            {{ rupiah($lr['laba_bersih']) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </x-kartu>

        <x-kartu judul="Jumlah barang">
            <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ([
                    ['Dikirim', angka($lr['qty_kirim']).' pcs', 'text-ink'],
                    ['Terjual', angka($lr['qty_terjual']).' pcs', 'text-mint'],
                    ['Retur', angka($lr['qty_retur']).' pcs', 'text-ink/70'],
                    ['Rusak', angka($lr['qty_rusak']).' pcs', 'text-strawberry'],
                ] as [$label, $nilai, $warna])
                    <div class="rounded-xl bg-frost px-3 py-2.5">
                        <dt class="text-[11px] text-ink/55">{{ $label }}</dt>
                        <dd class="mt-0.5 text-sm font-extrabold tabular-nums {{ $warna }}">{{ $nilai }}</dd>
                    </div>
                @endforeach
            </dl>

            <div class="mt-4 grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-frost px-3 py-2.5">
                    <p class="text-[11px] text-ink/55">Tingkat laku</p>
                    <p class="mt-0.5 text-lg font-extrabold tabular-nums text-berry">{{ persen($lr['tingkat_laku']) }}</p>
                </div>
                <div class="rounded-xl bg-frost px-3 py-2.5">
                    <p class="text-[11px] text-ink/55">Margin laba kotor</p>
                    <p class="mt-0.5 text-lg font-extrabold tabular-nums {{ $lr['laba_kotor'] >= 0 ? 'text-mint' : 'text-strawberry' }}">
                        {{ persen($lr['margin']) }}
                    </p>
                </div>
            </div>
        </x-kartu>

        <x-tautan-tombol gaya="kedua" :href="route('laporan.index')">Kembali ke daftar laporan</x-tautan-tombol>
    </div>

</x-app-layout>
