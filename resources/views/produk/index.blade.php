<x-app-layout title="Produk" subtitle="Daftar es krim beserta harga dan fee default">

    <x-slot:actions>
        @can('input-transaksi')
            <x-tautan-tombol :href="route('produk.create')" class="hidden lg:inline-flex">Tambah produk</x-tautan-tombol>
        @endcan
    </x-slot:actions>

    <div class="space-y-4">

        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="min-w-0 flex-1 sm:max-w-xs">
                <x-text-input name="cari" type="search" :value="request('cari')" placeholder="Cari nama atau kode" />
            </div>
            <div class="w-36">
                <x-select name="varian" aria-label="Saring varian">
                    <option value="">Semua varian</option>
                    @foreach (App\Models\Product::VARIAN as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(request('varian') === $nilai)>{{ $label }}</option>
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
                <x-tautan-tombol :href="route('produk.create')" class="ml-auto lg:hidden">Tambah</x-tautan-tombol>
            @endcan
        </form>

        <x-kartu padat>
            @if ($daftar->isEmpty())
                <x-kosong ikon="produk" judul="Belum ada produk"
                          :aksi-teks="auth()->user()->bisaInput() ? 'Tambah produk' : null"
                          :aksi-url="auth()->user()->bisaInput() ? route('produk.create') : null">
                    Catat es krim yang Anda jual beserta harga modal dan harga jualnya untuk mulai menghitung laba.
                </x-kosong>
            @else
                {{-- Kartu untuk HP --}}
                <ul class="divide-y divide-berry/5 lg:hidden">
                    @foreach ($daftar as $produk)
                        @php
                            $r = $hitung->ringkasanPerPcs($produk->cost_price, $produk->default_selling_price, $produk->default_fee_type, $produk->default_fee_value);
                            $stok = $stokGudang[$produk->id] ?? 0;
                        @endphp
                        <li>
                            <a href="{{ route('produk.show', $produk) }}" class="block px-4 py-3.5 transition hover:bg-frost">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate font-bold">{{ $produk->name }}</p>
                                        <p class="text-xs text-ink/50">{{ $produk->code }} &middot; {{ $produk->namaVarian() }}</p>
                                    </div>
                                    @unless ($produk->is_active)
                                        <x-lencana warna="strawberry">Nonaktif</x-lencana>
                                    @else
                                        <x-lencana :warna="$produk->min_stock > 0 && $stok <= $produk->min_stock ? 'mango' : 'ink'">
                                            {{ angka($stok) }} {{ $produk->unit }}
                                        </x-lencana>
                                    @endunless
                                </div>

                                <dl class="mt-2.5 grid grid-cols-4 gap-2 text-center">
                                    @foreach ([
                                        'Modal' => [rupiah($produk->cost_price), 'text-ink'],
                                        'Jual' => [rupiah($produk->default_selling_price), 'text-ink'],
                                        'Setoran' => [rupiah($r['setoran_per_pcs']), 'text-berry'],
                                        'Laba' => [rupiah($r['laba_per_pcs']), $r['laba_per_pcs'] >= 0 ? 'text-mint' : 'text-strawberry'],
                                    ] as $label => [$nilai, $warna])
                                        <div class="rounded-lg bg-frost px-1.5 py-1.5">
                                            <dt class="text-[10px] text-ink/50">{{ $label }}</dt>
                                            <dd class="text-xs font-bold tabular-nums {{ $warna }}">{{ $nilai }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </a>
                        </li>
                    @endforeach
                </ul>

                {{-- Tabel untuk layar besar --}}
                <div class="hidden overflow-x-auto lg:block">
                    <table class="w-full text-sm">
                        <thead class="border-b border-berry/10 text-left text-xs uppercase tracking-wide text-ink/50">
                            <tr>
                                <th class="px-5 py-3 font-bold">Produk</th>
                                <th class="px-3 py-3 font-bold">Varian</th>
                                <th class="px-3 py-3 text-right font-bold">Stok</th>
                                <th class="px-3 py-3 text-right font-bold">Modal</th>
                                <th class="px-3 py-3 text-right font-bold">Jual</th>
                                <th class="px-3 py-3 text-right font-bold">Fee</th>
                                <th class="px-3 py-3 text-right font-bold">Setoran</th>
                                <th class="px-5 py-3 text-right font-bold">Laba/pcs</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-berry/5">
                            @foreach ($daftar as $produk)
                                @php
                                    $r = $hitung->ringkasanPerPcs($produk->cost_price, $produk->default_selling_price, $produk->default_fee_type, $produk->default_fee_value);
                                    $stok = $stokGudang[$produk->id] ?? 0;
                                    $menipis = $produk->min_stock > 0 && $stok <= $produk->min_stock;
                                @endphp
                                <tr class="hover:bg-frost/60">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('produk.show', $produk) }}" class="font-semibold hover:text-berry">
                                            {{ $produk->name }}
                                        </a>
                                        <span class="block text-xs text-ink/45">{{ $produk->code }}</span>
                                        @unless ($produk->is_active)
                                            <x-lencana warna="strawberry" class="mt-1">Nonaktif</x-lencana>
                                        @endunless
                                    </td>
                                    <td class="px-3 py-3 text-ink/70">{{ $produk->namaVarian() }}</td>
                                    <td class="px-3 py-3 text-right tabular-nums {{ $menipis ? 'font-bold text-mango' : '' }}">
                                        {{ angka($stok) }}
                                    </td>
                                    <td class="px-3 py-3 text-right tabular-nums">{{ rupiah($produk->cost_price) }}</td>
                                    <td class="px-3 py-3 text-right tabular-nums">{{ rupiah($produk->default_selling_price) }}</td>
                                    <td class="px-3 py-3 text-right tabular-nums text-mango">
                                        {{ rupiah($r['fee_per_pcs']) }}
                                        @if ($produk->default_fee_type === 'percent')
                                            <span class="block text-[11px] text-ink/40">{{ $produk->default_fee_value }}%</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-right font-semibold tabular-nums text-berry">{{ rupiah($r['setoran_per_pcs']) }}</td>
                                    <td class="px-5 py-3 text-right font-bold tabular-nums {{ $r['laba_per_pcs'] >= 0 ? 'text-mint' : 'text-strawberry' }}">
                                        {{ rupiah($r['laba_per_pcs']) }}
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
