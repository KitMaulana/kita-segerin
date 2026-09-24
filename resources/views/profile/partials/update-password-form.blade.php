<header>
    <h2 class="text-base font-extrabold">Ganti kata sandi</h2>
    <p class="mt-1 text-sm text-ink/55">Gunakan kata sandi yang panjang dan tidak dipakai di tempat lain.</p>
</header>

<form method="POST" action="{{ route('password.update') }}" class="mt-5 space-y-4">
    @csrf
    @method('put')

    <div>
        <x-input-label for="update_password_current_password" value="Kata sandi saat ini" />
        <x-text-input id="update_password_current_password" name="current_password" type="password"
                      class="mt-1.5" autocomplete="current-password" />
        <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="update_password_password" value="Kata sandi baru" />
        <x-text-input id="update_password_password" name="password" type="password"
                      class="mt-1.5" autocomplete="new-password" />
        <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="update_password_password_confirmation" value="Ulangi kata sandi baru" />
        <x-text-input id="update_password_password_confirmation" name="password_confirmation"
                      type="password" class="mt-1.5" autocomplete="new-password" />
        <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
    </div>

    <x-primary-button>Simpan kata sandi</x-primary-button>
</form>
