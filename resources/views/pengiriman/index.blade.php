<x-app-layout title="Pengiriman" subtitle="Titip jual es krim ke toko">

    <x-slot:actions>
        @can('input-transaksi')
            <x-tautan-tombol :href="route('pengiriman.create')" class="hidden lg:inline-flex">Catat pengiriman</x-tautan-tombol>
        @endcan
    </x-slot:actions>

    <div class="space-y-4">

        <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <div>
                <x-input-label for="cari" value="Nomor" class="text-xs" />
                <x-text-input id="cari" name="cari" type="search" class="mt-1" :value="request('cari')" placeholder="KRM/..." />
            </div>
            <div>
                <x-input-label for="toko" value="Toko" class="text-xs" />
                <x-select id="toko" name="toko" class="mt-1">
                    <option value="">Semua toko</option>
                    @foreach ($toko as $t)
                        <option value="{{ $t->id }}" @selected(request('toko') == $t->id)>{{ $t->name }}</option>
                    @endforeach
                </x-select>
            </div>
            <div>
                <x-input-label for="status" value="Status" class="text-xs" />
                <x-select id="status" name="status" class="mt-1">
                    <option value="">Semua status</option>
                    @foreach (App\Models\Consignment::STATUS as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(request('status') === $nilai)>{{ $label }}</option>
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
                <x-tautan-tombol gaya="kedua" :href="route('pengiriman.index')">Reset</x-tautan-tombol>
            </div>
        </form>

        @can('input-transaksi')
            <x-tautan-tombol :href="route('pengiriman.create')" class="w-full lg:hidden">Catat pengiriman</x-tautan-tombol>
        @endcan

        <x-kartu padat>
            @if ($daftar->isEmpty())
                <x-kosong ikon="pengiriman" judul="Belum ada pengiriman"
                          :aksi-teks="auth()->user()->bisaInput() ? 'Catat pengiriman' : null"
                          :aksi-url="auth()->user()->bisaInput() ? route('pengiriman.create') : null">
                    Catat pengiriman pertama untuk mulai menghitung setoran.
                </x-kosong>
            @else
                {{-- Kartu untuk HP --}}
                <ul class="divide-y divide-berry/5 lg:hidden">
                    @foreach ($daftar as $kirim)
                        <li>
                            <a href="{{ route('pengiriman.show', $kirim) }}" class="block px-4 py-3.5 transition hover:bg-frost">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold tabular-nums">{{ $kirim->number }}</p>
                                        <p class="truncate text-sm text-ink/60">{{ $kirim->store->name }}</p>
                                    </div>
                                    <x-lencana :warna="$kirim->warnaStatus()">{{ $kirim->namaStatus() }}</x-lencana>
                                </div>

                                <p class="mt-1.5 text-xs text-ink/50">
                                    Kirim {{ tanggal_singkat($kirim->sent_date) }}
                                    &middot; {{ $kirim->items_count }} produk
                                    @if ($kirim->settled_date)
                                        &middot; rekonsiliasi {{ tanggal_singkat($kirim->settled_date) }}
                                    @elseif ($kirim->status === 'sent' && $kirim->umurHari() > 7)
                                        &middot; <span class="font-bold text-mango">{{ $kirim->umurHari() }} hari belum direkonsiliasi</span>
                                    @endif
                                </p>
                            </a>
                        </li>
                    @endforeach
                </ul>

                {{-- Tabel untuk layar besar --}}
                <div class="hidden overflow-x-auto lg:block">
                    <table class="w-full text-sm">
                        <thead class="border-b border-berry/10 text-left text-xs uppercase tracking-wide text-ink/50">
                            <tr>
                                <th class="px-5 py-3 font-bold">Nomor</th>
                                <th class="px-3 py-3 font-bold">Toko</th>
                                <th class="px-3 py-3 font-bold">Tanggal kirim</th>
                                <th class="px-3 py-3 text-right font-bold">Produk</th>
                                <th class="px-3 py-3 font-bold">Rekonsiliasi</th>
                                <th class="px-5 py-3 text-right font-bold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-berry/5">
                            @foreach ($daftar as $kirim)
                                <tr class="hover:bg-frost/60">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('pengiriman.show', $kirim) }}" class="font-semibold tabular-nums hover:text-berry">
                                            {{ $kirim->number }}
                                        </a>
                                    </td>
                                    <td class="px-3 py-3">{{ $kirim->store->name }}</td>
                                    <td class="px-3 py-3 text-ink/70">{{ tanggal_indo($kirim->sent_date) }}</td>
                                    <td class="px-3 py-3 text-right tabular-nums">{{ $kirim->items_count }}</td>
                                    <td class="px-3 py-3 text-ink/70">
                                        @if ($kirim->settled_date)
                                            {{ tanggal_indo($kirim->settled_date) }}
                                        @elseif ($kirim->status === 'sent' && $kirim->umurHari() > 7)
                                            <span class="font-bold text-mango">{{ $kirim->umurHari() }} hari menunggu</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <x-lencana :warna="$kirim->warnaStatus()">{{ $kirim->namaStatus() }}</x-lencana>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-kartu>

        {{ $daftar->links() }}
    </div>

</x-app-layout>
