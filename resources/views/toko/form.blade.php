@php $baru = ! $toko->exists; @endphp

<x-app-layout :title="$baru ? 'Tambah Toko' : 'Ubah Toko'" :subtitle="$baru ? null : $toko->name">

    <form method="POST"
          action="{{ $baru ? route('toko.store') : route('toko.update', $toko) }}"
          class="mx-auto max-w-2xl space-y-5">
        @csrf
        @unless ($baru) @method('put') @endunless

        <x-kartu judul="Identitas toko">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="code" value="Kode toko" />
                    <x-text-input id="code" name="code" class="mt-1.5 uppercase tabular-nums"
                                  :value="old('code', $toko->code)" required placeholder="TK-001" />
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="type" value="Jenis" />
                    <x-select id="type" name="type" class="mt-1.5">
                        @foreach (App\Models\Store::JENIS as $nilai => $label)
                            <option value="{{ $nilai }}" @selected(old('type', $toko->type) === $nilai)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="name" value="Nama toko" />
                    <x-text-input id="name" name="name" class="mt-1.5" :value="old('name', $toko->name)"
                                  required autofocus placeholder="mis. Koperasi Budi Utama" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="contact_person" value="Nama kontak" />
                    <x-text-input id="contact_person" name="contact_person" class="mt-1.5"
                                  :value="old('contact_person', $toko->contact_person)" />
                    <x-input-error :messages="$errors->get('contact_person')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="phone" value="Telepon / WhatsApp" />
                    <x-text-input id="phone" name="phone" class="mt-1.5" inputmode="tel"
                                  :value="old('phone', $toko->phone)" placeholder="08xxxxxxxxxx" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    <p class="mt-1.5 text-xs text-ink/50">Dipakai untuk tombol kirim tagihan lewat WhatsApp.</p>
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="address" value="Alamat" />
                    <x-text-input id="address" name="address" class="mt-1.5" :value="old('address', $toko->address)" />
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="payment_term_days" value="Tempo bayar (hari)" />
                    <x-text-input id="payment_term_days" name="payment_term_days" type="number" min="0" max="365"
                                  class="mt-1.5 tabular-nums" :value="old('payment_term_days', $toko->payment_term_days)"
                                  placeholder="kosongkan untuk memakai default" />
                    <x-input-error :messages="$errors->get('payment_term_days')" class="mt-2" />
                    <p class="mt-1.5 text-xs text-ink/50">
                        Bila dikosongkan, dipakai default pengaturan usaha
                        ({{ App\Models\Setting::ambil('jatuh_tempo_hari', '7') }} hari).
                    </p>
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="notes" value="Catatan" />
                    <textarea id="notes" name="notes" rows="2"
                              class="mt-1.5 block w-full rounded-xl border-berry/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-berry focus:ring-2 focus:ring-berry/30"
                              placeholder="mis. kirim Senin, rekonsiliasi Jumat">{{ old('notes', $toko->notes) }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>
            </div>

            <label class="mt-4 flex min-h-[44px] items-center gap-2.5 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $toko->is_active ?? true))
                       class="h-5 w-5 rounded border-berry/30 text-berry focus:ring-berry/40">
                Toko aktif (bisa dipilih saat membuat pengiriman)
            </label>
        </x-kartu>

        <div class="flex flex-wrap gap-3">
            <x-primary-button>Simpan toko</x-primary-button>
            <x-tautan-tombol gaya="kedua" :href="$baru ? route('toko.index') : route('toko.show', $toko)">Batal</x-tautan-tombol>
        </div>
    </form>

</x-app-layout>
