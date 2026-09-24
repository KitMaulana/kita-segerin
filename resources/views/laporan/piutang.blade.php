<x-app-layout title="Piutang &amp; Umur Piutang" subtitle="Seluruh tagihan yang belum lunas">

    <div class="space-y-5">

        @include('laporan.partials.saringan')

        <div class="rounded-2xl border border-berry/10 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold text-ink/55">Total piutang</p>
            <p class="mt-1 text-3xl font-extrabold tabular-nums {{ $piutang['total'] > 0 ? 'text-mango' : 'text-mint' }}">
                {{ rupiah($piutang['total']) }}
            </p>
            <p class="mt-1 text-xs text-ink/50">{{ count($piutang['tagihan']) }} tagihan belum lunas</p>
        </div>

        {{-- Umur piutang --}}
        <x-kartu judul="Umur piutang" keterangan="Dihitung dari hari lewat jatuh tempo">
            <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($piutang['umur'] as $rentang => $data)
                    @php
                        $warna = match ($rentang) {
                            '0-7' => 'text-mint',
                            '8-14' => 'text-mango',
                            '15-30' => 'text-strawberry',
                            default => 'text-strawberry',
                        };
                    @endphp
                    <div class="rounded-xl bg-frost px-3 py-3">
                        <dt class="text-[11px] font-semibold text-ink/55">{{ $rentang }} hari</dt>
                        <dd class="mt-1 text-base font-extrabold tabular-nums {{ $warna }}">{{ rupiah($data['nilai']) }}</dd>
                        <dd class="text-[11px] text-ink/45">{{ $data['jumlah'] }} tagihan</dd>
                    </div>
                @endforeach
            </dl>
        </x-kartu>

        {{-- Per toko --}}
        @if ($piutang['per_toko']->isNotEmpty())
            <x-kartu judul="Piutang per toko" padat>
                <ul class="divide-y divide-berry/5">
                    @foreach ($piutang['per_toko'] as $b)
                        <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">{{ $b['toko']->name }}</p>
                                <p class="text-xs text-ink/50">{{ $b['jumlah'] }} tagihan</p>
                            </div>
                            <span class="shrink-0 text-sm font-extrabold tabular-nums text-mango">{{ rupiah($b['nilai']) }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-kartu>
        @endif

        {{-- Daftar tagihan --}}
        <x-kartu judul="Rincian tagihan" padat>
            @if (count($piutang['tagihan']) === 0)
                <x-kosong ikon="tagihan" judul="Tidak ada piutang">
                    Semua tagihan sudah lunas. Kerja bagus!
                </x-kosong>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-berry/10 text-left text-xs uppercase tracking-wide text-ink/50">
                            <tr>
                                <th class="px-4 py-3 font-bold sm:px-5">Nomor</th>
                                <th class="px-3 py-3 font-bold">Toko</th>
                                <th class="px-3 py-3 font-bold">Jatuh tempo</th>
                                <th class="px-3 py-3 text-right font-bold">Tagihan</th>
                                <th class="px-3 py-3 text-right font-bold">Dibayar</th>
                                <th class="px-3 py-3 text-right font-bold">Sisa</th>
                                <th class="px-4 py-3 text-right font-bold sm:px-5">Umur</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-berry/5">
                            @foreach ($piutang['tagihan'] as $inv)
                                <tr class="hover:bg-frost/60 {{ $inv->lewatJatuhTempo() ? 'bg-strawberry/5' : '' }}">
                                    <td class="px-4 py-3 sm:px-5">
                                        <a href="{{ route('tagihan.show', $inv) }}" class="font-semibold tabular-nums hover:text-berry">
                                            {{ $inv->number }}
                                        </a>
                                    </td>
                                    <td class="px-3 py-3">{{ $inv->store->name }}</td>
                                    <td class="px-3 py-3 {{ $inv->lewatJatuhTempo() ? 'font-bold text-strawberry' : 'text-ink/70' }}">
                                        {{ tanggal_indo($inv->due_date) }}
                                    </td>
                                    <td class="px-3 py-3 text-right tabular-nums">{{ rupiah($inv->amount_due) }}</td>
                                    <td class="px-3 py-3 text-right tabular-nums text-mint">{{ rupiah($inv->amount_paid) }}</td>
                                    <td class="px-3 py-3 text-right font-bold tabular-nums text-mango">{{ rupiah($inv->sisaTagihan()) }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums sm:px-5">
                                        {{ max(0, $inv->umurPiutangHari()) }} hari
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t border-berry/10 bg-frost/60 font-bold">
                            <tr>
                                <td colspan="5" class="px-4 py-3 text-right sm:px-5">Total piutang</td>
                                <td class="px-3 py-3 text-right tabular-nums text-mango">{{ rupiah($piutang['total']) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </x-kartu>

        <x-tautan-tombol gaya="kedua" :href="route('laporan.index')">Kembali ke daftar laporan</x-tautan-tombol>
    </div>

</x-app-layout>
