<x-app-layout title="Buku Kas" subtitle="Seluruh uang masuk dan keluar dengan saldo berjalan">

    <x-slot:actions>
        @can('input-transaksi')
            <x-tautan-tombol :href="route('kas.create')" class="hidden lg:inline-flex">Input manual</x-tautan-tombol>
        @endcan
    </x-slot:actions>

    <div class="space-y-4">

        {{-- Ringkasan --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([
                ['Saldo awal', rupiah($saldoAwal), 'text-ink/70'],
                ['Uang masuk', rupiah($totalMasuk), 'text-mint'],
                ['Uang keluar', rupiah($totalKeluar), 'text-strawberry'],
                ['Saldo akhir', rupiah($saldoAkhir), $saldoAkhir >= 0 ? 'text-berry' : 'text-strawberry'],
            ] as [$label, $nilai, $warna])
                <div class="rounded-2xl border border-berry/10 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold text-ink/55">{{ $label }}</p>
                    <p class="mt-1 text-base font-extrabold tabular-nums {{ $warna }} sm:text-lg">{{ $nilai }}</p>
                </div>
            @endforeach
        </div>

        <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <x-input-label for="dari" value="Dari" class="text-xs" />
                <x-text-input id="dari" name="dari" type="date" class="mt-1" :value="$dari->toDateString()" />
            </div>
            <div>
                <x-input-label for="sampai" value="Sampai" class="text-xs" />
                <x-text-input id="sampai" name="sampai" type="date" class="mt-1" :value="$sampai->toDateString()" />
            </div>
            <div>
                <x-input-label for="kategori" value="Kategori" class="text-xs" />
                <x-select id="kategori" name="kategori" class="mt-1">
                    <option value="">Semua kategori</option>
                    @foreach (App\Models\CashTransaction::KATEGORI as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(request('kategori') === $nilai)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </div>
            <div>
                <x-input-label for="arah" value="Arah" class="text-xs" />
                <x-select id="arah" name="arah" class="mt-1">
                    <option value="">Masuk &amp; keluar</option>
                    <option value="in" @selected(request('arah') === 'in')>Hanya masuk</option>
                    <option value="out" @selected(request('arah') === 'out')>Hanya keluar</option>
                </x-select>
            </div>
            <div class="flex items-end gap-2">
                <x-primary-button class="flex-1">Terapkan</x-primary-button>
                <x-tautan-tombol gaya="kedua" :href="route('kas.index')">Reset</x-tautan-tombol>
            </div>
        </form>

        @can('input-transaksi')
            <x-tautan-tombol :href="route('kas.create')" class="w-full lg:hidden">Input manual</x-tautan-tombol>
        @endcan

        <x-kartu padat>
            @if ($baris->isEmpty())
                <x-kosong ikon="kas" judul="Belum ada transaksi kas pada periode ini">
                    Pembayaran tagihan, pembelian, dan biaya otomatis muncul di sini.
                    Modal pemilik dan prive diinput manual.
                </x-kosong>
            @else
                {{-- Kartu untuk HP --}}
                <ul class="divide-y divide-berry/5 lg:hidden">
                    @foreach ($baris as $b)
                        @php $t = $b['transaksi']; @endphp
                        <li class="flex items-center gap-3 px-4 py-3">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl text-sm font-bold
                                         {{ $t->direction === 'in' ? 'bg-mint/10 text-mint' : 'bg-strawberry/10 text-strawberry' }}">
                                {{ $t->direction === 'in' ? '+' : '−' }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold">{{ $t->description }}</p>
                                <p class="text-xs text-ink/50">
                                    {{ tanggal_singkat($t->date) }} &middot; {{ $t->namaKategori() }}
                                </p>
                            </div>

                            <div class="shrink-0 text-right">
                                <p class="text-sm font-extrabold tabular-nums {{ $t->direction === 'in' ? 'text-mint' : 'text-strawberry' }}">
                                    {{ rupiah($t->amount) }}
                                </p>
                                <p class="text-[11px] tabular-nums text-ink/45">saldo {{ rupiah($b['saldo']) }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- Tabel untuk layar besar --}}
                <div class="hidden overflow-x-auto lg:block">
                    <table class="w-full text-sm">
                        <thead class="border-b border-berry/10 text-left text-xs uppercase tracking-wide text-ink/50">
                            <tr>
                                <th class="px-5 py-3 font-bold">Tanggal</th>
                                <th class="px-3 py-3 font-bold">Keterangan</th>
                                <th class="px-3 py-3 font-bold">Kategori</th>
                                <th class="px-3 py-3 text-right font-bold">Masuk</th>
                                <th class="px-3 py-3 text-right font-bold">Keluar</th>
                                <th class="px-3 py-3 text-right font-bold">Saldo</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-berry/5">
                            @foreach ($baris as $b)
                                @php $t = $b['transaksi']; @endphp
                                <tr class="hover:bg-frost/60">
                                    <td class="px-5 py-3 whitespace-nowrap text-ink/70">{{ tanggal_indo($t->date) }}</td>
                                    <td class="px-3 py-3">
                                        {{ $t->description }}
                                        @if ($t->user)
                                            <span class="block text-xs text-ink/45">{{ $t->user->name }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3"><x-lencana warna="ink">{{ $t->namaKategori() }}</x-lencana></td>
                                    <td class="px-3 py-3 text-right tabular-nums text-mint">
                                        {{ $t->direction === 'in' ? rupiah($t->amount) : '—' }}
                                    </td>
                                    <td class="px-3 py-3 text-right tabular-nums text-strawberry">
                                        {{ $t->direction === 'out' ? rupiah($t->amount) : '—' }}
                                    </td>
                                    <td class="px-3 py-3 text-right font-semibold tabular-nums">{{ rupiah($b['saldo']) }}</td>
                                    <td class="px-5 py-3 text-right">
                                        @can('hapus-transaksi')
                                            @unless ($t->otomatis())
                                                <form method="POST" action="{{ route('kas.destroy', $t) }}"
                                                      onsubmit="return confirm('Hapus transaksi kas ini?')">
                                                    @csrf
                                                    @method('delete')
                                                    <button type="submit"
                                                            class="rounded-lg border border-strawberry/25 px-2 py-1 text-xs font-bold text-strawberry">
                                                        Hapus
                                                    </button>
                                                </form>
                                            @endunless
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-kartu>

        <p class="px-1 text-xs leading-relaxed text-ink/45">
            Baris yang berasal dari pembayaran tagihan, pembelian, dan biaya dibuat otomatis dan hanya bisa
            dihapus dari menu asalnya supaya data tetap cocok.
        </p>
    </div>

</x-app-layout>
