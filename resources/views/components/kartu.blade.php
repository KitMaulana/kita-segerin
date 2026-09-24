@props(['judul' => null, 'keterangan' => null, 'padat' => false])

<section {{ $attributes->merge(['class' => 'rounded-2xl border border-berry/10 bg-white shadow-sm']) }}>
    @if ($judul || isset($aksi))
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-berry/5 px-4 py-4 sm:px-5">
            <div class="min-w-0">
                @if ($judul)
                    <h2 class="text-base font-extrabold">{{ $judul }}</h2>
                @endif
                @if ($keterangan)
                    <p class="mt-0.5 text-sm text-ink/55">{{ $keterangan }}</p>
                @endif
            </div>

            @isset($aksi)
                <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $aksi }}</div>
            @endisset
        </header>
    @endif

    <div class="{{ $padat ? '' : 'px-4 py-4 sm:px-5' }}">
        {{ $slot }}
    </div>
</section>
