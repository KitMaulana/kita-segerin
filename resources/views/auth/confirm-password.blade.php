<x-guest-layout title="Konfirmasi kata sandi">

    <h2 class="text-lg font-extrabold">Konfirmasi kata sandi</h2>
    <p class="mt-1 text-sm leading-relaxed text-ink/55">
        Bagian ini berisi data penting. Masukkan kembali kata sandi Anda untuk melanjutkan.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="password" value="Kata sandi" />
            <x-text-input id="password" class="mt-1.5" type="password" name="password"
                          required autocomplete="current-password" autofocus />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">Lanjutkan</x-primary-button>
    </form>

</x-guest-layout>
