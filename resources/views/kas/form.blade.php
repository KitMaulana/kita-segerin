<x-app-layout title="Input Kas Manual" subtitle="Modal pemilik, prive, dan penerimaan lain">

    <form method="POST" action="{{ route('kas.store') }}" class="mx-auto max-w-xl space-y-5">
        @csrf

        <x-kartu judul="Transaksi kas">
            <div class="space-y-4">
                <fieldset>
                    <legend class="text-sm font-bold">Arah uang</legend>

                    <div class="mt-2 grid grid-cols-2 gap-3">
                        @foreach ([['in', 'Uang masuk', 'mint'], ['out', 'Uang keluar', 'strawberry']] as [$nilai, $label, $warna])
                            <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-berry/15 px-3 py-3 text-sm font-bold transition
                                          hover:bg-frost has-[:checked]:border-{{ $warna }} has-[:checked]:bg-{{ $warna }}/5 has-[:checked]:text-{{ $warna }}">
                                <input type="radio" name="direction" value="{{ $nilai }}"
                                       @checked(old('direction', 'in') === $nilai)
                                       class="h-5 w-5 border-berry/30 text-berry focus:ring-berry/40">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>

                    <x-input-error :messages="$errors->get('direction')" class="mt-2" />
                </fieldset>

                <div>
                    <x-input-label for="category" value="Kategori" />
                    <x-select id="category" name="category" class="mt-1.5" required>
                        @foreach (App\Models\CashTransaction::KATEGORI_MANUAL as $nilai)
                            <option value="{{ $nilai }}" @selected(old('category') === $nilai)>
                                {{ App\Models\CashTransaction::KATEGORI[$nilai] }}
                            </option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('category')" class="mt-2" />
                    <p class="mt-1.5 text-xs text-ink/50">
                        Pembayaran tagihan, pembelian, dan biaya tercatat otomatis dari menunya masing-masing.
                    </p>
                </div>

                <div>
                    <x-input-label for="description" value="Keterangan" />
                    <x-text-input id="description" name="description" class="mt-1.5" :value="old('description')"
                                  required placeholder="mis. Tambahan modal untuk beli freezer" />
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="amount" value="Jumlah" />
                        <x-text-input id="amount" name="amount" type="number" min="1" step="1"
                                      class="mt-1.5 tabular-nums" :value="old('amount')" required />
                        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="date" value="Tanggal" />
                        <x-text-input id="date" name="date" type="date" class="mt-1.5"
                                      :value="old('date', now()->toDateString())"
                                      :max="now()->toDateString()" required />
                        <x-input-error :messages="$errors->get('date')" class="mt-2" />
                    </div>
                </div>
            </div>
        </x-kartu>

        <div class="flex flex-wrap gap-3">
            <x-primary-button>Simpan transaksi</x-primary-button>
            <x-tautan-tombol gaya="kedua" :href="route('kas.index')">Batal</x-tautan-tombol>
        </div>
    </form>

</x-app-layout>
