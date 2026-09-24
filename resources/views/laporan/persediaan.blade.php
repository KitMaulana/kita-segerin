<x-app-layout title="Laporan Persediaan" subtitle="Stok gudang, stok di toko, dan nilainya">

    <div class="space-y-5">

        @include('laporan.partials.saringan')

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([
                ['Stok gudang', angka($persediaan['total_gudang']).' pcs', 'text-ink'],
                ['Dititipkan di toko', angka($persediaan['total_di_toko']).' pcs', 'text-berry'],
                ['Nilai persediaan', rupiah($persediaan['nilai_total']), 'text-mint'],
                ['Produk menipis', angka($persediaan['jumlah_menipis']), $persediaan['jumlah_menipis'] > 0 ? 'text-mango' : 'text-ink/50'],
            ] as [$label, $nilai, $warna])
                <div class="rounded-2xl border border-berry/10 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold leading-snug text-ink/55">{{ $label }}</p>
                    <p class="mt-1 text-base font-extrabold tabular-nums {{ $warna }} sm:text-lg">{{ $nilai }}</p>
                </div>
            @endforeach
        </div>

        <x-kartu padat>
            @if ($persediaan['baris']->isEmpty())
                <x-kosong ikon="stok" judul="Belum ada produk">
                    Tambahkan produk dan catat pembelian untuk mulai mengisi persediaan.
                </x-kosong>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-berry/10 text-left text-xs uppercase tracking-wide text-ink/50">
                            <tr>
                                <th class="px-4 py-3 font-bold sm:px-5">Produk</th>
                                <th class="px-3 py-3 text-right font-bold">Gudang</th>
                                <th class="px-3 py-3 text-right font-bold">Di toko</th>
                                <th class="px-3 py-3 text-right font-bold">Total</th>
                                <th class="px-3 py-3 text-right font-bold">Harga modal</th>
                                <th class="px-3 py-3 text-right font-bold">Nilai gudang</th>
                                <th class="px-4 py-3 text-right font-bold sm:px-5">Nilai total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-berry/5">
                            @foreach ($persediaan['baris'] as $b)
                                <tr class="hover:bg-frost/60 {{ $b['menipis'] ? 'bg-mango/5' : '' }}">
                                    <td class="px-4 py-3 sm:px-5">
                                        <span class="font-semibold">{{ $b['produk']->name }}</span>
                                        <span class="block text-xs text-ink/45">
                                            {{ $b['produk']->code }}
                                            @unless ($b['produk']->is_active) &middot; nonaktif @endunless
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-right tabular-nums {{ $b['menipis'] ? 'font-bold text-mango' : '' }}">
                                        {{ angka($b['stok_gudang']) }}
                                    </td>
                                    <td class="px-3 py-3 text-right tabular-nums">{{ angka($b['stok_di_toko']) }}</td>
                                    <td class="px-3 py-3 text-right font-semibold tabular-nums">{{ angka($b['stok_total']) }}</td>
                                    <td class="px-3 py-3 text-right tabular-nums text-ink/60">{{ rupiah($b['produk']->cost_price) }}</td>
                                    <td class="px-3 py-3 text-right tabular-nums">{{ rupiah($b['nilai_gudang']) }}</td>
                                    <td class="px-4 py-3 text-right font-bold tabular-nums text-berry sm:px-5">{{ rupiah($b['nilai_total']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t border-berry/10 bg-frost/60 font-bold">
                            <tr>
                                <td class="px-4 py-3 sm:px-5">Total</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ angka($persediaan['total_gudang']) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ angka($persediaan['total_di_toko']) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">
                                    {{ angka($persediaan['total_gudang'] + $persediaan['total_di_toko']) }}
                                </td>
                                <td></td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ rupiah($persediaan['nilai_gudang']) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-berry sm:px-5">{{ rupiah($persediaan['nilai_total']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </x-kartu>

        <x-tautan-tombol gaya="kedua" :href="route('laporan.index')">Kembali ke daftar laporan</x-tautan-tombol>
    </div>

</x-app-layout>
