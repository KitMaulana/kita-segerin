<x-guest-layout title="Lupa kata sandi">

    <h2 class="text-lg font-extrabold">Lupa kata sandi</h2>
    <p class="mt-1 text-sm leading-relaxed text-ink/55">
        Masukkan email akun Anda, nanti kami kirim tautan untuk membuat kata sandi baru.
        Bila akun Anda tidak memakai email, minta pemilik usaha mengatur ulang kata sandi Anda.
    </p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="mt-1.5" type="email" name="email"
                          :value="old('email')" required autofocus inputmode="email" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">Kirim tautan</x-primary-button>

        <p class="text-center">
            <a href="{{ route('login') }}"
               class="inline-block rounded px-2 py-1 text-sm font-semibold text-berry underline underline-offset-2">
                Kembali ke halaman masuk
            </a>
        </p>
    </form>

</x-guest-layout>
