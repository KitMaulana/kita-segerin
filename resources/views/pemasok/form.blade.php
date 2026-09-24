@php $baru = ! $pemasok->exists; @endphp

<x-app-layout :title="$baru ? 'Tambah Pemasok' : 'Ubah Pemasok'" :subtitle="$baru ? null : $pemasok->name">

    <form method="POST"
          action="{{ $baru ? route('pemasok.store') : route('pemasok.update', $pemasok) }}"
          class="mx-auto max-w-2xl space-y-5">
        @csrf
        @unless ($baru) @method('put') @endunless

        <x-kartu judul="Data pemasok">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="name" value="Nama pemasok" />
                    <x-text-input id="name" name="name" class="mt-1.5" :value="old('name', $pemasok->name)"
                                  required autofocus placeholder="mis. PT Es Krim Nusantara" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="contact_person" value="Nama kontak" />
                    <x-text-input id="contact_person" name="contact_person" class="mt-1.5"
                                  :value="old('contact_person', $pemasok->contact_person)" />
                    <x-input-error :messages="$errors->get('contact_person')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="phone" value="Telepon" />
                    <x-text-input id="phone" name="phone" class="mt-1.5" inputmode="tel"
                                  :value="old('phone', $pemasok->phone)" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="address" value="Alamat" />
                    <x-text-input id="address" name="address" class="mt-1.5"
                                  :value="old('address', $pemasok->address)" />
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="notes" value="Catatan" />
                    <textarea id="notes" name="notes" rows="3"
                              class="mt-1.5 block w-full rounded-xl border-berry/20 bg-white px-3 py-2 text-sm shadow-sm
                                     focus:border-berry focus:ring-2 focus:ring-berry/30"
                              placeholder="mis. minimal 10 dus, ambil Senin dan Kamis">{{ old('notes', $pemasok->notes) }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>
            </div>

            <label class="mt-4 flex min-h-[44px] items-center gap-2.5 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       @checked(old('is_active', $pemasok->is_active ?? true))
                       class="h-5 w-5 rounded border-berry/30 text-berry focus:ring-berry/40">
                Pemasok aktif
            </label>
        </x-kartu>

        <div class="flex flex-wrap gap-3">
            <x-primary-button>Simpan pemasok</x-primary-button>
            <x-tautan-tombol gaya="kedua" :href="route('pemasok.index')">Batal</x-tautan-tombol>
        </div>
    </form>

</x-app-layout>
