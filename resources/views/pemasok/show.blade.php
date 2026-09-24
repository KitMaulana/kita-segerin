<x-app-layout :title="$pemasok->name" subtitle="Detail pemasok">

    <x-slot:actions>
        @can('input-transaksi')
            <x-tautan-tombol gaya="kedua" :href="route('pemasok.edit', $pemasok)">Ubah</x-tautan-tombol>
        @endcan
    </x-slot:actions>

    <div class="mx-auto max-w-3xl space-y-5">

        <x-kartu judul="Keterangan">
            <dl class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    'Nama kontak' => $pemasok->contact_person,
                    'Telepon' => $pemasok->phone,
                    'Alamat' => $pemasok->address,
                    'Status' => $pemasok->is_active ? 'Aktif' : 'Nonaktif',
                ] as $label => $nilai)
                    <div>
                        <dt class="text-xs font-semibold text-ink/50">{{ $label }}</dt>
                        <dd class="mt-0.5 text-sm font-semibold">{{ $nilai ?: '—' }}</dd>
                    </div>
                @endforeach

                @if ($pemasok->notes)
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold text-ink/50">Catatan</dt>
                        <dd class="mt-0.5 whitespace-pre-line text-sm leading-relaxed">{{ $pemasok->notes }}</dd>
                    </div>
                @endif
            </dl>
        </x-kartu>

        <x-kartu judul="Produk dari pemasok ini" :keterangan="$pemasok->products->count().' produk'" padat>
            @if ($pemasok->products->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-ink/55">Belum ada produk yang dikaitkan dengan pemasok ini.</p>
            @else
                <ul class="divide-y divide-berry/5">
                    @foreach ($pemasok->products as $produk)
                        <li>
                            <a href="{{ route('produk.show', $produk) }}"
                               class="flex items-center justify-between gap-3 px-4 py-3 transition hover:bg-frost sm:px-5">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold">{{ $produk->name }}</span>
                                    <span class="block text-xs text-ink/50">{{ $produk->code }} &middot; {{ $produk->namaVarian() }}</span>
                                </span>
                                <span class="shrink-0 text-right text-sm font-bold tabular-nums">
                                    {{ rupiah($produk->cost_price) }}
                                    <span class="block text-[11px] font-normal text-ink/45">harga modal</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-kartu>

        <x-kartu judul="Pembelian terakhir" padat>
            @if ($pembelian->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-ink/55">Belum ada pembelian dari pemasok ini.</p>
            @else
                <ul class="divide-y divide-berry/5">
                    @foreach ($pembelian as $beli)
                        <li>
                            <a href="{{ route('pembelian.show', $beli) }}"
                               class="flex items-center justify-between gap-3 px-4 py-3 transition hover:bg-frost sm:px-5">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold tabular-nums">{{ $beli->number }}</span>
                                    <span class="block text-xs text-ink/50">
                                        {{ tanggal_indo($beli->purchase_date) }} &middot; {{ $beli->items_count }} produk
                                    </span>
                                </span>
                                <span class="shrink-0 text-sm font-bold tabular-nums">{{ rupiah($beli->total) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-kartu>

        @can('hapus-transaksi')
            @unless ($pemasok->punyaTransaksi())
                <form method="POST" action="{{ route('pemasok.destroy', $pemasok) }}"
                      onsubmit="return confirm('Hapus pemasok {{ $pemasok->name }}?')">
                    @csrf
                    @method('delete')
                    <x-danger-button>Hapus pemasok</x-danger-button>
                </form>
            @else
                <p class="px-1 text-xs text-ink/45">
                    Pemasok ini sudah dipakai di produk atau pembelian, jadi tidak bisa dihapus. Nonaktifkan saja lewat tombol Ubah.
                </p>
            @endunless
        @endcan
    </div>

</x-app-layout>
