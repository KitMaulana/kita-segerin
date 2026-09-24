<x-app-layout title="Tagihan" subtitle="Setoran yang ditagih ke toko">

    <x-slot:actions>
        @can('kelola-tagihan')
            <x-tautan-tombol :href="route('tagihan.create')" class="hidden lg:inline-flex">Buat tagihan</x-tautan-tombol>
        @endcan
    </x-slot:actions>

    <div class="space-y-4">

        <div class="grid grid-cols-3 gap-3">
            @foreach ([
                ['Total ditagih', rupiah($totalTagihan), 'text-ink'],
                ['Sudah dibayar', rupiah($totalDibayar), 'text-mint'],
                ['Lewat jatuh tempo', angka($jumlahJatuhTempo).' tagihan', $jumlahJatuhTempo > 0 ? 'text-strawberry' : 'text-ink/50'],
            ] as [$label, $nilai, $warna])
                <div class="rounded-2xl border border-berry/10 bg-white p-4 shadow-sm">
                    <p class="text-[11px] font-semibold leading-snug text-ink/55">{{ $label }}</p>
                    <p class="mt-1 text-base font-extrabold tabular-nums {{ $warna }} sm:text-lg">{{ $nilai }}</p>
                </div>
            @endforeach
        </div>

        <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <div>
                <x-input-label for="cari" value="Nomor" class="text-xs" />
                <x-text-input id="cari" name="cari" type="search" class="mt-1" :value="request('cari')" placeholder="INV/..." />
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
                    @foreach (App\Models\Invoice::STATUS as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(request('status') === $nilai)>{{ $label }}</option>
                    @endforeach
                    <option value="jatuh_tempo" @selected(request('status') === 'jatuh_tempo')>Lewat jatuh tempo</option>
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
                <x-tautan-tombol gaya="kedua" :href="route('tagihan.index')">Reset</x-tautan-tombol>
            </div>
        </form>

        @can('kelola-tagihan')
            <x-tautan-tombol :href="route('tagihan.create')" class="w-full lg:hidden">Buat tagihan</x-tautan-tombol>
        @endcan

        <x-kartu padat>
            @if ($daftar->isEmpty())
                <x-kosong ikon="tagihan" judul="Belum ada tagihan"
                          :aksi-teks="auth()->user()->bisaInput() ? 'Buat tagihan' : null"
                          :aksi-url="auth()->user()->bisaInput() ? route('tagihan.create') : null">
                    Tagihan dibuat dari pengiriman yang sudah direkonsiliasi.
                </x-kosong>
            @else
                {{-- Kartu untuk HP --}}
                <ul class="divide-y divide-berry/5 lg:hidden">
                    @foreach ($daftar as $inv)
                        <li>
                            <a href="{{ route('tagihan.show', $inv) }}" class="block px-4 py-3.5 transition hover:bg-frost">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold tabular-nums">{{ $inv->number }}</p>
                                        <p class="truncate text-sm text-ink/60">{{ $inv->store->name }}</p>
                                    </div>
                                    <x-lencana :warna="$inv->warnaStatus()">
                                        {{ $inv->lewatJatuhTempo() ? 'Lewat tempo' : $inv->namaStatus() }}
                                    </x-lencana>
                                </div>

                                <div class="mt-2 flex items-end justify-between gap-3">
                                    <p class="text-xs text-ink/50">
                                        {{ tanggal_singkat($inv->invoice_date) }}
                                        &middot; tempo {{ tanggal_singkat($inv->due_date) }}
                                    </p>
                                    <p class="text-right">
                                        <span class="block text-base font-extrabold tabular-nums text-berry">{{ rupiah($inv->amount_due) }}</span>
                                        @if ($inv->amount_paid > 0 && $inv->status !== 'paid')
                                            <span class="block text-[11px] tabular-nums text-ink/50">
                                                sisa {{ rupiah($inv->sisaTagihan()) }}
                                            </span>
                                        @endif
                                    </p>
                                </div>
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
                                <th class="px-3 py-3 font-bold">Tanggal</th>
                                <th class="px-3 py-3 font-bold">Jatuh tempo</th>
                                <th class="px-3 py-3 text-right font-bold">Setoran</th>
                                <th class="px-3 py-3 text-right font-bold">Dibayar</th>
                                <th class="px-5 py-3 text-right font-bold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-berry/5">
                            @foreach ($daftar as $inv)
                                <tr class="hover:bg-frost/60 {{ $inv->lewatJatuhTempo() ? 'bg-strawberry/5' : '' }}">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('tagihan.show', $inv) }}" class="font-semibold tabular-nums hover:text-berry">
                                            {{ $inv->number }}
                                        </a>
                                    </td>
                                    <td class="px-3 py-3">{{ $inv->store->name }}</td>
                                    <td class="px-3 py-3 text-ink/70">{{ tanggal_indo($inv->invoice_date) }}</td>
                                    <td class="px-3 py-3 {{ $inv->lewatJatuhTempo() ? 'font-bold text-strawberry' : 'text-ink/70' }}">
                                        {{ tanggal_indo($inv->due_date) }}
                                    </td>
                                    <td class="px-3 py-3 text-right font-bold tabular-nums text-berry">{{ rupiah($inv->amount_due) }}</td>
                                    <td class="px-3 py-3 text-right tabular-nums text-mint">{{ rupiah($inv->amount_paid) }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <x-lencana :warna="$inv->warnaStatus()">
                                            {{ $inv->lewatJatuhTempo() ? 'Lewat tempo' : $inv->namaStatus() }}
                                        </x-lencana>
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
