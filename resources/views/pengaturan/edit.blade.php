<x-app-layout title="Pengaturan Usaha" subtitle="Identitas yang tampil di tagihan dan surat jalan">

    <form method="POST" action="{{ route('pengaturan.update') }}" enctype="multipart/form-data"
          class="mx-auto max-w-2xl space-y-5">
        @csrf
        @method('put')

        <x-kartu judul="Identitas usaha">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="nama_usaha" value="Nama usaha" />
                    <x-text-input id="nama_usaha" name="nama_usaha" class="mt-1.5"
                                  :value="old('nama_usaha', $pengaturan['nama_usaha'])" required />
                    <x-input-error :messages="$errors->get('nama_usaha')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="alamat" value="Alamat" />
                    <x-text-input id="alamat" name="alamat" class="mt-1.5"
                                  :value="old('alamat', $pengaturan['alamat'])" />
                    <x-input-error :messages="$errors->get('alamat')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="telepon" value="Telepon" />
                    <x-text-input id="telepon" name="telepon" class="mt-1.5" inputmode="tel"
                                  :value="old('telepon', $pengaturan['telepon'])" />
                    <x-input-error :messages="$errors->get('telepon')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="mt-1.5" inputmode="email"
                                  :value="old('email', $pengaturan['email'])" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
            </div>
        </x-kartu>

        <x-kartu judul="Logo" keterangan="Tampil di kop tagihan dan surat jalan. JPG atau PNG, maksimal 2 MB.">
            @if ($pengaturan['logo_path'])
                <div class="mb-4 flex items-center gap-4">
                    <img src="{{ Storage::url($pengaturan['logo_path']) }}" alt="Logo usaha saat ini"
                         class="h-20 w-20 rounded-xl border border-berry/10 object-contain p-1">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="hapus_logo" value="1"
                               class="h-5 w-5 rounded border-berry/30 text-strawberry focus:ring-strawberry/40">
                        Hapus logo ini
                    </label>
                </div>
            @endif

            <x-input-label for="logo" value="Unggah logo baru" />
            <input id="logo" name="logo" type="file" accept=".jpg,.jpeg,.png"
                   class="mt-1.5 block w-full text-sm text-ink/70 file:mr-3 file:min-h-[44px] file:rounded-xl
                          file:border-0 file:bg-berry/10 file:px-4 file:text-sm file:font-bold file:text-berry">
            <x-input-error :messages="$errors->get('logo')" class="mt-2" />
        </x-kartu>

        <x-kartu judul="Rekening penerima setoran">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="bank_nama" value="Nama bank" />
                    <x-text-input id="bank_nama" name="bank_nama" class="mt-1.5"
                                  :value="old('bank_nama', $pengaturan['bank_nama'])" placeholder="mis. BRI" />
                    <x-input-error :messages="$errors->get('bank_nama')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="bank_nomor_rekening" value="Nomor rekening" />
                    <x-text-input id="bank_nomor_rekening" name="bank_nomor_rekening" class="mt-1.5 tabular-nums"
                                  :value="old('bank_nomor_rekening', $pengaturan['bank_nomor_rekening'])" />
                    <x-input-error :messages="$errors->get('bank_nomor_rekening')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="bank_atas_nama" value="Atas nama" />
                    <x-text-input id="bank_atas_nama" name="bank_atas_nama" class="mt-1.5"
                                  :value="old('bank_atas_nama', $pengaturan['bank_atas_nama'])" />
                    <x-input-error :messages="$errors->get('bank_atas_nama')" class="mt-2" />
                </div>
            </div>
        </x-kartu>

        <x-kartu judul="Tagihan">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="jatuh_tempo_hari" value="Jatuh tempo default (hari)" />
                    <x-text-input id="jatuh_tempo_hari" name="jatuh_tempo_hari" type="number" min="0" max="365"
                                  class="mt-1.5 tabular-nums"
                                  :value="old('jatuh_tempo_hari', $pengaturan['jatuh_tempo_hari'])" required />
                    <x-input-error :messages="$errors->get('jatuh_tempo_hari')" class="mt-2" />
                    <p class="mt-1.5 text-xs text-ink/50">Dipakai bila toko tidak punya tempo bayar sendiri.</p>
                </div>

                <div>
                    <x-input-label for="kota_ttd" value="Kota penandatangan" />
                    <x-text-input id="kota_ttd" name="kota_ttd" class="mt-1.5"
                                  :value="old('kota_ttd', $pengaturan['kota_ttd'])" />
                    <x-input-error :messages="$errors->get('kota_ttd')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="nama_penandatangan" value="Nama penandatangan" />
                    <x-text-input id="nama_penandatangan" name="nama_penandatangan" class="mt-1.5"
                                  :value="old('nama_penandatangan', $pengaturan['nama_penandatangan'])" />
                    <x-input-error :messages="$errors->get('nama_penandatangan')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="catatan_tagihan" value="Catatan kaki tagihan" />
                    <textarea id="catatan_tagihan" name="catatan_tagihan" rows="3"
                              class="mt-1.5 block w-full rounded-xl border-berry/20 bg-white px-3 py-2 text-sm shadow-sm
                                     focus:border-berry focus:ring-2 focus:ring-berry/30">{{ old('catatan_tagihan', $pengaturan['catatan_tagihan']) }}</textarea>
                    <x-input-error :messages="$errors->get('catatan_tagihan')" class="mt-2" />
                </div>
            </div>
        </x-kartu>

        <div class="flex flex-wrap gap-3">
            <x-primary-button>Simpan pengaturan</x-primary-button>
            <x-tautan-tombol gaya="kedua" :href="route('beranda')">Batal</x-tautan-tombol>
        </div>
    </form>

</x-app-layout>
