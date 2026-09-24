{{-- Sidebar kiri, hanya tampil di layar besar (lg ke atas). --}}
<aside class="hidden w-64 shrink-0 flex-col border-r border-berry/10 bg-white lg:flex">

    <div class="flex h-16 items-center gap-3 border-b border-berry/10 px-5">
        <x-app-logo class="h-9 w-9" />
        <div class="min-w-0">
            <p class="truncate text-sm font-extrabold leading-tight text-berry">{{ config('app.name') }}</p>
            <p class="truncate text-[11px] leading-tight text-ink/50">Sistem keuangan titip jual</p>
        </div>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5" aria-label="Menu utama">
        @foreach (config('navigation.groups') as $group)
            @php $items = nav_grup_terpakai($group); @endphp
            @continue ($items->isEmpty())

            <div>
                @if ($group['label'])
                    <p class="mb-2 px-3 text-[11px] font-bold uppercase tracking-wider text-ink/40">
                        {{ $group['label'] }}
                    </p>
                @endif

                <ul class="space-y-1">
                    @foreach ($items as $item)
                        @php $aktif = nav_aktif($item['key']); @endphp
                        <li>
                            <a href="{{ nav_url($item['route']) }}"
                               @if ($aktif) aria-current="page" @endif
                               class="flex min-h-[44px] items-center gap-3 rounded-xl px-3 text-sm font-semibold transition
                                      focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-berry
                                      {{ $aktif
                                            ? 'bg-berry text-white shadow-sm shadow-berry/30'
                                            : 'text-ink/70 hover:bg-frost hover:text-berry' }}">
                                <x-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                                <span class="truncate">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <div class="border-t border-berry/10 p-3">
        <a href="{{ nav_url('profile.edit') }}"
           class="flex min-h-[44px] items-center gap-3 rounded-xl px-3 text-sm font-semibold text-ink/70 transition hover:bg-frost hover:text-berry">
            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-berry/10 text-xs font-bold text-berry">
                {{ Str::of(auth()->user()?->name ?? '?')->substr(0, 1)->upper() }}
            </span>
            <span class="min-w-0 flex-1">
                <span class="block truncate">{{ auth()->user()?->name }}</span>
                <span class="block truncate text-[11px] font-normal text-ink/45">Lihat profil</span>
            </span>
        </a>

        <form method="POST" action="{{ route('logout') }}" class="mt-1">
            @csrf
            <button type="submit"
                    class="flex min-h-[44px] w-full items-center gap-3 rounded-xl px-3 text-sm font-semibold text-ink/60 transition hover:bg-strawberry/10 hover:text-strawberry">
                <x-icon name="keluar" class="h-5 w-5 shrink-0" />
                Keluar
            </button>
        </form>
    </div>
</aside>
