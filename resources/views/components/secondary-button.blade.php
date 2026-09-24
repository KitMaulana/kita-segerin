<button {{ $attributes->merge([
    'type' => 'button',
    'class' => 'inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-berry/20 bg-white px-5 text-sm font-bold text-ink
                transition hover:bg-frost
                focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-berry',
]) }}>
    {{ $slot }}
</button>
