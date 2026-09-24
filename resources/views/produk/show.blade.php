<x-app-layout :title="$produk->name" :subtitle="$produk->code.' · '.$produk->namaVarian()">

    <x-slot:actions>
        @can('input-transaksi')
            <x-tautan-tombol gaya="kedua" :href="route('produk.edit', $produk)">Ubah</x-tautan-tombol>
        @endcan
    </x-slot:actions>

    <div class="mx-auto max-w-3xl space-y-5">

        @unless ($produk->is_active)
            <div class="rounded-xl border border-strawberry/25 bg-strawberry/5 px-4 py-3 text-sm font-semibold text-strawberry">
                Produk ini nonaktif, jadi tidak muncul saat membuat pembelian atau pengiriman.
            </div>
        @endunless

        {{-- Ringkasan per pcs --}}
        <x-kartu judul="Hitungan per pcs" keterangan="Memakai harga default produk ini">
            <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ([
                    ['Harga modal', rupiah($produk->cost_price), 'text-ink'],
                    ['Harga jual', rupiah($produk->default_selling_price), 'text-ink'],
                    ['Fee toko', rupiah($ringkasan['fee_per_pcs']), 'text-mango'],
                    ['Setoran', rupiah($ringkasan['setoran_per_pcs']), 'text-berry'],
                ] as [$label, $nilai, $warna])
                    <div class="rounded-xl bg-frost px-3 py-2.5">
                        <dt class="text-[11px] text-ink/55">{{ $label }}</dt>
                        <dd class="mt-0.5 text-base font-extrabold tabular-nums {{ $warna }}">{{ $nilai }}</dd>
                    </div>
                @endforeach
            </dl>

            <div class="mt-3 flex flex-wrap items-center gap-3 rounded-xl bg-mint/5 px-4 py-3">
                <div>
                    <p class="text-[11px] text-ink/55">Laba per pcs</p>
                    <p class="text-xl font-extrabold tabular-nums {{ $ringkasan['laba_per_pcs'] >= 0 ? 'text-mint' : 'text-strawberry' }}">
                        {{ rupiah($ringkasan['laba_per_pcs']) }}
                    </p>
                </div>
                <div class="ml-auto text-right">
                    <p class="text-[11px] text-ink/55">Margin</p>
                    <p class="text-xl font-extrabold tabular-nums {{ $ringkasan['laba_per_pcs'] >= 0 ? 'text-mint' : 'text-strawberry' }}">
                        {{ persen($ringkasan['margin']) }}
                    </p>
                </div>
            </div>

            <p class="mt-3 text-xs text-ink/50">
                Jenis fee: <span class="font-semibold">{{ $produk->default_fee_type === 'percent' ? $produk->default_fee_value.'% dari harga jual' : rupiah($produk->default_fee_value).' per pcs' }}</span>
            </p>
        </x-kartu>

        {{-- Stok --}}
        <x-kartu judul="Stok">
            <x-slot:aksi>
                <x-tautan-tombol gaya="kedua" :href="route('stok.show', $produk)" class="!min-h-[36px] text-xs">
                    Riwayat mutasi
                </x-tautan-tombol>
            </x-slot:aksi>

            <dl class="grid grid-cols-3 gap-3">
                @php $menipis = $produk->min_stock > 0 && $stokGudang <= $produk->min_stock; @endphp

                <div class="rounded-xl bg-frost px-3 py-2.5">
                    <dt class="text-[11px] text-ink/55">Di gudang</dt>
                    <dd class="mt-0.5 text-lg font-extrabold tabular-nums {{ $menipis ? 'text-mango' : '' }}">
                        {{ angka($stokGudang) }}
                    </dd>
                </div>
                <div class="rounded-xl bg-frost px-3 py-2.5">
                    <dt class="text-[11px] text-ink/55">Dititipkan</dt>
                    <dd class="mt-0.5 text-lg font-extrabold tabular-nums">{{ angka($stokDiToko) }}</dd>
                </div>
                <div class="rounded-xl bg-frost px-3 py-2.5">
                    <dt class="text-[11px] text-ink/55">Nilai persediaan</dt>
                    <dd class="mt-0.5 text-lg font-extrabold tabular-nums text-berry">
                        {{ rupiah(($stokGudang + $stokDiToko) * $produk->cost_price) }}
                    </dd>
                </div>
            </dl>

            @if ($menipis)
                <p class="mt-3 rounded-xl bg-mango/10 px-4 py-2.5 text-sm font-semibold text-mango">
                    Stok gudang sudah di bawah atau sama dengan stok minimum ({{ angka($produk->min_stock) }} {{ $produk->unit }}). Saatnya kulakan lagi.
                </p>
            @endif
        </x-kartu>

        {{-- Keterangan --}}
        <x-kartu judul="Keterangan">
            <dl class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    'Pemasok' => $produk->supplier?->name,
                    'Satuan' => $produk->unit,
                    'Stok minimum' => angka($produk->min_stock).' '.$produk->unit,
                    'Status' => $produk->is_active ? 'Aktif' : 'Nonaktif',
                ] as $label => $nilai)
                    <div>
                        <dt class="text-xs font-semibold text-ink/50">{{ $label }}</dt>
                        <dd class="mt-0.5 text-sm font-semibold">{{ $nilai ?: '—' }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($produk->photo_path)
                <img src="{{ Storage::url($produk->photo_path) }}" alt="Foto {{ $produk->name }}"
                     class="mt-4 h-40 w-40 rounded-xl border border-berry/10 object-cover">
            @endif
        </x-kartu>

        {{-- Riwayat harga modal --}}
        <x-kartu judul="Riwayat harga modal" :keterangan="$produk->costHistories->count().' perubahan'" padat>
            @if ($produk->costHistories->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-ink/55">Belum ada perubahan harga modal.</p>
            @else
                <ul class="divide-y divide-berry/5">
                    @foreach ($produk->costHistories as $riwayat)
                        <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold tabular-nums">
                                    {{ rupiah($riwayat->old_price) }}
                                    <span class="mx-1 text-ink/35">&rarr;</span>
                                    {{ rupiah($riwayat->new_price) }}
                                </p>
                                <p class="text-xs text-ink/50">
                                    {{ tanggal_indo($riwayat->created_at, true) }}
                                    &middot; {{ $riwayat->sumberTeks() }}
                                    @if ($riwayat->user) &middot; {{ $riwayat->user->name }} @endif
                                </p>
                            </div>

                            <x-lencana :warna="$riwayat->selisih() > 0 ? 'strawberry' : ($riwayat->selisih() < 0 ? 'mint' : 'ink')">
                                {{ $riwayat->selisih() > 0 ? '+' : '' }}{{ rupiah($riwayat->selisih()) }}
                            </x-lencana>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-kartu>

        @can('hapus-transaksi')
            @unless ($produk->punyaTransaksi())
                <form method="POST" action="{{ route('produk.destroy', $produk) }}"
                      onsubmit="return confirm('Hapus produk {{ $produk->name }}?')">
                    @csrf
                    @method('delete')
                    <x-danger-button>Hapus produk</x-danger-button>
                </form>
            @else
                <p class="px-1 text-xs text-ink/45">
                    Produk ini sudah punya transaksi, jadi tidak bisa dihapus. Nonaktifkan saja lewat tombol Ubah.
                </p>
            @endunless
        @endcan
    </div>

</x-app-layout>
