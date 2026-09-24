{{-- Navigasi bawah, hanya di HP. Lima menu sesuai panduan tampilan. --}}
@php
    $kunciBawah = config('navigation.bottom');
    $semua = collect(config('navigation.groups'))->flatMap(fn ($g) => $g['items'])->keyBy('key');

    $menuBawah = collect($kunciBawah)->map(fn ($k) => $semua->get($k) ?? [
        'key' => 'lainnya',
        'label' => 'Lainnya',
        'route' => 'lainnya',
        'icon' => 'lainnya',
    ]);
@endphp

<nav aria-label="Menu bawah"
     class="fixed inset-x-0 bottom-0 z-40 border-t border-berry/10 bg-white/95 backdrop-blur
            pb-[env(safe-area-inset-bottom)] lg:hidden">
    <ul class="grid grid-cols-5">
        @foreach ($menuBawah as $item)
            @php $aktif = nav_aktif($item['key']); @endphp
            <li>
                <a href="{{ nav_url($item['route']) }}"
                   @if ($aktif) aria-current="page" @endif
                   class="flex min-h-[56px] flex-col items-center justify-center gap-0.5 px-1 py-2 text-[11px] font-semibold transition
                          focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-berry
                          {{ $aktif ? 'text-berry' : 'text-ink/50' }}">
                    <span class="relative">
                        <x-icon :name="$item['icon']" class="h-6 w-6" />
                        @if ($aktif)
                            <span class="absolute -bottom-1 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-berry"></span>
                        @endif
                    </span>
                    <span class="truncate">{{ Str::before($item['label'], ' /') }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>
