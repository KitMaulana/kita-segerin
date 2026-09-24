<x-app-layout title="Biaya Operasional" subtitle="Bensin, dry ice, listrik freezer, dan pengeluaran lain">

    <x-slot:actions>
        @can('input-transaksi')
            <x-tautan-tombol :href="route('biaya.create')" class="hidden lg:inline-flex">Catat biaya</x-tautan-tombol>
        @endcan
    </x-slot:actions>

    <div class="space-y-4">

        <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <x-input-label for="cari" value="Keterangan" class="text-xs" />
                <x-text-input id="cari" name="cari" type="search" class="mt-1" :value="request('cari')" />
            </div>
            <div>
                <x-input-label for="kategori" value="Kategori" class="text-xs" />
                <x-select id="kategori" name="kategori" class="mt-1">
                    <option value="">Semua kategori</option>
                    @foreach ($kategori as $k)
                        <option value="{{ $k->id }}" @selected(request('kategori') == $k->id)>{{ $k->name }}</option>
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
                <x-tautan-tombol gaya="kedua" :href="route('biaya.index')">Reset</x-tautan-tombol>
            </div>
        </form>

        @can('input-transaksi')
            <x-tautan-tombol :href="route('biaya.create')" class="w-full lg:hidden">Catat biaya</x-tautan-tombol>
        @endcan

        <div class="rounded-2xl border border-berry/10 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold text-ink/55">Total biaya sesuai saringan</p>
            <p class="mt-1 text-2xl font-extrabold tabular-nums text-strawberry">{{ rupiah($total) }}</p>

            @if ($perKategori->isNotEmpty())
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach ($perKategori as $nama => $jumlah)
                        <li class="rounded-lg bg-frost px-2.5 py-1.5 text-xs">
                            <span class="text-ink/60">{{ $nama }}</span>
                            <span class="ml-1 font-bold tabular-nums">{{ rupiah($jumlah) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <x-kartu padat>
            @if ($daftar->isEmpty())
                <x-kosong ikon="biaya" judul="Belum ada biaya tercatat"
                          :aksi-teks="auth()->user()->bisaInput() ? 'Catat biaya' : null"
                          :aksi-url="auth()->user()->bisaInput() ? route('biaya.create') : null">
                    Catat bensin, dry ice, listrik freezer, dan pengeluaran lain supaya laba bersih terhitung benar.
                </x-kosong>
            @else
                <ul class="divide-y divide-berry/5">
                    @foreach ($daftar as $biaya)
                        <li class="flex items-center gap-3 px-4 py-3.5 sm:px-5">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-strawberry/10 text-strawberry">
                                <x-icon name="biaya" class="h-5 w-5" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold">{{ $biaya->description }}</p>
                                <p class="text-xs text-ink/50">
                                    {{ tanggal_indo($biaya->date) }}
                                    &middot; {{ $biaya->category->name }}
                                    @if ($biaya->user) &middot; {{ $biaya->user->name }} @endif
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <span class="text-sm font-extrabold tabular-nums text-strawberry">{{ rupiah($biaya->amount) }}</span>

                                @if ($biaya->proof_path)
                                    <a href="{{ Storage::url($biaya->proof_path) }}" target="_blank"
                                       class="rounded-lg border border-berry/20 px-2 py-1.5 text-xs font-bold text-berry">Bukti</a>
                                @endif

                                @can('input-transaksi')
                                    <a href="{{ route('biaya.edit', $biaya) }}"
                                       class="rounded-lg border border-berry/20 px-2 py-1.5 text-xs font-bold text-berry">Ubah</a>
                                @endcan

                                @can('hapus-transaksi')
                                    <form method="POST" action="{{ route('biaya.destroy', $biaya) }}"
                                          onsubmit="return confirm('Hapus biaya ini?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit"
                                                class="rounded-lg border border-strawberry/25 px-2 py-1.5 text-xs font-bold text-strawberry">
                                            Hapus
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-kartu>

        {{ $daftar->links() }}
    </div>

</x-app-layout>
