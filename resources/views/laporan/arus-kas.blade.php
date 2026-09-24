<x-app-layout title="Laporan Arus Kas" :subtitle="tanggal_indo($dari).' – '.tanggal_indo($sampai)">

    <div class="mx-auto max-w-3xl space-y-5">

        @include('laporan.partials.saringan')

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([
                ['Saldo awal', rupiah($kas['saldo_awal']), 'text-ink/70'],
                ['Total masuk', rupiah($kas['total_masuk']), 'text-mint'],
                ['Total keluar', rupiah($kas['total_keluar']), 'text-strawberry'],
                ['Saldo akhir', rupiah($kas['saldo_akhir']), $kas['saldo_akhir'] >= 0 ? 'text-berry' : 'text-strawberry'],
            ] as [$label, $nilai, $warna])
                <div class="rounded-2xl border border-berry/10 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold text-ink/55">{{ $label }}</p>
                    <p class="mt-1 text-base font-extrabold tabular-nums {{ $warna }} sm:text-lg">{{ $nilai }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <x-kartu judul="Uang masuk" padat>
                @if ($kas['masuk_per_kategori']->isEmpty())
                    <p class="px-5 py-6 text-center text-sm text-ink/55">Tidak ada uang masuk pada periode ini.</p>
                @else
                    <ul class="divide-y divide-berry/5">
                        @foreach ($kas['masuk_per_kategori'] as $b)
                            <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
                                <span class="text-sm">{{ $b['nama'] }}</span>
                                <span class="text-sm font-bold tabular-nums text-mint">{{ rupiah($b['jumlah']) }}</span>
                            </li>
                        @endforeach
                        <li class="flex items-center justify-between gap-3 bg-frost/60 px-4 py-3 font-bold sm:px-5">
                            <span class="text-sm">Total masuk</span>
                            <span class="text-sm tabular-nums text-mint">{{ rupiah($kas['total_masuk']) }}</span>
                        </li>
                    </ul>
                @endif
            </x-kartu>

            <x-kartu judul="Uang keluar" padat>
                @if ($kas['keluar_per_kategori']->isEmpty())
                    <p class="px-5 py-6 text-center text-sm text-ink/55">Tidak ada uang keluar pada periode ini.</p>
                @else
                    <ul class="divide-y divide-berry/5">
                        @foreach ($kas['keluar_per_kategori'] as $b)
                            <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
                                <span class="text-sm">{{ $b['nama'] }}</span>
                                <span class="text-sm font-bold tabular-nums text-strawberry">{{ rupiah($b['jumlah']) }}</span>
                            </li>
                        @endforeach
                        <li class="flex items-center justify-between gap-3 bg-frost/60 px-4 py-3 font-bold sm:px-5">
                            <span class="text-sm">Total keluar</span>
                            <span class="text-sm tabular-nums text-strawberry">{{ rupiah($kas['total_keluar']) }}</span>
                        </li>
                    </ul>
                @endif
            </x-kartu>
        </div>

        <x-kartu>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold text-ink/55">Saldo akhir periode</p>
                    <p class="text-2xl font-extrabold tabular-nums {{ $kas['saldo_akhir'] >= 0 ? 'text-berry' : 'text-strawberry' }}">
                        {{ rupiah($kas['saldo_akhir']) }}
                    </p>
                </div>
                <a href="{{ route('kas.index', ['dari' => $dari->toDateString(), 'sampai' => $sampai->toDateString()]) }}"
                   class="inline-flex min-h-[44px] items-center rounded-xl border border-berry/20 px-4 text-sm font-bold text-berry transition hover:bg-berry/5">
                    Lihat buku kas
                </a>
            </div>
        </x-kartu>

        <x-tautan-tombol gaya="kedua" :href="route('laporan.index')">Kembali ke daftar laporan</x-tautan-tombol>
    </div>

</x-app-layout>
