<x-app-layout title="Catat Pengiriman" subtitle="Titip jual es krim ke toko">

    <form method="POST" action="{{ route('pengiriman.store') }}"
          class="mx-auto max-w-3xl space-y-5"
          x-data="formPengiriman(
              {{ Js::from($produk) }},
              {{ Js::from($stokGudang) }},
              {{ Js::from(old('items', [['product_id' => '', 'qty_sent' => '']])) }}
          )">
        @csrf

        <x-kartu judul="Tujuan &amp; tanggal">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="store_id" value="Toko tujuan" />
                    <x-select id="store_id" name="store_id" class="mt-1.5" required>
                        <option value="">— Pilih toko —</option>
                        @foreach ($toko as $t)
                            <option value="{{ $t->id }}" @selected(old('store_id', $tokoTerpilih) == $t->id)>{{ $t->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('store_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="sent_date" value="Tanggal kirim" />
                    <x-text-input id="sent_date" name="sent_date" type="date" class="mt-1.5"
                                  :value="old('sent_date', now()->toDateString())"
                                  :max="now()->toDateString()" required />
                    <x-input-error :messages="$errors->get('sent_date')" class="mt-2" />
                </div>
            </div>
        </x-kartu>

        <x-kartu judul="Produk yang dikirim">
            <x-slot:aksi>
                <button type="button" @click="tambahBaris()"
                        class="inline-flex min-h-[36px] items-center rounded-xl border border-berry/20 px-3 text-xs font-bold text-berry transition hover:bg-berry/5">
                    + Tambah baris
                </button>
            </x-slot:aksi>

            <x-input-error :messages="$errors->get('items')" class="mb-3" />

            <div class="space-y-3">
                <template x-for="(baris, i) in items" :key="i">
                    <div class="rounded-xl border border-berry/10 p-3">
                        <div class="grid gap-3 sm:grid-cols-12">
                            <div class="sm:col-span-7">
                                <label class="block text-xs font-bold" :for="'produk-' + i">Produk</label>
                                <select :id="'produk-' + i" :name="'items[' + i + '][product_id]'"
                                        x-model="baris.product_id"
                                        class="mt-1 block min-h-[44px] w-full rounded-xl border-berry/20 bg-white px-3 text-sm shadow-sm focus:border-berry focus:ring-2 focus:ring-berry/30">
                                    <option value="">— Pilih produk —</option>
                                    <template x-for="p in produk" :key="p.id">
                                        <option :value="p.id" x-text="p.code + ' — ' + p.name + ' (stok ' + stokDari(p.id) + ')'"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="sm:col-span-4">
                                <label class="block text-xs font-bold" :for="'qty-' + i">Jumlah kirim</label>
                                <input :id="'qty-' + i" :name="'items[' + i + '][qty_sent]'" type="number" min="1" step="1"
                                       x-model.number="baris.qty_sent"
                                       class="mt-1 block min-h-[44px] w-full rounded-xl border-berry/20 bg-white px-3 text-sm tabular-nums shadow-sm focus:border-berry focus:ring-2 focus:ring-berry/30">
                            </div>

                            <div class="flex items-end sm:col-span-1">
                                <button type="button" @click="hapusBaris(i)" x-show="items.length > 1"
                                        class="grid h-11 w-full place-items-center rounded-xl border border-strawberry/25 text-strawberry transition hover:bg-strawberry/5"
                                        :aria-label="'Hapus baris ' + (i + 1)">&times;</button>
                            </div>
                        </div>

                        {{-- Peringatan bila melebihi stok --}}
                        <p x-show="melebihiStok(baris)" x-cloak
                           class="mt-2 rounded-lg bg-strawberry/10 px-3 py-1.5 text-xs font-semibold text-strawberry">
                            Stok gudang hanya <span x-text="stokDari(baris.product_id)"></span> pcs.
                        </p>

                        @foreach ($errors->get('items.*.qty_sent') as $pesanBaris)
                            @foreach ($pesanBaris as $pesan)
                                <p class="mt-2 rounded-lg bg-strawberry/10 px-3 py-1.5 text-xs font-semibold text-strawberry">{{ $pesan }}</p>
                            @endforeach
                        @endforeach
                    </div>
                </template>
            </div>

            <div class="mt-4 flex items-center justify-between rounded-xl bg-frost px-4 py-3">
                <span class="text-sm font-bold">Total dikirim</span>
                <span class="text-xl font-extrabold tabular-nums text-berry" x-text="totalPcs + ' pcs'"></span>
            </div>
        </x-kartu>

        <x-kartu judul="Catatan">
            <textarea name="notes" rows="2"
                      class="block w-full rounded-xl border-berry/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-berry focus:ring-2 focus:ring-berry/30"
                      placeholder="mis. diterima Ibu Sri, freezer bagian kiri">{{ old('notes') }}</textarea>
            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
        </x-kartu>

        <div class="flex flex-wrap gap-3">
            <x-primary-button>Simpan pengiriman</x-primary-button>
            <x-tautan-tombol gaya="kedua" :href="route('pengiriman.index')">Batal</x-tautan-tombol>
        </div>

        <p class="px-1 text-xs leading-relaxed text-ink/45">
            Harga jual dan fee toko disalin saat pengiriman disimpan, jadi perubahan harga di kemudian hari
            tidak mengubah kiriman ini.
        </p>
    </form>

    @push('scripts')
        <script>
            function formPengiriman(produk, stok, itemsAwal) {
                return {
                    produk,
                    stok,
                    items: itemsAwal.length ? itemsAwal : [{ product_id: '', qty_sent: '' }],

                    tambahBaris() { this.items.push({ product_id: '', qty_sent: '' }); },
                    hapusBaris(i) { this.items.splice(i, 1); },

                    stokDari(id) { return this.stok[id] ?? 0; },

                    melebihiStok(baris) {
                        return baris.product_id && (Number(baris.qty_sent) || 0) > this.stokDari(baris.product_id);
                    },

                    get totalPcs() {
                        return this.items.reduce((t, b) => t + (Number(b.qty_sent) || 0), 0);
                    },
                };
            }
        </script>
    @endpush

</x-app-layout>
