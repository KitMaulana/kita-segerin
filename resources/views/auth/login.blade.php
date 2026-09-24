<x-guest-layout title="Masuk">

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <h2 class="text-lg font-extrabold">Masuk ke aplikasi</h2>
    <p class="mt-1 text-sm text-ink/55">Gunakan username atau email yang diberikan pemilik usaha.</p>

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="login" value="Username atau email" />
            <x-text-input id="login" class="mt-1.5" type="text" name="login"
                          :value="old('login')" required autofocus autocomplete="username"
                          inputmode="text" placeholder="mis. pemilik" />
            <x-input-error :messages="$errors->get('login')" class="mt-2" />
        </div>

        <div x-data="{ lihat: false }">
            <x-input-label for="password" value="Kata sandi" />
            <div class="relative mt-1.5">
                <x-text-input id="password" class="pr-12"
                              ::type="lihat ? 'text' : 'password'"
                              type="password" name="password" required autocomplete="current-password" />
                <button type="button" @click="lihat = !lihat"
                        class="absolute inset-y-0 right-0 grid w-12 place-items-center rounded-r-xl text-xs font-bold text-berry"
                        :aria-label="lihat ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                    <span x-text="lihat ? 'Tutup' : 'Lihat'"></span>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="flex min-h-[44px] items-center gap-2.5 text-sm text-ink/70">
            <input id="remember_me" type="checkbox" name="remember"
                   class="h-5 w-5 rounded border-berry/30 text-berry focus:ring-berry/40">
            Ingat saya di perangkat ini
        </label>

        <x-primary-button class="w-full">Masuk</x-primary-button>

        @if (Route::has('password.request'))
            <p class="text-center">
                <a href="{{ route('password.request') }}"
                   class="inline-block rounded px-2 py-1 text-sm font-semibold text-berry underline underline-offset-2 hover:text-berry/80">
                    Lupa kata sandi?
                </a>
            </p>
        @endif
    </form>

</x-guest-layout>
