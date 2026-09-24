<x-app-layout title="Catat Pembelian" subtitle="Kulakan es krim dari pemasok">

    <form method="POST" action="{{ route('pembelian.store') }}"
          class="mx-auto max-w-3xl space-y-5"
          x-data="formPembelian({{ Js::from($produk) }}, {{ Js::from(old('items', [['product_id' => '', 'qty' => '', 'unit_cost' => '']])) }})">
        @csrf

        <x-kartu judul="Keterangan pembelian">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="purchase_date" value="Tanggal pembelian" />
                    <x-text-input id="purchase_date" name="purchase_date" type="date" class="mt-1.5"
                                  :value="old('purchase_date', now()->toDateString())"
                                  :max="now()->toDateString()" required />
                    <x-input-error :messages="$errors->get('purchase_date')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="supplier_id" value="Pemasok" />
                    <x-select id="supplier_id" name="supplier_id" class="mt-1.5">
                        <option value="">— Tanpa pemasok —</option>
                        @foreach ($pemasok as $p)
                            <option value="{{ $p->id }}" @selected(old('supplier_id') == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('supplier_id')" class="mt-2" />
                </div>
            </div>
        </x-kartu>

        <x-kartu judul="Produk yang dibeli">
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
                            <div class="sm:col-span-6">
                                <label class="block text-xs font-bold" :for="'produk-' + i">Produk</label>
                                <select :id="'produk-' + i" :name="'items[' + i + '][product_id]'"
                                        x-model="baris.product_id" @change="isiHargaModal(i)"
                                        class="mt-1 block min-h-[44px] w-full rounded-xl border-berry/20 bg-white px-3 text-sm shadow-sm focus:border-berry focus:ring-2 focus:ring-berry/30">
                                    <option value="">— Pilih produk —</option>
                                    <template x-for="p in produk" :key="p.id">
                                        <option :value="p.id" x-text="p.code + ' — ' + p.name"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold" :for="'qty-' + i">Jumlah</label>
                                <input :id="'qty-' + i" :name="'items[' + i + '][qty]'" type="number" min="1" step="1"
                                       x-model.number="baris.qty"
                                       class="mt-1 block min-h-[44px] w-full rounded-xl border-berry/20 bg-white px-3 text-sm tabular-nums shadow-sm focus:border-berry focus:ring-2 focus:ring-berry/30">
                            </div>

                            <div class="sm:col-span-3">
                                <label class="block text-xs font-bold" :for="'modal-' + i">Harga modal satuan</label>
                                <input :id="'modal-' + i" :name="'items[' + i + '][unit_cost]'" type="number" min="0" step="1"
                                       x-model.number="baris.unit_cost"
                                       class="mt-1 block min-h-[44px] w-full rounded-xl border-berry/20 bg-white px-3 text-sm tabular-nums shadow-sm focus:border-berry focus:ring-2 focus:ring-berry/30">
                            </div>

                            <div class="flex items-end sm:col-span-1">
                                <button type="button" @click="hapusBaris(i)" x-show="items.length > 1"
                                        class="grid h-11 w-full place-items-center rounded-xl border border-strawberry/25 text-strawberry transition hover:bg-strawberry/5"
                                        :aria-label="'Hapus baris ' + (i + 1)">
                                    &times;
                                </button>
                            </div>
                        </div>

                        <p class="mt-2 text-right text-xs font-bold tabular-nums text-ink/60">
                            Subtotal: <span class="text-berry" x-text="rupiah(subtotal(baris))"></span>
                        </p>
                    </div>
                </template>
            </div>

            <div class="mt-4 flex items-center justify-between rounded-xl bg-frost px-4 py-3">
                <span class="text-sm font-bold">Total pembelian</span>
                <span class="text-xl font-extrabold tabular-nums text-berry" x-text="rupiah(total)"></span>
            </div>
        </x-kartu>

        <x-kartu judul="Opsi">
            <label class="flex min-h-[44px] items-start gap-2.5 text-sm">
                <input type="hidden" name="update_cost_price" value="0">
                <input type="checkbox" name="update_cost_price" value="1" @checked(old('update_cost_price'))
                       class="mt-0.5 h-5 w-5 rounded border-berry/30 text-berry focus:ring-berry/40">
                <span>
                    <span class="block font-semibold">Perbarui harga modal produk</span>
                    <span class="block text-xs leading-relaxed text-ink/55">
                        Harga modal di data produk diganti dengan harga pada pembelian ini, dan perubahannya dicatat di riwayat harga.
                        Transaksi lama tidak ikut berubah.
                    </span>
                </span>
            </label>

            <div class="mt-4">
                <x-input-label for="notes" value="Catatan" />
                <textarea id="notes" name="notes" rows="2"
                          class="mt-1.5 block w-full rounded-xl border-berry/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-berry focus:ring-2 focus:ring-berry/30">{{ old('notes') }}</textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>
        </x-kartu>

        <div class="flex flex-wrap gap-3">
            <x-primary-button>Simpan pembelian</x-primary-button>
            <x-tautan-tombol gaya="kedua" :href="route('pembelian.index')">Batal</x-tautan-tombol>
        </div>

        <p class="px-1 text-xs text-ink/45">
            Menyimpan pembelian akan menambah stok gudang dan mencatat uang keluar di buku kas.
        </p>
    </form>

    @push('scripts')
        <script>
            function formPembelian(produk, itemsAwal) {
                return {
                    produk,
                    items: itemsAwal.length ? itemsAwal : [{ product_id: '', qty: '', unit_cost: '' }],

                    tambahBaris() {
                        this.items.push({ product_id: '', qty: '', unit_cost: '' });
                    },

                    hapusBaris(i) {
                        this.items.splice(i, 1);
                    },

                    // Harga modal terisi otomatis dari data produk, tetap bisa diubah.
                    isiHargaModal(i) {
                        const p = this.produk.find((x) => String(x.id) === String(this.items[i].product_id));
                        if (p && !this.items[i].unit_cost) {
                            this.items[i].unit_cost = p.cost_price;
                        }
                    },

                    subtotal(baris) {
                        return (Number(baris.qty) || 0) * (Number(baris.unit_cost) || 0);
                    },

                    get total() {
                        return this.items.reduce((t, b) => t + this.subtotal(b), 0);
                    },
                };
            }
        </script>
    @endpush

</x-app-layout>
