<x-app-layout title="Penyesuaian Stok" subtitle="Menyamakan catatan dengan hitungan fisik di gudang">

    <form method="POST" action="{{ route('stok.penyesuaian.store') }}"
          class="mx-auto max-w-xl space-y-5"
          x-data="{
              produkId: '{{ old('product_id') }}',
              stokBaru: {{ (int) old('stok_baru', 0) }},
              stok: {{ Js::from($stokGudang) }},
              daftar: {{ Js::from($produk) }},

              get stokSekarang() { return this.stok[this.produkId] ?? 0; },
              get selisih() { return (Number(this.stokBaru) || 0) - this.stokSekarang; },
              get satuan() {
                  const p = this.daftar.find((x) => String(x.id) === String(this.produkId));
                  return p ? p.unit : 'pcs';
              },
          }">
        @csrf

        <x-kartu judul="Produk yang disesuaikan">
            <div class="space-y-4">
                <div>
                    <x-input-label for="product_id" value="Produk" />
                    <x-select id="product_id" name="product_id" class="mt-1.5" x-model="produkId" required>
                        <option value="">— Pilih produk —</option>
                        @foreach ($produk as $p)
                            <option value="{{ $p->id }}" @selected(old('product_id') == $p->id)>
                                {{ $p->code }} — {{ $p->name }}
                            </option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('product_id')" class="mt-2" />
                </div>

                <div x-show="produkId" x-cloak class="rounded-xl bg-frost px-4 py-3">
                    <p class="text-xs text-ink/55">Stok tercatat saat ini</p>
                    <p class="text-2xl font-extrabold tabular-nums">
                        <span x-text="stokSekarang"></span>
                        <span class="text-base font-bold text-ink/50" x-text="satuan"></span>
                    </p>
                </div>

                <div>
                    <x-input-label for="stok_baru" value="Stok sebenarnya (hasil hitung fisik)" />
                    <x-text-input id="stok_baru" name="stok_baru" type="number" min="0" step="1"
                                  class="mt-1.5 tabular-nums" x-model.number="stokBaru"
                                  :value="old('stok_baru')" required />
                    <x-input-error :messages="$errors->get('stok_baru')" class="mt-2" />
                </div>

                <div x-show="produkId && selisih !== 0" x-cloak
                     class="rounded-xl px-4 py-3"
                     :class="selisih > 0 ? 'bg-mint/10' : 'bg-strawberry/10'">
                    <p class="text-xs" :class="selisih > 0 ? 'text-mint' : 'text-strawberry'">Selisih yang akan dicatat</p>
                    <p class="text-xl font-extrabold tabular-nums"
                       :class="selisih > 0 ? 'text-mint' : 'text-strawberry'"
                       x-text="(selisih > 0 ? '+' : '') + selisih + ' ' + satuan"></p>
                </div>

                <div>
                    <x-input-label for="notes" value="Alasan penyesuaian (wajib)" />
                    <textarea id="notes" name="notes" rows="3" required
                              class="mt-1.5 block w-full rounded-xl border-berry/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-berry focus:ring-2 focus:ring-berry/30"
                              placeholder="mis. 3 pcs meleleh karena freezer mati semalam">{{ old('notes') }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    <p class="mt-1.5 text-xs text-ink/50">Alasan ini tersimpan di riwayat mutasi dan log aktivitas.</p>
                </div>

                <div>
                    <x-input-label for="date" value="Tanggal" />
                    <x-text-input id="date" name="date" type="date" class="mt-1.5"
                                  :value="old('date', now()->toDateString())"
                                  :max="now()->toDateString()" required />
                    <x-input-error :messages="$errors->get('date')" class="mt-2" />
                </div>
            </div>
        </x-kartu>

        <div class="flex flex-wrap gap-3">
            <x-primary-button>Simpan penyesuaian</x-primary-button>
            <x-tautan-tombol gaya="kedua" :href="route('stok.index')">Batal</x-tautan-tombol>
        </div>
    </form>

</x-app-layout>
