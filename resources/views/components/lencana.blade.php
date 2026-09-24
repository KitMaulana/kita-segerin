@props(['warna' => 'berry'])

@php
    $kelas = [
        'berry' => 'bg-berry/10 text-berry',
        'mint' => 'bg-mint/10 text-mint',
        'mango' => 'bg-mango/15 text-mango',
        'strawberry' => 'bg-strawberry/10 text-strawberry',
        'ink' => 'bg-ink/10 text-ink/70',
    ][$warna] ?? 'bg-ink/10 text-ink/70';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold leading-none $kelas"]) }}>
    {{ $slot }}
</span>
