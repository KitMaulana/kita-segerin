<header>
    <h2 class="text-base font-extrabold">Data diri</h2>
    <p class="mt-1 text-sm text-ink/55">Nama ini muncul di log aktivitas dan dokumen yang Anda buat.</p>
</header>

<form method="POST" action="{{ route('profile.update') }}" class="mt-5 space-y-4">
    @csrf
    @method('patch')

    <div>
        <x-input-label for="name" value="Nama" />
        <x-text-input id="name" name="name" type="text" class="mt-1.5"
                      :value="old('name', $user->name)" required autocomplete="name" />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="username" value="Username" />
        <x-text-input id="username" type="text" class="mt-1.5 bg-frost"
                      :value="$user->username" disabled />
        <p class="mt-1.5 text-xs text-ink/50">Username hanya bisa diubah oleh pemilik usaha.</p>
    </div>

    <div>
        <x-input-label for="email" value="Email (boleh dikosongkan)" />
        <x-text-input id="email" name="email" type="email" class="mt-1.5"
                      :value="old('email', $user->email)" autocomplete="email" inputmode="email" />
        <x-input-error class="mt-2" :messages="$errors->get('email')" />
        <p class="mt-1.5 text-xs text-ink/50">Diisi bila Anda ingin bisa mengatur ulang kata sandi sendiri.</p>
    </div>

    <x-primary-button>Simpan data diri</x-primary-button>
</form>
