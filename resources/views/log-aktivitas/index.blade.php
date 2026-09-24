<x-app-layout title="Log Aktivitas" subtitle="Riwayat siapa mengubah apa dan kapan">

    <div class="space-y-4">

        <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <x-input-label for="pengguna" value="Pengguna" class="text-xs" />
                <x-select id="pengguna" name="pengguna" class="mt-1">
                    <option value="">Semua pengguna</option>
                    @foreach ($pengguna as $p)
                        <option value="{{ $p->id }}" @selected(request('pengguna') == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </x-select>
            </div>

            <div>
                <x-input-label for="aksi" value="Aksi" class="text-xs" />
                <x-select id="aksi" name="aksi" class="mt-1">
                    <option value="">Semua aksi</option>
                    @foreach ($daftarAksi as $a)
                        <option value="{{ $a }}" @selected(request('aksi') === $a)>{{ ucfirst($a) }}</option>
                    @endforeach
                </x-select>
            </div>

            <div>
                <x-input-label for="dari" value="Dari tanggal" class="text-xs" />
                <x-text-input id="dari" name="dari" type="date" class="mt-1" :value="request('dari')" />
            </div>

            <div>
                <x-input-label for="sampai" value="Sampai tanggal" class="text-xs" />
                <x-text-input id="sampai" name="sampai" type="date" class="mt-1" :value="request('sampai')" />
            </div>

            <div class="flex items-end gap-2">
                <x-primary-button class="flex-1">Saring</x-primary-button>
                <x-tautan-tombol gaya="kedua" :href="route('log-aktivitas.index')">Reset</x-tautan-tombol>
            </div>
        </form>

        <x-kartu padat>
            @if ($daftar->isEmpty())
                <x-kosong ikon="log" judul="Belum ada aktivitas tercatat">
                    Setiap penambahan, perubahan, dan penghapusan data penting akan muncul di sini.
                </x-kosong>
            @else
                <ul class="divide-y divide-berry/5">
                    @foreach ($daftar as $baris)
                        <li class="flex gap-3 px-4 py-3.5 sm:px-5">
                            <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-frost text-berry">
                                <x-icon name="log" class="h-4.5 w-4.5" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm leading-snug">{{ $baris->description }}</p>

                                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-ink/50">
                                    <span class="font-semibold text-ink/70">{{ $baris->user?->name ?? 'Sistem' }}</span>
                                    <span>{{ tanggal_indo($baris->created_at, true) }}</span>
                                    @if ($baris->subject_type)
                                        <x-lencana warna="ink">{{ $baris->namaSubjek() }}</x-lencana>
                                    @endif
                                    @if ($baris->ip_address)
                                        <span class="tabular-nums">{{ $baris->ip_address }}</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-kartu>

        {{ $daftar->links() }}
    </div>

</x-app-layout>
