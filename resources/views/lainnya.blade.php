{{-- Halaman "Lainnya" di HP: seluruh menu yang tidak muat di navigasi bawah. --}}
<x-app-layout title="Lainnya" subtitle="Semua menu aplikasi">

    @php $dibawah = config('navigation.bottom'); @endphp

    <div class="space-y-6">
        @foreach (config('navigation.groups') as $group)
            @php
                $items = collect($group['items'])->reject(fn ($i) => in_array($i['key'], $dibawah, true));
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

        <p class="px-1 text-center text-xs text-ink/40">
            {{ config('app.name') }} &middot; versi 0.1 (Tahap 1)
        </p>
    </div>

</x-app-layout>
