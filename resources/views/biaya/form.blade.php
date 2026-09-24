@php $baru = ! $biaya->exists; @endphp

<x-app-layout :title="$baru ? 'Catat Biaya' : 'Ubah Biaya'" subtitle="Biaya otomatis masuk buku kas">

    <form method="POST"
          action="{{ $baru ? route('biaya.store') : route('biaya.update', $biaya) }}"
          enctype="multipart/form-data"
          class="mx-auto max-w-xl space-y-5">
        @csrf
        @unless ($baru) @method('put') @endunless

        <x-kartu judul="Keterangan biaya">
            <div class="space-y-4">
                <div>
                    <x-input-label for="expense_category_id" value="Kategori" />
                    <x-select id="expense_category_id" name="expense_category_id" class="mt-1.5" required>
                        <option value="">— Pilih kategori —</option>
                        @foreach ($kategori as $k)
                            <option value="{{ $k->id }}" @selected(old('expense_category_id', $biaya->expense_category_id) == $k->id)>
                                {{ $k->name }}
                            </option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('expense_category_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="description" value="Keterangan" />
                    <x-text-input id="description" name="description" class="mt-1.5"
                                  :value="old('description', $biaya->description)" required
                                  placeholder="mis. Bensin antar ke 3 toko" />
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="amount" value="Jumlah" />
                        <x-text-input id="amount" name="amount" type="number" min="1" step="1"
                                      class="mt-1.5 tabular-nums" :value="old('amount', $biaya->amount)" required />
                        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="date" value="Tanggal" />
                        <x-text-input id="date" name="date" type="date" class="mt-1.5"
                                      :value="old('date', ($biaya->date ?? now())->toDateString())"
                                      :max="now()->toDateString()" required />
                        <x-input-error :messages="$errors->get('date')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="proof" value="Bukti (opsional)" />
                    @if ($biaya->proof_path)
                        <div class="mt-1.5 flex items-center gap-3">
                            <a href="{{ Storage::url($biaya->proof_path) }}" target="_blank"
                               class="rounded-lg border border-berry/20 px-3 py-2 text-xs font-bold text-berry">Lihat bukti saat ini</a>
                            <label class="flex items-center gap-2 text-xs">
                                <input type="checkbox" name="hapus_bukti" value="1"
                                       class="h-4 w-4 rounded border-berry/30 text-strawberry">
                                Hapus bukti
                            </label>
                        </div>
                    @endif
                    <input id="proof" name="proof" type="file" accept=".jpg,.jpeg,.png,.pdf"
                           class="mt-1.5 block w-full text-sm text-ink/70 file:mr-3 file:min-h-[44px] file:rounded-xl
                                  file:border-0 file:bg-berry/10 file:px-4 file:text-sm file:font-bold file:text-berry">
                    <x-input-error :messages="$errors->get('proof')" class="mt-2" />
                    <p class="mt-1.5 text-xs text-ink/50">JPG, PNG, atau PDF. Maksimal 2 MB.</p>
                </div>
            </div>
        </x-kartu>

        <div class="flex flex-wrap gap-3">
            <x-primary-button>Simpan biaya</x-primary-button>
            <x-tautan-tombol gaya="kedua" :href="route('biaya.index')">Batal</x-tautan-tombol>
        </div>
    </form>

</x-app-layout>
