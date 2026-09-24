<x-app-layout :title="$toko->name" :subtitle="$toko->code.' · '.$toko->namaJenis()">

    <x-slot:actions>
        @can('input-transaksi')
            <x-tautan-tombol gaya="kedua" :href="route('toko.edit', $toko)">Ubah</x-tautan-tombol>
        @endcan
    </x-slot:actions>

    <div class="mx-auto max-w-3xl space-y-5">

        {{-- Tab --}}
        <nav class="flex gap-1 rounded-xl bg-white p-1 shadow-sm" aria-label="Tab toko">
            <span class="flex-1 rounded-lg bg-berry px-3 py-2 text-center text-sm font-bold text-white">Ringkasan</span>
            <a href="{{ route('toko.harga', $toko) }}"
               class="flex-1 rounded-lg px-3 py-2 text-center text-sm font-bold text-ink/60 transition hover:bg-frost">
                Harga &amp; Fee
            </a>
        </nav>

        @unless ($toko->is_active)
            <div class="rounded-xl border border-strawberry/25 bg-strawberry/5 px-4 py-3 text-sm font-semibold text-strawberry">
                Toko ini nonaktif, jadi tidak muncul saat membuat pengiriman baru.
            </div>
        @endunless

        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-2xl border border-berry/10 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold text-ink/55">Piutang belum dibayar</p>
                <p class="mt-1 text-xl font-extrabold tabular-nums {{ $piutang > 0 ? 'text-mango' : 'text-mint' }}">
                    {{ rupiah($piutang) }}
                </p>
                <p class="mt-0.5 text-[11px] text-ink/45">{{ $jumlahTagihanBelumLunas }} tagihan belum lunas</p>
            </div>

            <div class="rounded-2xl border border-berry/10 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold text-ink/55">Tempo bayar</p>
                <p class="mt-1 text-xl font-extrabold tabular-nums">{{ $toko->tempoBayar() }} hari</p>
                <p class="mt-0.5 text-[11px] text-ink/45">
                    {{ $toko->payment_term_days ? 'khusus toko ini' : 'mengikuti pengaturan usaha' }}
                </p>
            </div>
        </div>

        <x-kartu judul="Keterangan">
            <dl class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    'Nama kontak' => $toko->contact_person,
                    'Telepon' => $toko->phone,
                    'Alamat' => $toko->address,
                    'Status' => $toko->is_active ? 'Aktif' : 'Nonaktif',
                ] as $label => $nilai)
                    <div>
                        <dt class="text-xs font-semibold text-ink/50">{{ $label }}</dt>
                        <dd class="mt-0.5 text-sm font-semibold">{{ $nilai ?: '—' }}</dd>
                    </div>
                @endforeach

                @if ($toko->notes)
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold text-ink/50">Catatan</dt>
                        <dd class="mt-0.5 whitespace-pre-line text-sm leading-relaxed">{{ $toko->notes }}</dd>
                    </div>
                @endif
            </dl>
        </x-kartu>

        <x-kartu judul="Pengiriman terakhir" padat>
            @if ($pengiriman->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-ink/55">Belum ada pengiriman ke toko ini.</p>
            @else
                <ul class="divide-y divide-berry/5">
                    @foreach ($pengiriman as $kirim)
                        <li>
                            <a href="{{ route('pengiriman.show', $kirim) }}"
                               class="flex items-center justify-between gap-3 px-4 py-3 transition hover:bg-frost sm:px-5">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold tabular-nums">{{ $kirim->number }}</span>
                                    <span class="block text-xs text-ink/50">
                                        {{ tanggal_indo($kirim->sent_date) }} &middot; {{ $kirim->items_count }} produk
                                    </span>
                                </span>
                                <x-lencana :warna="$kirim->warnaStatus()">{{ $kirim->namaStatus() }}</x-lencana>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-kartu>

        @can('hapus-transaksi')
            @unless ($toko->punyaTransaksi())
                <form method="POST" action="{{ route('toko.destroy', $toko) }}"
                      onsubmit="return confirm('Hapus toko {{ $toko->name }}?')">
                    @csrf
                    @method('delete')
                    <x-danger-button>Hapus toko</x-danger-button>
                </form>
            @else
                <p class="px-1 text-xs text-ink/45">
                    Toko ini sudah punya transaksi, jadi tidak bisa dihapus. Nonaktifkan saja lewat tombol Ubah.
                </p>
            @endunless
        @endcan
    </div>

</x-app-layout>
