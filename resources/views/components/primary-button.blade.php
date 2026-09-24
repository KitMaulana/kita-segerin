<button {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-berry px-5 text-sm font-bold text-white
                transition hover:bg-berry/90 active:bg-berry
                focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-berry
                disabled:cursor-not-allowed disabled:opacity-60',
]) }}>
    {{ $slot }}
</button>
