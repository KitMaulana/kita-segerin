<x-app-layout title="Stok" subtitle="Gudang, titipan di toko, dan nilai persediaan">

    <x-slot:actions>
        @can('input-transaksi')
            <x-tautan-tombol :href="route('stok.penyesuaian.create')" class="hidden lg:inline-flex">
                Penyesuaian stok
            </x-tautan-tombol>
        @endcan
    </x-slot:actions>

    <div class="space-y-4">

        {{-- Ringkasan --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([
                ['Stok gudang', angka($totalGudang).' pcs', 'text-ink', 'stok'],
                ['Dititipkan di toko', angka($totalDiToko).' pcs', 'text-berry', 'toko'],
                ['Nilai persediaan', rupiah($totalNilai), 'text-mint', 'kas'],
                ['Stok menipis', angka($jumlahMenipis).' produk', $jumlahMenipis > 0 ? 'text-mango' : 'text-ink/50', 'pemasok'],
            ] as [$label, $nilai, $warna, $ikon])
                <div class="rounded-2xl border border-berry/10 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-xs font-semibold leading-snug text-ink/55">{{ $label }}</p>
                        <x-icon :name="$ikon" class="h-5 w-5 shrink-0 text-berry/40" />
                    </div>
                    <p class="mt-2 text-lg font-extrabold tabular-nums {{ $warna }} sm:text-xl">{{ $nilai }}</p>
                </div>
            @endforeach
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="min-w-0 flex-1 sm:max-w-xs">
                <x-text-input name="cari" type="search" :value="request('cari')" placeholder="Cari produk" />
            </div>
            <div class="w-40">
                <x-select name="saring" aria-label="Saring stok">
                    <option value="">Semua produk</option>
                    <option value="menipis" @selected(request('saring') === 'menipis')>Hanya yang menipis</option>
                </x-select>
            </div>
            <label class="flex min-h-[44px] items-center gap-2 text-sm">
                <input type="checkbox" name="termasuk_nonaktif" value="1" @checked(request()->boolean('termasuk_nonaktif'))
                       class="h-5 w-5 rounded border-berry/30 text-berry focus:ring-berry/40">
                Termasuk nonaktif
            </label>
            <x-primary-button>Terapkan</x-primary-button>

            @can('input-transaksi')
                <x-tautan-tombol :href="route('stok.penyesuaian.create')" class="ml-auto lg:hidden">Sesuaikan</x-tautan-tombol>
            @endcan
        </form>

        <x-kartu padat>
            @if ($ringkasan->isEmpty())
                <x-kosong ikon="stok" judul="Belum ada data stok"
                          :aksi-teks="auth()->user()->bisaInput() ? 'Catat pembelian' : null"
                          :aksi-url="auth()->user()->bisaInput() ? route('pembelian.create') : null">
                    Stok bertambah otomatis begitu Anda mencatat pembelian dari pemasok.
                </x-kosong>
            @else
                {{-- Kartu untuk HP --}}
                <ul class="divide-y divide-berry/5 lg:hidden">
                    @foreach ($ringkasan as $baris)
                        <li>
                            <a href="{{ route('stok.show', $baris['produk']) }}" class="block px-4 py-3.5 transition hover:bg-frost">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate font-bold">{{ $baris['produk']->name }}</p>
                                        <p class="text-xs text-ink/50">{{ $baris['produk']->code }}</p>
                                    </div>
                                    @if ($baris['menipis'])
                                        <x-lencana warna="mango">Menipis</x-lencana>
                                    @endif
                                </div>

                                <dl class="mt-2.5 grid grid-cols-3 gap-2 text-center">
                                    <div class="rounded-lg bg-frost px-2 py-1.5">
                                        <dt class="text-[10px] text-ink/50">Gudang</dt>
                                        <dd class="text-sm font-bold tabular-nums {{ $baris['menipis'] ? 'text-mango' : '' }}">
                                            {{ angka($baris['stok_gudang']) }}
                                        </dd>
                                    </div>
                                    <div class="rounded-lg bg-frost px-2 py-1.5">
                                        <dt class="text-[10px] text-ink/50">Di toko</dt>
                                        <dd class="text-sm font-bold tabular-nums">{{ angka($baris['stok_di_toko']) }}</dd>
                                    </div>
                                    <div class="rounded-lg bg-frost px-2 py-1.5">
                                        <dt class="text-[10px] text-ink/50">Nilai</dt>
                                        <dd class="text-sm font-bold tabular-nums text-berry">{{ rupiah($baris['nilai_total']) }}</dd>
                                    </div>
                                </dl>
                            </a>
                        </li>
                    @endforeach
                </ul>

                {{-- Tabel untuk layar besar --}}
                <div class="hidden overflow-x-auto lg:block">
                    <table class="w-full text-sm">
                        <thead class="border-b border-berry/10 text-left text-xs uppercase tracking-wide text-ink/50">
                            <tr>
                                <th class="px-5 py-3 font-bold">Produk</th>
                                <th class="px-3 py-3 text-right font-bold">Gudang</th>
                                <th class="px-3 py-3 text-right font-bold">Di toko</th>
                                <th class="px-3 py-3 text-right font-bold">Total</th>
                                <th class="px-3 py-3 text-right font-bold">Stok min.</th>
                                <th class="px-5 py-3 text-right font-bold">Nilai persediaan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-berry/5">
                            @foreach ($ringkasan as $baris)
                                <tr class="hover:bg-frost/60 {{ $baris['menipis'] ? 'bg-mango/5' : '' }}">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('stok.show', $baris['produk']) }}" class="font-semibold hover:text-berry">
                                            {{ $baris['produk']->name }}
                                        </a>
                                        <span class="block text-xs text-ink/45">{{ $baris['produk']->code }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-right tabular-nums {{ $baris['menipis'] ? 'font-bold text-mango' : '' }}">
                                        {{ angka($baris['stok_gudang']) }}
                                    </td>
                                    <td class="px-3 py-3 text-right tabular-nums">{{ angka($baris['stok_di_toko']) }}</td>
                                    <td class="px-3 py-3 text-right font-semibold tabular-nums">{{ angka($baris['stok_total']) }}</td>
                                    <td class="px-3 py-3 text-right tabular-nums text-ink/50">{{ angka($baris['produk']->min_stock) }}</td>
                                    <td class="px-5 py-3 text-right font-bold tabular-nums text-berry">{{ rupiah($baris['nilai_total']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t border-berry/10 bg-frost/60">
                            <tr>
                                <td class="px-5 py-3 font-bold">Total</td>
                                <td class="px-3 py-3 text-right font-bold tabular-nums">{{ angka($totalGudang) }}</td>
                                <td class="px-3 py-3 text-right font-bold tabular-nums">{{ angka($totalDiToko) }}</td>
                                <td class="px-3 py-3 text-right font-bold tabular-nums">{{ angka($totalGudang + $totalDiToko) }}</td>
                                <td></td>
                                <td class="px-5 py-3 text-right font-extrabold tabular-nums text-berry">{{ rupiah($totalNilai) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </x-kartu>

        <p class="px-1 text-xs leading-relaxed text-ink/45">
            Barang yang sedang dititipkan di toko tetap dihitung sebagai persediaan milik {{ config('app.name') }}
            sampai direkonsiliasi.
        </p>
    </div>

</x-app-layout>
