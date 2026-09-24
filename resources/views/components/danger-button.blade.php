<button {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-strawberry px-5 text-sm font-bold text-white
                transition hover:bg-strawberry/90
                focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-strawberry',
]) }}>
    {{ $slot }}
</button>
