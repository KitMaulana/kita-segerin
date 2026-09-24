<x-app-layout title="Pembelian" subtitle="Riwayat kulakan es krim dari pemasok">

    <x-slot:actions>
        @can('input-transaksi')
            <x-tautan-tombol :href="route('pembelian.create')" class="hidden lg:inline-flex">Catat pembelian</x-tautan-tombol>
        @endcan
    </x-slot:actions>

    <div class="space-y-4">

        <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <x-input-label for="cari" value="Nomor" class="text-xs" />
                <x-text-input id="cari" name="cari" type="search" class="mt-1" :value="request('cari')" placeholder="BLI/..." />
            </div>
            <div>
                <x-input-label for="pemasok" value="Pemasok" class="text-xs" />
                <x-select id="pemasok" name="pemasok" class="mt-1">
                    <option value="">Semua pemasok</option>
                    @foreach ($pemasok as $p)
                        <option value="{{ $p->id }}" @selected(request('pemasok') == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </x-select>
            </div>
            <div>
                <x-input-label for="dari" value="Dari" class="text-xs" />
                <x-text-input id="dari" name="dari" type="date" class="mt-1" :value="request('dari')" />
            </div>
            <div>
                <x-input-label for="sampai" value="Sampai" class="text-xs" />
                <x-text-input id="sampai" name="sampai" type="date" class="mt-1" :value="request('sampai')" />
            </div>
            <div class="flex items-end gap-2">
                <x-primary-button class="flex-1">Saring</x-primary-button>
                <x-tautan-tombol gaya="kedua" :href="route('pembelian.index')">Reset</x-tautan-tombol>
            </div>
        </form>

        @can('input-transaksi')
            <x-tautan-tombol :href="route('pembelian.create')" class="w-full lg:hidden">Catat pembelian</x-tautan-tombol>
        @endcan

        <x-kartu padat>
            @if ($daftar->isEmpty())
                <x-kosong ikon="pembelian" judul="Belum ada pembelian"
                          :aksi-teks="auth()->user()->bisaInput() ? 'Catat pembelian' : null"
                          :aksi-url="auth()->user()->bisaInput() ? route('pembelian.create') : null">
                    Catat kulakan pertama Anda untuk mulai mengisi stok gudang.
                </x-kosong>
            @else
                <ul class="divide-y divide-berry/5">
                    @foreach ($daftar as $beli)
                        <li>
                            <a href="{{ route('pembelian.show', $beli) }}"
                               class="flex items-center gap-3 px-4 py-3.5 transition hover:bg-frost sm:px-5">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-berry/10 text-berry">
                                    <x-icon name="pembelian" class="h-5 w-5" />
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-bold tabular-nums">{{ $beli->number }}</span>
                                    <span class="mt-0.5 block truncate text-xs text-ink/55">
                                        {{ tanggal_indo($beli->purchase_date) }}
                                        &middot; {{ $beli->supplier?->name ?? 'Tanpa pemasok' }}
                                        &middot; {{ angka($beli->items_sum_qty ?? 0) }} pcs
                                    </span>
                                </span>

                                <span class="shrink-0 text-right">
                                    <span class="block text-sm font-extrabold tabular-nums text-berry">{{ rupiah($beli->total) }}</span>
                                    @if ($beli->update_cost_price)
                                        <x-lencana warna="mint" class="mt-1">Harga modal ikut diperbarui</x-lencana>
                                    @endif
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="flex items-center justify-between border-t border-berry/10 bg-frost/60 px-5 py-3 text-sm">
                    <span class="font-bold">Total di halaman ini</span>
                    <span class="font-extrabold tabular-nums text-berry">{{ rupiah($totalPeriode) }}</span>
                </div>
            @endif
        </x-kartu>

        {{ $daftar->links() }}
    </div>

</x-app-layout>
