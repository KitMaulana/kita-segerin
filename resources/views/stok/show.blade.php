<x-app-layout :title="'Mutasi Stok — '.$produk->name" :subtitle="$produk->code">

    <div class="mx-auto max-w-3xl space-y-5">

        <div class="grid grid-cols-3 gap-3">
            @foreach ([
                ['Stok gudang', angka($stokGudang).' '.$produk->unit, 'text-ink'],
                ['Dititipkan', angka($stokDiToko).' '.$produk->unit, 'text-berry'],
                ['Nilai', rupiah(($stokGudang + $stokDiToko) * $produk->cost_price), 'text-mint'],
            ] as [$label, $nilai, $warna])
                <div class="rounded-2xl border border-berry/10 bg-white p-4 shadow-sm">
                    <p class="text-[11px] font-semibold text-ink/55">{{ $label }}</p>
                    <p class="mt-1 text-base font-extrabold tabular-nums {{ $warna }}">{{ $nilai }}</p>
                </div>
            @endforeach
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="w-56">
                <x-input-label for="jenis" value="Jenis mutasi" class="text-xs" />
                <x-select id="jenis" name="jenis" class="mt-1">
                    <option value="">Semua jenis</option>
                    @foreach (App\Models\StockMovement::JENIS as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(request('jenis') === $nilai)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </div>
            <x-primary-button>Saring</x-primary-button>
            <x-tautan-tombol gaya="kedua" :href="route('produk.show', $produk)">Lihat produk</x-tautan-tombol>
        </form>

        <x-kartu padat>
            @if ($mutasi->isEmpty())
                <x-kosong ikon="stok" judul="Belum ada mutasi stok">
                    Mutasi muncul otomatis saat Anda mencatat pembelian, pengiriman, atau rekonsiliasi.
                </x-kosong>
            @else
                <ul class="divide-y divide-berry/5">
                    @foreach ($mutasi as $baris)
                        <li class="flex items-center gap-3 px-4 py-3 sm:px-5">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl
                                         {{ $baris->qty >= 0 ? 'bg-mint/10 text-mint' : 'bg-strawberry/10 text-strawberry' }}">
                                {{ $baris->qty >= 0 ? '+' : '−' }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold">{{ $baris->namaJenis() }}</p>
                                <p class="text-xs text-ink/50">
                                    {{ tanggal_indo($baris->date) }}
                                    @if ($baris->user) &middot; {{ $baris->user->name }} @endif
                                </p>
                                @if ($baris->notes)
                                    <p class="mt-0.5 text-xs italic text-ink/55">{{ $baris->notes }}</p>
                                @endif
                            </div>

                            <span class="shrink-0 text-right">
                                <span class="block text-sm font-extrabold tabular-nums
                                             {{ $baris->qty >= 0 ? 'text-mint' : 'text-strawberry' }}">
                                    {{ $baris->qty > 0 ? '+' : '' }}{{ angka($baris->qty) }}
                                </span>
                                @unless (in_array($baris->type, App\Models\StockMovement::MEMENGARUHI_GUDANG, true))
                                    <span class="block text-[10px] text-ink/40">tidak mengubah gudang</span>
                                @endunless
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-kartu>

        {{ $mutasi->links() }}
    </div>

</x-app-layout>
