@php $baru = ! $produk->exists; @endphp

<x-app-layout :title="$baru ? 'Tambah Produk' : 'Ubah Produk'" :subtitle="$baru ? null : $produk->name">

    <form method="POST"
          action="{{ $baru ? route('produk.store') : route('produk.update', $produk) }}"
          enctype="multipart/form-data"
          class="mx-auto max-w-2xl space-y-5"
          x-data="{
              modal: {{ (int) old('cost_price', $produk->cost_price ?? 0) }},
              jual: {{ (int) old('default_selling_price', $produk->default_selling_price ?? 0) }},
              jenisFee: '{{ old('default_fee_type', $produk->default_fee_type ?? 'nominal') }}',
              nilaiFee: {{ (int) old('default_fee_value', $produk->default_fee_value ?? 0) }},

              get fee() {
                  const f = this.jenisFee === 'percent'
                      ? Math.round(this.jual * this.nilaiFee / 100)
                      : this.nilaiFee;
                  return Math.min(Math.max(f, 0), this.jual);
              },
              get setoran() { return this.jual - this.fee; },
              get laba() { return this.setoran - this.modal; },
              get margin() { return this.setoran > 0 ? (this.laba / this.setoran * 100) : 0; },
          }">
        @csrf
        @unless ($baru) @method('put') @endunless

        <x-kartu judul="Identitas produk">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="code" value="Kode produk" />
                    <x-text-input id="code" name="code" class="mt-1.5 uppercase tabular-nums"
                                  :value="old('code', $produk->code)" required placeholder="ES-001" />
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="name" value="Nama produk" />
                    <x-text-input id="name" name="name" class="mt-1.5" :value="old('name', $produk->name)"
                                  required autofocus placeholder="mis. Sutaco Taro" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="variant" value="Varian" />
                    <x-select id="variant" name="variant" class="mt-1.5">
                        @foreach (App\Models\Product::VARIAN as $nilai => $label)
                            <option value="{{ $nilai }}" @selected(old('variant', $produk->variant) === $nilai)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('variant')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="unit" value="Satuan" />
                    <x-text-input id="unit" name="unit" class="mt-1.5" :value="old('unit', $produk->unit ?? 'pcs')" required />
                    <x-input-error :messages="$errors->get('unit')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="supplier_id" value="Pemasok (boleh dikosongkan)" />
                    <x-select id="supplier_id" name="supplier_id" class="mt-1.5">
                        <option value="">— Tanpa pemasok —</option>
                        @foreach ($pemasok as $p)
                            <option value="{{ $p->id }}" @selected(old('supplier_id', $produk->supplier_id) == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('supplier_id')" class="mt-2" />
                </div>
            </div>
        </x-kartu>

        <x-kartu judul="Harga &amp; fee toko"
                 keterangan="Harga di sini dipakai sebagai default. Toko tertentu bisa punya harga sendiri.">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="cost_price" value="Harga modal per pcs" />
                    <x-text-input id="cost_price" name="cost_price" type="number" min="0" step="1"
                                  class="mt-1.5 tabular-nums" x-model.number="modal"
                                  :value="old('cost_price', $produk->cost_price ?? 0)" required />
                    <x-input-error :messages="$errors->get('cost_price')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="default_selling_price" value="Harga jual per pcs" />
                    <x-text-input id="default_selling_price" name="default_selling_price" type="number" min="0" step="1"
                                  class="mt-1.5 tabular-nums" x-model.number="jual"
                                  :value="old('default_selling_price', $produk->default_selling_price ?? 0)" required />
                    <x-input-error :messages="$errors->get('default_selling_price')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="default_fee_type" value="Jenis fee toko" />
                    <x-select id="default_fee_type" name="default_fee_type" class="mt-1.5" x-model="jenisFee">
                        <option value="nominal">Nominal (Rupiah per pcs)</option>
                        <option value="percent">Persen dari harga jual</option>
                    </x-select>
                    <x-input-error :messages="$errors->get('default_fee_type')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="default_fee_value" value="Nilai fee" />
                    <div class="relative mt-1.5">
                        <x-text-input id="default_fee_value" name="default_fee_value" type="number" min="0" step="1"
                                      class="tabular-nums pr-10" x-model.number="nilaiFee"
                                      :value="old('default_fee_value', $produk->default_fee_value ?? 0)" required />
                        <span class="pointer-events-none absolute inset-y-0 right-0 grid w-10 place-items-center text-sm font-bold text-ink/40"
                              x-text="jenisFee === 'percent' ? '%' : 'Rp'"></span>
                    </div>
                    <x-input-error :messages="$errors->get('default_fee_value')" class="mt-2" />
                </div>
            </div>

            {{-- Pratinjau langsung --}}
            <div class="mt-5 rounded-xl bg-frost p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-ink/50">Pratinjau per pcs</p>

                <dl class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div>
                        <dt class="text-[11px] text-ink/55">Fee toko</dt>
                        <dd class="text-sm font-extrabold tabular-nums text-mango" x-text="rupiah(fee)"></dd>
                    </div>
                    <div>
                        <dt class="text-[11px] text-ink/55">Setoran</dt>
                        <dd class="text-sm font-extrabold tabular-nums text-berry" x-text="rupiah(setoran)"></dd>
                    </div>
                    <div>
                        <dt class="text-[11px] text-ink/55">Laba</dt>
                        <dd class="text-sm font-extrabold tabular-nums"
                            :class="laba >= 0 ? 'text-mint' : 'text-strawberry'"
                            x-text="rupiah(laba)"></dd>
                    </div>
                    <div>
                        <dt class="text-[11px] text-ink/55">Margin</dt>
                        <dd class="text-sm font-extrabold tabular-nums"
                            :class="laba >= 0 ? 'text-mint' : 'text-strawberry'"
                            x-text="margin.toFixed(1).replace('.', ',') + '%'"></dd>
                    </div>
                </dl>

                <p class="mt-3 text-[11px] leading-relaxed text-ink/45">
                    Setoran = harga jual − fee toko. Laba = setoran − harga modal.
                </p>
            </div>
        </x-kartu>

        <x-kartu judul="Stok &amp; foto">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="min_stock" value="Stok minimum" />
                    <x-text-input id="min_stock" name="min_stock" type="number" min="0" step="1"
                                  class="mt-1.5 tabular-nums" :value="old('min_stock', $produk->min_stock ?? 0)" required />
                    <x-input-error :messages="$errors->get('min_stock')" class="mt-2" />
                    <p class="mt-1.5 text-xs text-ink/50">Diberi tanda peringatan bila stok gudang turun sampai angka ini.</p>
                </div>

                <div>
                    <x-input-label for="photo" value="Foto produk" />
                    @if ($produk->photo_path)
                        <div class="mt-1.5 flex items-center gap-3">
                            <img src="{{ Storage::url($produk->photo_path) }}" alt="Foto {{ $produk->name }}"
                                 class="h-16 w-16 rounded-xl border border-berry/10 object-cover">
                            <label class="flex items-center gap-2 text-xs">
                                <input type="checkbox" name="hapus_foto" value="1"
                                       class="h-4 w-4 rounded border-berry/30 text-strawberry">
                                Hapus foto
                            </label>
                        </div>
                    @endif
                    <input id="photo" name="photo" type="file" accept=".jpg,.jpeg,.png"
                           class="mt-1.5 block w-full text-sm text-ink/70 file:mr-3 file:min-h-[44px] file:rounded-xl
                                  file:border-0 file:bg-berry/10 file:px-4 file:text-sm file:font-bold file:text-berry">
                    <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                </div>
            </div>

            <label class="mt-4 flex min-h-[44px] items-center gap-2.5 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       @checked(old('is_active', $produk->is_active ?? true))
                       class="h-5 w-5 rounded border-berry/30 text-berry focus:ring-berry/40">
                Produk aktif (bisa dipilih saat pembelian dan pengiriman)
            </label>
        </x-kartu>

        <div class="flex flex-wrap gap-3">
            <x-primary-button>Simpan produk</x-primary-button>
            <x-tautan-tombol gaya="kedua" :href="$baru ? route('produk.index') : route('produk.show', $produk)">
                Batal
            </x-tautan-tombol>
        </div>
    </form>

</x-app-layout>
