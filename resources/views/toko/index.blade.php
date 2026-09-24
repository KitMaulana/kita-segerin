<x-app-layout title="Toko / Mitra" subtitle="Koperasi sekolah, kantin, dan toko tempat menitipkan es krim">

    <x-slot:actions>
        @can('input-transaksi')
            <x-tautan-tombol :href="route('toko.create')" class="hidden lg:inline-flex">Tambah toko</x-tautan-tombol>
        @endcan
    </x-slot:actions>

    <div class="space-y-4">

        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="min-w-0 flex-1 sm:max-w-xs">
                <x-text-input name="cari" type="search" :value="request('cari')" placeholder="Cari nama atau kode toko" />
            </div>
            <div class="w-44">
                <x-select name="jenis" aria-label="Saring jenis">
                    <option value="">Semua jenis</option>
                    @foreach (App\Models\Store::JENIS as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(request('jenis') === $nilai)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </div>
            <div class="w-32">
                <x-select name="status" aria-label="Saring status">
                    <option value="">Semua</option>
                    <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
                    <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
                </x-select>
            </div>
            <x-primary-button>Cari</x-primary-button>

            @can('input-transaksi')
                <x-tautan-tombol :href="route('toko.create')" class="ml-auto lg:hidden">Tambah</x-tautan-tombol>
            @endcan
        </form>

        <x-kartu padat>
            @if ($daftar->isEmpty())
                <x-kosong ikon="toko" judul="Belum ada toko"
                          :aksi-teks="auth()->user()->bisaInput() ? 'Tambah toko' : null"
                          :aksi-url="auth()->user()->bisaInput() ? route('toko.create') : null">
                    Catat koperasi sekolah, kantin, atau toko tempat Anda menitipkan es krim.
                </x-kosong>
            @else
                <ul class="divide-y divide-berry/5">
                    @foreach ($daftar as $toko)
                        <li>
                            <a href="{{ route('toko.show', $toko) }}"
                               class="flex items-center gap-3 px-4 py-3.5 transition hover:bg-frost sm:px-5">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-berry/10 text-berry">
                                    <x-icon name="toko" class="h-5 w-5" />
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-2">
                                        <span class="truncate font-bold">{{ $toko->name }}</span>
                                        @unless ($toko->is_active)
                                            <x-lencana warna="strawberry">Nonaktif</x-lencana>
                                        @endunless
                                    </span>
                                    <span class="mt-0.5 block truncate text-xs text-ink/55">
                                        {{ $toko->code }} &middot; {{ $toko->namaJenis() }}
                                        @if ($toko->contact_person) &middot; {{ $toko->contact_person }} @endif
                                    </span>
                                </span>

                                <span class="shrink-0 text-right">
                                    <span class="block text-sm font-bold tabular-nums">{{ $toko->consignments_count }}</span>
                                    <span class="block text-[11px] text-ink/45">pengiriman</span>
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
