@php $baru = ! $akun->exists; @endphp

<x-app-layout :title="$baru ? 'Tambah Akun' : 'Ubah Akun'"
              :subtitle="$baru ? 'Buat akun baru untuk admin atau pemantau' : $akun->name">

    <form method="POST"
          action="{{ $baru ? route('akun.store') : route('akun.update', $akun) }}"
          class="mx-auto max-w-2xl space-y-5">
        @csrf
        @unless ($baru) @method('put') @endunless

        <x-kartu judul="Data akun">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="name" value="Nama lengkap" />
                    <x-text-input id="name" name="name" class="mt-1.5" :value="old('name', $akun->name)"
                                  required autofocus autocomplete="name" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="username" value="Username" />
                    <x-text-input id="username" name="username" class="mt-1.5"
                                  :value="old('username', $akun->username)" required autocomplete="off"
                                  placeholder="mis. siti" />
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                    <p class="mt-1.5 text-xs text-ink/50">Dipakai untuk masuk. Huruf kecil, tanpa spasi.</p>
                </div>

                <div>
                    <x-input-label for="email" value="Email (boleh dikosongkan)" />
                    <x-text-input id="email" name="email" type="email" class="mt-1.5"
                                  :value="old('email', $akun->email)" inputmode="email" autocomplete="off" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
            </div>
        </x-kartu>

        <x-kartu judul="Peran &amp; status">
            <fieldset>
                <legend class="sr-only">Peran</legend>

                <div class="space-y-2">
                    @foreach (App\Models\User::PERAN as $nilai => $label)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-berry/15 p-3 transition hover:bg-frost has-[:checked]:border-berry has-[:checked]:bg-berry/5">
                            <input type="radio" name="role" value="{{ $nilai }}"
                                   @checked(old('role', $akun->role ?? 'admin') === $nilai)
                                   class="mt-0.5 h-5 w-5 border-berry/30 text-berry focus:ring-berry/40">
                            <span class="min-w-0">
                                <span class="block text-sm font-bold">{{ $label }}</span>
                                <span class="block text-xs leading-relaxed text-ink/55">
                                    {{ App\Models\User::KETERANGAN_PERAN[$nilai] }}
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>

                <x-input-error :messages="$errors->get('role')" class="mt-2" />
            </fieldset>

            <label class="mt-4 flex min-h-[44px] items-center gap-2.5 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       @checked(old('is_active', $akun->is_active ?? true))
                       class="h-5 w-5 rounded border-berry/30 text-berry focus:ring-berry/40">
                Akun aktif (boleh masuk ke aplikasi)
            </label>
        </x-kartu>

        <x-kartu :judul="$baru ? 'Kata sandi' : 'Ganti kata sandi'"
                 :keterangan="$baru ? 'Minimal 8 karakter.' : 'Kosongkan bila kata sandi tidak diganti.'">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="password" value="Kata sandi" />
                    <x-text-input id="password" name="password" type="password" class="mt-1.5"
                                  autocomplete="new-password" :required="$baru" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" value="Ulangi kata sandi" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                                  class="mt-1.5" autocomplete="new-password" :required="$baru" />
                </div>
            </div>
        </x-kartu>

        <div class="flex flex-wrap gap-3">
            <x-primary-button>{{ $baru ? 'Simpan akun' : 'Simpan perubahan' }}</x-primary-button>
            <x-tautan-tombol gaya="kedua" :href="route('akun.index')">Batal</x-tautan-tombol>
        </div>
    </form>

</x-app-layout>
