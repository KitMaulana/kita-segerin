{{--
    Beranda sementara (Tahap 1). Infografis Chart.js dan angka sungguhan
    dipasang pada Tahap 9; di sini hanya rangka tampilan dan tombol cepat.
--}}
<x-app-layout title="Beranda" subtitle="Selamat datang, {{ Str::before(auth()->user()->name, ' ') }}">

    {{-- Kartu ringkasan --}}
    <section aria-labelledby="judul-ringkasan">
        <h2 id="judul-ringkasan" class="sr-only">Ringkasan bulan ini</h2>

        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            @foreach ([
                ['label' => 'Setoran bulan ini', 'nilai' => 0, 'ikon' => 'tagihan', 'warna' => 'text-berry'],
                ['label' => 'Laba bersih bulan ini', 'nilai' => 0, 'ikon' => 'laporan', 'warna' => 'text-mint'],
                ['label' => 'Piutang belum dibayar', 'nilai' => 0, 'ikon' => 'kas', 'warna' => 'text-mango'],
                ['label' => 'Nilai persediaan', 'nilai' => 0, 'ikon' => 'stok', 'warna' => 'text-ink'],
            ] as $kartu)
                <div class="rounded-2xl border border-berry/10 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-xs font-semibold leading-snug text-ink/55">{{ $kartu['label'] }}</p>
                        <x-icon :name="$kartu['ikon']" class="h-5 w-5 shrink-0 text-berry/40" />
                    </div>
                    <p class="mt-2 text-xl font-extrabold tabular-nums {{ $kartu['warna'] }} sm:text-2xl">
                        {{ rupiah($kartu['nilai']) }}
                    </p>
                    <p class="mt-1 text-[11px] text-ink/40">Menunggu data transaksi</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Tombol cepat --}}
    <section aria-labelledby="judul-cepat" class="mt-6">
        <h2 id="judul-cepat" class="mb-3 text-sm font-bold text-ink/70">Aksi cepat</h2>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            @foreach ([
                ['label' => 'Catat pengiriman', 'route' => 'pengiriman.index', 'ikon' => 'pengiriman'],
                ['label' => 'Buat tagihan', 'route' => 'tagihan.index', 'ikon' => 'tagihan'],
                ['label' => 'Catat biaya', 'route' => 'biaya.index', 'ikon' => 'biaya'],
            ] as $aksi)
                <a href="{{ nav_url($aksi['route']) }}"
                   class="flex min-h-[56px] items-center gap-3 rounded-2xl border border-berry/10 bg-white px-4 text-sm font-bold text-ink shadow-sm transition
                          hover:border-berry/30 hover:bg-berry/5
                          focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-berry">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-berry/10 text-berry">
                        <x-icon :name="$aksi['ikon']" class="h-5 w-5" />
                    </span>
                    {{ $aksi['label'] }}
                </a>
            @endforeach
        </div>
    </section>

    {{-- Catatan tahap --}}
    <section class="mt-6 rounded-2xl border border-berry/10 bg-white p-5 shadow-sm">
        <div class="flex items-start gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-mint/10 text-mint">
                <x-icon name="produk" class="h-5 w-5" />
            </span>
            <div>
                <h2 class="text-sm font-extrabold">Fondasi aplikasi sudah siap</h2>
                <p class="mt-1 text-sm leading-relaxed text-ink/60">
                    Tahap 1 selesai: login, tata letak, warna, font, dan pembantu format Rupiah.
                    Menu lain masih berupa halaman sementara dan akan diisi pada tahap berikutnya.
                </p>

                <dl class="mt-4 grid gap-2 text-sm sm:grid-cols-3">
                    <div class="rounded-xl bg-frost px-3 py-2">
                        <dt class="text-[11px] font-semibold text-ink/50">Contoh format uang</dt>
                        <dd class="font-bold tabular-nums">{{ rupiah(261000) }}</dd>
                    </div>
                    <div class="rounded-xl bg-frost px-3 py-2">
                        <dt class="text-[11px] font-semibold text-ink/50">Contoh format tanggal</dt>
                        <dd class="font-bold">{{ tanggal_indo(now()) }}</dd>
                    </div>
                    <div class="rounded-xl bg-frost px-3 py-2">
                        <dt class="text-[11px] font-semibold text-ink/50">Contoh terbilang</dt>
                        <dd class="font-bold capitalize">{{ terbilang(261000) }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

</x-app-layout>
