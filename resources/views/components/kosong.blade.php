@props(['ikon' => 'lainnya', 'judul' => 'Belum ada data', 'aksiTeks' => null, 'aksiUrl' => null])

<div class="px-6 py-12 text-center">
    <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-frost text-berry">
        <x-icon :name="$ikon" class="h-7 w-7" />
    </div>

    <h3 class="mt-4 text-base font-extrabold">{{ $judul }}</h3>
    <p class="mx-auto mt-1.5 max-w-sm text-sm leading-relaxed text-ink/55">{{ $slot }}</p>

    @if ($aksiTeks && $aksiUrl)
        <a href="{{ $aksiUrl }}"
           class="mt-5 inline-flex min-h-[44px] items-center rounded-xl bg-berry px-5 text-sm font-bold text-white transition hover:bg-berry/90">
            {{ $aksiTeks }}
        </a>
    @endif
</div>
