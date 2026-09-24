{{-- Header atas: judul halaman + identitas usaha di HP. --}}
<header class="sticky top-0 z-30 border-b border-berry/10 bg-berry text-white lg:bg-white lg:text-ink">
    <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">

        {{-- Logo hanya di HP, karena desktop sudah punya sidebar. --}}
        <div class="flex items-center gap-2 lg:hidden">
            <x-app-logo class="h-8 w-8" />
        </div>

        <div class="min-w-0 flex-1">
            <h1 class="truncate text-base font-bold leading-tight lg:text-lg">
                {{ $title ?? 'Beranda' }}
            </h1>
            @isset($subtitle)
                <p class="truncate text-xs text-white/70 lg:text-ink/50">{{ $subtitle }}</p>
            @endisset
        </div>

        @isset($actions)
            <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
        @endisset

        {{-- Menu akun di HP --}}
        <div class="lg:hidden">
            <a href="{{ nav_url('profile.edit') }}"
               class="grid h-10 w-10 place-items-center rounded-full bg-white/15 text-sm font-bold"
               aria-label="Profil saya">
                {{ Str::of(auth()->user()?->name ?? '?')->substr(0, 1)->upper() }}
            </a>
        </div>
    </div>
</header>
