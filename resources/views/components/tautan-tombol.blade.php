@props(['gaya' => 'utama', 'href' => '#'])

@php
    $kelas = [
        'utama' => 'bg-berry text-white hover:bg-berry/90',
        'kedua' => 'border border-berry/20 bg-white text-ink hover:bg-frost',
        'bahaya' => 'bg-strawberry text-white hover:bg-strawberry/90',
    ][$gaya] ?? 'bg-berry text-white hover:bg-berry/90';
@endphp

<a href="{{ $href }}"
   {{ $attributes->merge(['class' => "inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl px-4 text-sm font-bold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-berry $kelas"]) }}>
    {{ $slot }}
</a>
