@props(['disabled' => false])

<select @disabled($disabled)
    {{ $attributes->merge([
        'class' => 'block w-full min-h-[44px] rounded-xl border-berry/20 bg-white px-3 text-sm text-ink shadow-sm
                    focus:border-berry focus:ring-2 focus:ring-berry/30 disabled:bg-frost',
    ]) }}>
    {{ $slot }}
</select>
