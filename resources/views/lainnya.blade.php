{{-- Halaman "Lainnya" di HP: seluruh menu yang tidak muat di navigasi bawah. --}}
<x-app-layout title="Lainnya" subtitle="Semua menu aplikasi">

    @php $dibawah = config('navigation.bottom'); @endphp

    <div class="space-y-6">
        @foreach (config('navigation.groups') as $group)
            @php
                $items = nav_grup_terpakai($group)
                    ->reject(fn ($i) => in_array($i['key'], $dibawah, true));
            @endphp

            @if ($items->isNotEmpty())
                <section>
                    <h2 class="mb-2 px-1 text-[11px] font-bold uppercase tracking-wider text-ink/40">
                        {{ $group['label'] ?? 'Umum' }}
                    </h2>

                    <ul class="overflow-hidden rounded-2xl border border-berry/10 bg-white shadow-sm">
                        @foreach ($items as $item)
                            <li class="border-b border-berry/5 last:border-0">
                                <a href="{{ nav_url($item['route']) }}"
                                   class="flex min-h-[56px] items-center gap-3 px-4 text-sm font-semibold transition hover:bg-frost
                                          focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-berry">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-berry/10 text-berry">
                                        <x-icon :name="$item['icon']" class="h-5 w-5" />
                                    </span>
                                    <span class="flex-1">{{ $item['label'] }}</span>
                                    <span class="text-ink/30" aria-hidden="true">&rsaquo;</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @endforeach

        <section>
            <h2 class="mb-2 px-1 text-[11px] font-bold uppercase tracking-wider text-ink/40">Akun</h2>

            <ul class="overflow-hidden rounded-2xl border border-berry/10 bg-white shadow-sm">
                <li class="border-b border-berry/5">
                    <a href="{{ nav_url('profile.edit') }}"
                       class="flex min-h-[56px] items-center gap-3 px-4 text-sm font-semibold transition hover:bg-frost">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-berry/10 text-berry">
                            <x-icon name="profil" class="h-5 w-5" />
                        </span>
                        <span class="flex-1">Profil saya</span>
                        <span class="text-ink/30" aria-hidden="true">&rsaquo;</span>
                    </a>
                </li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex min-h-[56px] w-full items-center gap-3 px-4 text-sm font-semibold text-strawberry transition hover:bg-strawberry/5">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-strawberry/10">
                                <x-icon name="keluar" class="h-5 w-5" />
                            </span>
                            <span class="flex-1 text-left">Keluar</span>
                        </button>
                    </form>
                </li>
            </ul>
        </section>

        {{--
            Pemasangan aplikasi. Empat keadaan diatur resources/js/pwa.js:
            "siap" (Chrome/Edge menawarkan pasang), "ios" (harus manual lewat
            menu Bagikan), "terpasang", dan "belum" (mis. belum HTTPS).
        --}}
        <section data-pasang-wadah>
            <h2 class="mb-2 px-1 text-[11px] font-bold uppercase tracking-wider text-ink/40">Aplikasi</h2>

            <div class="rounded-2xl border border-berry/10 bg-white p-4 shadow-sm">

                <div data-pasang-keadaan="siap" hidden>
                    <p class="text-sm font-semibold">Pasang di layar utama</p>
                    <p class="mt-1 text-xs text-ink/55">
                        Aplikasi terbuka langsung tanpa lewat peramban, dan lebih cepat dipakai saat mengantar barang.
                    </p>
                    <button type="button" data-pasang-aplikasi
                            class="mt-3 flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl bg-berry px-4 text-sm font-bold text-white transition hover:bg-berry/90
                                   focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-berry">
                        Pasang aplikasi
                    </button>
                </div>

                <div data-pasang-keadaan="ios" hidden>
                    <p class="text-sm font-semibold">Pasang di iPhone atau iPad</p>
                    <ol class="mt-2 list-decimal space-y-1 pl-5 text-xs text-ink/55">
                        <li>Ketuk tombol <span class="font-semibold text-ink/75">Bagikan</span> di bawah layar Safari.</li>
                        <li>Pilih <span class="font-semibold text-ink/75">Tambah ke Layar Utama</span>.</li>
                        <li>Ketuk <span class="font-semibold text-ink/75">Tambah</span> di pojok kanan atas.</li>
                    </ol>
                </div>

                <div data-pasang-keadaan="terpasang" hidden>
                    <p class="text-sm font-semibold text-mint">Aplikasi sudah terpasang</p>
                    <p class="mt-1 text-xs text-ink/55">
                        Buka lewat ikon {{ config('app.name') }} di layar utama.
                    </p>
                </div>

                {{-- Keadaan awal sebelum JavaScript menilai: kalau skripnya gagal
                     dimuat, yang tampil tetap keterangan yang masuk akal. --}}
                <div data-pasang-keadaan="belum">
                    <p class="text-sm font-semibold">Pasang di layar utama</p>
                    <p class="mt-1 text-xs text-ink/55">
                        Belum bisa dipasang dari peramban ini. Pemasangan hanya tersedia lewat alamat HTTPS
                        (atau localhost) memakai Chrome, Edge, atau Safari.
                    </p>
                </div>

            </div>
        </section>

        <p class="px-1 text-center text-xs text-ink/40">
            {{ config('app.name') }} &middot; versi 0.1
        </p>
    </div>

</x-app-layout>
