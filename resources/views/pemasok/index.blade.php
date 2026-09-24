<x-app-layout title="Pemasok" subtitle="Perusahaan tempat es krim diambil">

    <x-slot:actions>
        @can('input-transaksi')
            <x-tautan-tombol :href="route('pemasok.create')" class="hidden lg:inline-flex">Tambah pemasok</x-tautan-tombol>
        @endcan
    </x-slot:actions>

    <div class="space-y-4">

        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="min-w-0 flex-1 sm:max-w-xs">
                <x-text-input name="cari" type="search" :value="request('cari')" placeholder="Cari nama pemasok" />
            </div>
            <div class="w-36">
                <x-select name="status" aria-label="Saring status">
                    <option value="">Semua status</option>
                    <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
                    <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
                </x-select>
            </div>
            <x-primary-button>Cari</x-primary-button>

            @can('input-transaksi')
                <x-tautan-tombol :href="route('pemasok.create')" class="ml-auto lg:hidden">Tambah</x-tautan-tombol>
            @endcan
        </form>

        <x-kartu padat>
            @if ($daftar->isEmpty())
                <x-kosong ikon="pemasok" judul="Belum ada pemasok"
                          :aksi-teks="auth()->user()->bisaInput() ? 'Tambah pemasok' : null"
                          :aksi-url="auth()->user()->bisaInput() ? route('pemasok.create') : null">
                    Catat pemasok es krim Anda supaya pembelian bisa dikelompokkan per pemasok.
                </x-kosong>
            @else
                <ul class="divide-y divide-berry/5">
                    @foreach ($daftar as $pemasok)
                        <li>
                            <a href="{{ route('pemasok.show', $pemasok) }}"
                               class="flex items-center gap-3 px-4 py-3.5 transition hover:bg-frost sm:px-5">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-berry/10 text-berry">
                                    <x-icon name="pemasok" class="h-5 w-5" />
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-2">
                                        <span class="truncate font-bold">{{ $pemasok->name }}</span>
                                        @unless ($pemasok->is_active)
                                            <x-lencana warna="strawberry">Nonaktif</x-lencana>
                                        @endunless
                                    </span>
                                    <span class="mt-0.5 block truncate text-sm text-ink/55">
                                        {{ $pemasok->contact_person ?: 'Tanpa kontak' }}
                                        @if ($pemasok->phone) &middot; {{ $pemasok->phone }} @endif
                                    </span>
                                </span>

                                <span class="shrink-0 text-right">
                                    <span class="block text-sm font-bold tabular-nums">{{ $pemasok->products_count }}</span>
                                    <span class="block text-[11px] text-ink/45">produk</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-kartu>

        {{ $daftar->links() }}
    </div>

</x-app-layout>
