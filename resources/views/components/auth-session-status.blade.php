@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-xl border border-mint/30 bg-mint/10 px-4 py-3 text-sm font-semibold text-mint']) }}>
        {{ $status }}
    </div>
@endif
