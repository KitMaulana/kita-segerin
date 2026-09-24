<x-app-layout :title="'Pembelian '.$pembelian->number" :subtitle="tanggal_indo($pembelian->purchase_date)">

    <div class="mx-auto max-w-3xl space-y-5">

        <x-kartu judul="Keterangan">
            <dl class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    'Nomor' => $pembelian->number,
                    'Tanggal' => tanggal_indo($pembelian->purchase_date),
                    'Pemasok' => $pembelian->supplier?->name ?? 'Tanpa pemasok',
                    'Dicatat oleh' => $pembelian->user?->name ?? '—',
                ] as $label => $nilai)
                    <div>
                        <dt class="text-xs font-semibold text-ink/50">{{ $label }}</dt>
                        <dd class="mt-0.5 text-sm font-semibold">{{ $nilai }}</dd>
                    </div>
                @endforeach

                @if ($pembelian->notes)
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold text-ink/50">Catatan</dt>
                        <dd class="mt-0.5 whitespace-pre-line text-sm leading-relaxed">{{ $pembelian->notes }}</dd>
                    </div>
                @endif
            </dl>
        </x-kartu>

        <x-kartu judul="Rincian produk" padat>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-berry/10 text-left text-xs uppercase tracking-wide text-ink/50">
                        <tr>
                            <th class="px-4 py-3 font-bold sm:px-5">Produk</th>
                            <th class="px-3 py-3 text-right font-bold">Jumlah</th>
                            <th class="px-3 py-3 text-right font-bold">Harga satuan</th>
                            <th class="px-4 py-3 text-right font-bold sm:px-5">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-berry/5">
                        @foreach ($pembelian->items as $baris)
                            <tr>
                                <td class="px-4 py-3 sm:px-5">
                                    <a href="{{ route('produk.show', $baris->product_id) }}" class="font-semibold hover:text-berry">
                                        {{ $baris->product->name }}
                                    </a>
                                    <span class="block text-xs text-ink/45">{{ $baris->product->code }}</span>
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ angka($baris->qty) }} {{ $baris->product->unit }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ rupiah($baris->unit_cost) }}</td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums sm:px-5">{{ rupiah($baris->subtotal) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-berry/10 bg-frost/60">
                        <tr>
                            <td class="px-4 py-3 font-bold sm:px-5">Total</td>
                            <td class="px-3 py-3 text-right font-bold tabular-nums">{{ angka($pembelian->totalPcs()) }} pcs</td>
                            <td></td>
                            <td class="px-4 py-3 text-right text-base font-extrabold tabular-nums text-berry sm:px-5">
                                {{ rupiah($pembelian->total) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-kartu>

        <x-kartu judul="Dampak pembelian ini">
            <ul class="space-y-2 text-sm">
                <li class="flex items-start gap-2">
                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-mint"></span>
                    <span>Stok gudang bertambah <span class="font-bold tabular-nums">{{ angka($pembelian->totalPcs()) }} pcs</span>.</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-strawberry"></span>
                    <span>Buku kas mencatat uang keluar <span class="font-bold tabular-nums">{{ rupiah($pembelian->total) }}</span>.</span>
                </li>
                @if ($pembelian->update_cost_price)
                    <li class="flex items-start gap-2">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-berry"></span>
                        <span>Harga modal produk diperbarui sesuai harga pada pembelian ini.</span>
                    </li>
                @endif
            </ul>
        </x-kartu>

        <div class="flex flex-wrap gap-3">
            <x-tautan-tombol gaya="kedua" :href="route('pembelian.index')">Kembali ke daftar</x-tautan-tombol>

            @can('hapus-transaksi')
                <form method="POST" action="{{ route('pembelian.destroy', $pembelian) }}"
                      onsubmit="return confirm('Hapus pembelian {{ $pembelian->number }}? Mutasi stok dan catatan kasnya ikut dihapus.')">
                    @csrf
                    @method('delete')
                    <x-danger-button>Hapus pembelian</x-danger-button>
                </form>
            @endcan
        </div>
    </div>

</x-app-layout>
