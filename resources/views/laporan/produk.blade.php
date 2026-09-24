<x-app-layout title="Rekap per Produk" :subtitle="tanggal_indo($dari).' – '.tanggal_indo($sampai)">

    <div class="space-y-5">

        @include('laporan.partials.saringan')

        <x-kartu padat>
            @if ($baris->isEmpty())
                <x-kosong ikon="produk" judul="Belum ada penjualan pada periode ini">
                    Angka muncul setelah ada pengiriman yang direkonsiliasi dalam rentang tanggal ini.
                </x-kosong>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-berry/10 text-left text-xs uppercase tracking-wide text-ink/50">
                            <tr>
                                <th class="px-4 py-3 font-bold sm:px-5">Produk</th>
                                <th class="px-2 py-3 text-right font-bold">Kirim</th>
                                <th class="px-2 py-3 text-right font-bold">Terjual</th>
                                <th class="px-2 py-3 text-right font-bold">Retur</th>
                                <th class="px-2 py-3 text-right font-bold">Rusak</th>
                                <th class="px-3 py-3 font-bold">Tingkat laku</th>
                                <th class="px-3 py-3 text-right font-bold">Setoran</th>
                                <th class="px-3 py-3 text-right font-bold">Laba</th>
                                <th class="px-4 py-3 text-right font-bold sm:px-5">Margin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-berry/5">
                            @foreach ($baris as $b)
                                <tr class="hover:bg-frost/60">
                                    <td class="px-4 py-3 sm:px-5">
                                        <span class="font-semibold">{{ $b['nama'] }}</span>
                                        <span class="block text-xs text-ink/45">{{ $b['kode'] }}</span>
                                    </td>
                                    <td class="px-2 py-3 text-right tabular-nums">{{ angka($b['qty_kirim']) }}</td>
                                    <td class="px-2 py-3 text-right font-semibold tabular-nums text-mint">{{ angka($b['qty_terjual']) }}</td>
                                    <td class="px-2 py-3 text-right tabular-nums">{{ angka($b['qty_retur']) }}</td>
                                    <td class="px-2 py-3 text-right tabular-nums {{ $b['qty_rusak'] > 0 ? 'text-strawberry' : '' }}">
                                        {{ angka($b['qty_rusak']) }}
                                    </td>
                                    <td class="px-3 py-3">
                                        {{-- Batang berujung bulat seperti es krim stik. --}}
                                        <div class="flex items-center gap-2">
                                            <div class="h-2.5 w-20 overflow-hidden rounded-full bg-frost">
                                                <div class="h-full rounded-full bg-berry" style="width: {{ min(100, $b['tingkat_laku']) }}%"></div>
                                            </div>
                                            <span class="text-xs font-bold tabular-nums text-berry">{{ persen($b['tingkat_laku']) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-right font-semibold tabular-nums text-berry">{{ rupiah($b['setoran']) }}</td>
                                    <td class="px-3 py-3 text-right font-bold tabular-nums {{ $b['laba_kotor'] >= 0 ? 'text-mint' : 'text-strawberry' }}">
                                        {{ rupiah($b['laba_kotor']) }}
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums sm:px-5">{{ persen($b['margin']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t border-berry/10 bg-frost/60 font-bold">
                            <tr>
                                <td class="px-4 py-3 sm:px-5">Total</td>
                                <td class="px-2 py-3 text-right tabular-nums">{{ angka($baris->sum('qty_kirim')) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums">{{ angka($baris->sum('qty_terjual')) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums">{{ angka($baris->sum('qty_retur')) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums">{{ angka($baris->sum('qty_rusak')) }}</td>
                                <td></td>
                                <td class="px-3 py-3 text-right tabular-nums text-berry">{{ rupiah($baris->sum('setoran')) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums text-mint">{{ rupiah($baris->sum('laba_kotor')) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </x-kartu>

        <x-tautan-tombol gaya="kedua" :href="route('laporan.index')">Kembali ke daftar laporan</x-tautan-tombol>
    </div>

</x-app-layout>
