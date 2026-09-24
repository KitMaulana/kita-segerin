<x-app-layout title="Beranda" :subtitle="$bulan->locale('id')->translatedFormat('F Y')">

    <x-slot:actions>
        <a href="{{ route('beranda.cetak', ['bulan' => $bulanTerpilih]) }}" target="_blank"
           class="hidden min-h-[36px] items-center rounded-xl border border-berry/20 bg-white px-3 text-xs font-bold text-berry transition hover:bg-frost lg:inline-flex">
            Cetak infografis
        </a>
    </x-slot:actions>

    <div class="space-y-5">

        {{-- Pilih bulan --}}
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="w-full sm:max-w-xs">
                <x-input-label for="bulan" value="Bulan" class="text-xs" />
                <x-select id="bulan" name="bulan" class="mt-1" onchange="this.form.submit()">
                    @foreach ($daftarBulan as $nilai => $label)
                        <option value="{{ $nilai }}" @selected($bulanTerpilih === $nilai)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </div>
            <noscript><x-primary-button>Tampilkan</x-primary-button></noscript>
        </form>

        {{-- Kartu ringkasan --}}
        <section aria-labelledby="judul-ringkasan">
            <h2 id="judul-ringkasan" class="sr-only">Ringkasan bulan ini</h2>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
                @php
                    $kartu = [
                        ['Setoran bulan ini', rupiah($ringkas['setoran']), 'text-berry', $perubahan_setoran, 'tagihan'],
                        ['Laba bersih', rupiah($ringkas['laba_bersih']), $ringkas['laba_bersih'] >= 0 ? 'text-mint' : 'text-strawberry', $perubahan_laba, 'laporan'],
                        ['Piutang belum dibayar', rupiah($piutang), $piutang > 0 ? 'text-mango' : 'text-ink/50', null, 'kas'],
                        ['Nilai persediaan', rupiah($persediaan['nilai_total']), 'text-ink', null, 'stok'],
                        ['Terjual bulan ini', angka($ringkas['qty_terjual']).' pcs', 'text-ink', null, 'produk'],
                    ];
                @endphp

                @foreach ($kartu as [$label, $nilai, $warna, $perubahan, $ikon])
                    <div class="rounded-2xl border border-berry/10 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-xs font-semibold leading-snug text-ink/55">{{ $label }}</p>
                            <x-icon :name="$ikon" class="h-5 w-5 shrink-0 text-berry/40" />
                        </div>

                        <p class="mt-2 text-lg font-extrabold tabular-nums {{ $warna }} sm:text-xl">{{ $nilai }}</p>

                        @if ($perubahan !== null)
                            <p class="mt-1 text-[11px] font-bold {{ $perubahan >= 0 ? 'text-mint' : 'text-strawberry' }}">
                                {{ $perubahan >= 0 ? '▲' : '▼' }} {{ persen(abs($perubahan)) }}
                                <span class="font-normal text-ink/45">dari bulan lalu</span>
                            </p>
                        @elseif (in_array($label, ['Setoran bulan ini', 'Laba bersih'], true))
                            <p class="mt-1 text-[11px] text-ink/40">Belum ada pembanding bulan lalu</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Tombol cepat --}}
        @can('input-transaksi')
            <section aria-labelledby="judul-cepat">
                <h2 id="judul-cepat" class="mb-2 text-sm font-bold text-ink/70">Aksi cepat</h2>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    @foreach ([
                        ['Catat pengiriman', 'pengiriman.create', 'pengiriman'],
                        ['Buat tagihan', 'tagihan.create', 'tagihan'],
                        ['Catat biaya', 'biaya.create', 'biaya'],
                    ] as [$label, $rute, $ikon])
                        <a href="{{ route($rute) }}"
                           class="flex min-h-[56px] items-center gap-3 rounded-2xl border border-berry/10 bg-white px-4 text-sm font-bold text-ink shadow-sm transition
                                  hover:border-berry/30 hover:bg-berry/5
                                  focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-berry">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-berry/10 text-berry">
                                <x-icon :name="$ikon" class="h-5 w-5" />
                            </span>
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </section>
        @endcan

        {{-- Panel perhatian --}}
        @if ($perhatian['ada_perhatian'])
            <x-kartu judul="Perlu perhatian" keterangan="Hal-hal yang sebaiknya segera ditindaklanjuti">
                <div class="grid gap-4 lg:grid-cols-3">

                    {{-- Tagihan lewat jatuh tempo --}}
                    <div class="rounded-xl border border-strawberry/20 bg-strawberry/5 p-3">
                        <p class="text-xs font-bold text-strawberry">
                            Tagihan lewat jatuh tempo ({{ $perhatian['jatuh_tempo']->count() }})
                        </p>

                        @if ($perhatian['jatuh_tempo']->isEmpty())
                            <p class="mt-1.5 text-xs text-ink/55">Tidak ada. Bagus!</p>
                        @else
                            <p class="mt-0.5 text-lg font-extrabold tabular-nums text-strawberry">
                                {{ rupiah($perhatian['nilai_jatuh_tempo']) }}
                            </p>
                            <ul class="mt-2 space-y-1.5">
                                @foreach ($perhatian['jatuh_tempo']->take(4) as $inv)
                                    <li>
                                        <a href="{{ route('tagihan.show', $inv) }}"
                                           class="block truncate text-xs text-ink/70 hover:text-berry">
                                            {{ $inv->store->name }} —
                                            <span class="font-bold tabular-nums">{{ rupiah($inv->sisaTagihan()) }}</span>
                                            <span class="text-ink/45">({{ abs($inv->umurPiutangHari()) }} hari)</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    {{-- Stok menipis --}}
                    <div class="rounded-xl border border-mango/25 bg-mango/5 p-3">
                        <p class="text-xs font-bold text-mango">
                            Stok menipis ({{ $perhatian['stok_menipis']->count() }})
                        </p>

                        @if ($perhatian['stok_menipis']->isEmpty())
                            <p class="mt-1.5 text-xs text-ink/55">Semua stok masih aman.</p>
                        @else
                            <ul class="mt-2 space-y-1.5">
                                @foreach ($perhatian['stok_menipis']->take(5) as $b)
                                    <li>
                                        <a href="{{ route('produk.show', $b['produk']) }}"
                                           class="block truncate text-xs text-ink/70 hover:text-berry">
                                            {{ $b['produk']->name }} —
                                            <span class="font-bold tabular-nums">{{ angka($b['stok_gudang']) }}</span>
                                            <span class="text-ink/45">/ min {{ angka($b['produk']->min_stock) }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    {{-- Belum direkonsiliasi --}}
                    <div class="rounded-xl border border-berry/20 bg-berry/5 p-3">
                        <p class="text-xs font-bold text-berry">
                            Belum direkonsiliasi &gt; 7 hari ({{ $perhatian['belum_rekonsiliasi']->count() }})
                        </p>

                        @if ($perhatian['belum_rekonsiliasi']->isEmpty())
                            <p class="mt-1.5 text-xs text-ink/55">Semua pengiriman sudah ditindaklanjuti.</p>
                        @else
                            <ul class="mt-2 space-y-1.5">
                                @foreach ($perhatian['belum_rekonsiliasi']->take(5) as $kirim)
                                    <li>
                                        <a href="{{ route('pengiriman.show', $kirim) }}"
                                           class="block truncate text-xs text-ink/70 hover:text-berry">
                                            {{ $kirim->store->name }} —
                                            <span class="font-bold">{{ $kirim->umurHari() }} hari</span>
                                            <span class="text-ink/45">({{ $kirim->number }})</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </x-kartu>
        @endif

        {{-- Grafik --}}
        <div class="grid gap-5 lg:grid-cols-2">
            <x-kartu judul="Tren setoran &amp; laba" keterangan="Enam bulan terakhir">
                <div class="h-64"><canvas id="grafikTren" aria-label="Grafik tren setoran dan laba"></canvas></div>
            </x-kartu>

            <x-kartu judul="Komposisi penjualan" keterangan="Berdasarkan setoran per produk bulan ini">
                @if ($per_produk->isEmpty())
                    <p class="py-16 text-center text-sm text-ink/55">Belum ada penjualan bulan ini.</p>
                @else
                    <div class="h-64"><canvas id="grafikDonat" aria-label="Grafik komposisi penjualan per produk"></canvas></div>
                @endif
            </x-kartu>
        </div>

        {{-- Tingkat laku: batang berujung bulat seperti es krim stik --}}
        <x-kartu judul="Tingkat laku per produk" keterangan="Terjual dibagi jumlah yang dikirim">
            @if ($per_produk->isEmpty())
                <p class="py-10 text-center text-sm text-ink/55">Belum ada pengiriman yang direkonsiliasi bulan ini.</p>
            @else
                <ul class="space-y-3">
                    @foreach ($per_produk->take(8) as $b)
                        <li>
                            <div class="flex items-baseline justify-between gap-3">
                                <span class="min-w-0 truncate text-sm font-semibold">{{ $b['nama'] }}</span>
                                <span class="shrink-0 text-xs font-extrabold tabular-nums text-berry">
                                    {{ persen($b['tingkat_laku']) }}
                                </span>
                            </div>

                            {{-- Batang es krim stik: ujung membulat + "stik" di kiri. --}}
                            <div class="mt-1.5 flex items-center gap-1.5">
                                <span class="h-4 w-1.5 shrink-0 rounded-full bg-[#C9A227]" aria-hidden="true"></span>
                                <div class="h-4 flex-1 overflow-hidden rounded-full bg-frost">
                                    <div class="h-full rounded-full bg-gradient-to-r from-berry to-mint transition-all"
                                         style="width: {{ min(100, $b['tingkat_laku']) }}%"></div>
                                </div>
                            </div>

                            <p class="mt-1 text-[11px] text-ink/45">
                                {{ angka($b['qty_terjual']) }} terjual dari {{ angka($b['qty_kirim']) }} dikirim
                                &middot; laba {{ rupiah($b['laba_kotor']) }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-kartu>

        {{-- Toko terbesar --}}
        <x-kartu judul="5 toko dengan setoran terbesar" keterangan="Bulan ini">
            @if ($toko_teratas->isEmpty())
                <p class="py-16 text-center text-sm text-ink/55">Belum ada setoran bulan ini.</p>
            @else
                <div class="h-64"><canvas id="grafikToko" aria-label="Grafik lima toko dengan setoran terbesar"></canvas></div>
            @endif
        </x-kartu>
    </div>

    @include('beranda.grafik')

</x-app-layout>
