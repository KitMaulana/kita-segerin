<x-app-layout :title="'Harga &amp; Fee — '.$toko->name"
              :subtitle="'Berlaku pada '.tanggal_indo($tanggal)">

    <div class="mx-auto max-w-4xl space-y-5"
         x-data="simulasi({{ Js::from($baris->map(fn ($b) => [
             'id' => $b['produk']->id,
             'nama' => $b['produk']->name,
             'kode' => $b['produk']->code,
             'modal' => $b['unit_cost'],
             'jual' => $b['unit_price'],
             'fee' => $b['fee_per_unit'],
             'setoran' => $b['setoran_per_pcs'],
             'laba' => $b['laba_per_pcs'],
             'sumber' => $b['sumber'],
         ])->values()) }})">

        {{-- Tab --}}
        <nav class="flex gap-1 rounded-xl bg-white p-1 shadow-sm" aria-label="Tab toko">
            <a href="{{ route('toko.show', $toko) }}"
               class="flex-1 rounded-lg px-3 py-2 text-center text-sm font-bold text-ink/60 transition hover:bg-frost">
                Ringkasan
            </a>
            <span class="flex-1 rounded-lg bg-berry px-3 py-2 text-center text-sm font-bold text-white">Harga &amp; Fee</span>
        </nav>

        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="w-52">
                <x-input-label for="tanggal" value="Lihat harga yang berlaku pada" class="text-xs" />
                <x-text-input id="tanggal" name="tanggal" type="date" class="mt-1" :value="$tanggal->toDateString()" />
            </div>
            <x-primary-button>Terapkan</x-primary-button>
        </form>

        {{-- Tabel harga --}}
        <x-kartu judul="Harga per produk"
                 keterangan="Produk bertanda &quot;default&quot; memakai harga bawaan produk." padat>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-berry/10 text-left text-xs uppercase tracking-wide text-ink/50">
                        <tr>
                            <th class="px-4 py-3 font-bold sm:px-5">Produk</th>
                            <th class="px-3 py-3 text-right font-bold">Modal</th>
                            <th class="px-3 py-3 text-right font-bold">Harga jual</th>
                            <th class="px-3 py-3 text-right font-bold">Fee toko</th>
                            <th class="px-3 py-3 text-right font-bold">Setoran/pcs</th>
                            <th class="px-3 py-3 text-right font-bold">Laba/pcs</th>
                            @can('ubah-harga')
                                <th class="px-4 py-3 text-right font-bold sm:px-5">Aksi</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-berry/5">
                        @forelse ($baris as $b)
                            <tr class="hover:bg-frost/60">
                                <td class="px-4 py-3 sm:px-5">
                                    <span class="font-semibold">{{ $b['produk']->name }}</span>
                                    <span class="mt-0.5 flex items-center gap-1.5">
                                        <span class="text-xs text-ink/45">{{ $b['produk']->code }}</span>
                                        <x-lencana :warna="$b['sumber'] === 'khusus' ? 'berry' : 'ink'">
                                            {{ $b['sumber'] === 'khusus' ? 'Khusus toko' : 'Default' }}
                                        </x-lencana>
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums text-ink/60">{{ rupiah($b['unit_cost']) }}</td>
                                <td class="px-3 py-3 text-right font-semibold tabular-nums">{{ rupiah($b['unit_price']) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums text-mango">
                                    {{ rupiah($b['fee_per_unit']) }}
                                    @if ($b['fee_type'] === 'percent')
                                        <span class="block text-[11px] text-ink/40">{{ $b['fee_value'] }}%</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-right font-bold tabular-nums text-berry">{{ rupiah($b['setoran_per_pcs']) }}</td>
                                <td class="px-3 py-3 text-right font-bold tabular-nums {{ $b['laba_per_pcs'] >= 0 ? 'text-mint' : 'text-strawberry' }}">
                                    {{ rupiah($b['laba_per_pcs']) }}
                                </td>
                                @can('ubah-harga')
                                    <td class="px-4 py-3 text-right sm:px-5">
                                        <button type="button"
                                                @click="bukaForm({{ $b['produk']->id }}, {{ $b['unit_price'] }}, '{{ $b['fee_type'] }}', {{ $b['fee_value'] }})"
                                                class="inline-flex min-h-[36px] items-center rounded-xl border border-berry/20 px-3 text-xs font-bold text-berry transition hover:bg-berry/5">
                                            Atur harga
                                        </button>
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-sm text-ink/55">
                                    Belum ada produk aktif. Tambahkan produk lebih dulu.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-kartu>

        {{-- Form harga khusus --}}
        @can('ubah-harga')
            <x-kartu judul="Atur harga khusus toko ini" x-ref="formHarga">
                <form method="POST" action="{{ route('toko.harga.simpan', $toko) }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf

                    <div class="sm:col-span-2">
                        <x-input-label for="product_id" value="Produk" />
                        <x-select id="product_id" name="product_id" x-model="form.product_id" class="mt-1.5" required>
                            <option value="">— Pilih produk —</option>
                            @foreach ($baris as $b)
                                <option value="{{ $b['produk']->id }}">{{ $b['produk']->code }} — {{ $b['produk']->name }}</option>
                            @endforeach
                        </x-select>
                        <x-input-error :messages="$errors->get('product_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="selling_price" value="Harga jual di toko ini" />
                        <x-text-input id="selling_price" name="selling_price" type="number" min="0" step="1"
                                      class="mt-1.5 tabular-nums" x-model.number="form.selling_price" required />
                        <x-input-error :messages="$errors->get('selling_price')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="effective_from" value="Berlaku mulai" />
                        <x-text-input id="effective_from" name="effective_from" type="date" class="mt-1.5"
                                      :value="old('effective_from', now()->toDateString())" required />
                        <x-input-error :messages="$errors->get('effective_from')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="fee_type" value="Jenis fee" />
                        <x-select id="fee_type" name="fee_type" class="mt-1.5" x-model="form.fee_type">
                            <option value="nominal">Nominal (Rupiah per pcs)</option>
                            <option value="percent">Persen dari harga jual</option>
                        </x-select>
                    </div>

                    <div>
                        <x-input-label for="fee_value" value="Nilai fee" />
                        <x-text-input id="fee_value" name="fee_value" type="number" min="0" step="1"
                                      class="mt-1.5 tabular-nums" x-model.number="form.fee_value" required />
                        <x-input-error :messages="$errors->get('fee_value')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2 rounded-xl bg-frost p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-ink/50">Pratinjau per pcs</p>
                        <dl class="mt-2 grid grid-cols-3 gap-3">
                            <div>
                                <dt class="text-[11px] text-ink/55">Fee toko</dt>
                                <dd class="text-sm font-extrabold tabular-nums text-mango" x-text="rupiah(formFee)"></dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-ink/55">Setoran</dt>
                                <dd class="text-sm font-extrabold tabular-nums text-berry" x-text="rupiah(formSetoran)"></dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-ink/55">Laba</dt>
                                <dd class="text-sm font-extrabold tabular-nums"
                                    :class="formLaba >= 0 ? 'text-mint' : 'text-strawberry'"
                                    x-text="rupiah(formLaba)"></dd>
                            </div>
                        </dl>
                    </div>

                    <div class="sm:col-span-2">
                        <x-primary-button>Simpan harga khusus</x-primary-button>
                    </div>
                </form>
            </x-kartu>
        @endcan

        {{-- Simulasi cepat --}}
        <x-kartu judul="Simulasi cepat"
                 keterangan="Masukkan perkiraan jumlah terjual untuk melihat setoran dan laba.">
            <div class="space-y-2">
                <template x-for="p in produk" :key="p.id">
                    <div class="flex items-center gap-3 rounded-xl border border-berry/10 px-3 py-2">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold" x-text="p.nama"></span>
                            <span class="block text-xs text-ink/50">
                                <span x-text="rupiah(p.jual)"></span> &middot; setoran <span x-text="rupiah(p.setoran)"></span>
                            </span>
                        </span>

                        <input type="number" min="0" step="1" x-model.number="jumlah[p.id]"
                               class="w-24 min-h-[44px] rounded-xl border-berry/20 bg-white px-3 text-sm tabular-nums shadow-sm focus:border-berry focus:ring-2 focus:ring-berry/30"
                               :aria-label="'Jumlah terjual ' + p.nama" placeholder="0">
                    </div>
                </template>
            </div>

            <dl class="mt-4 grid grid-cols-2 gap-3 rounded-xl bg-frost p-4 sm:grid-cols-4">
                <div>
                    <dt class="text-[11px] text-ink/55">Penjualan kotor</dt>
                    <dd class="text-base font-extrabold tabular-nums" x-text="rupiah(totalKotor)"></dd>
                </div>
                <div>
                    <dt class="text-[11px] text-ink/55">Fee toko</dt>
                    <dd class="text-base font-extrabold tabular-nums text-mango" x-text="rupiah(totalFee)"></dd>
                </div>
                <div>
                    <dt class="text-[11px] text-ink/55">Setoran</dt>
                    <dd class="text-base font-extrabold tabular-nums text-berry" x-text="rupiah(totalSetoran)"></dd>
                </div>
                <div>
                    <dt class="text-[11px] text-ink/55">Laba</dt>
                    <dd class="text-base font-extrabold tabular-nums"
                        :class="totalLaba >= 0 ? 'text-mint' : 'text-strawberry'"
                        x-text="rupiah(totalLaba)"></dd>
                </div>
            </dl>
        </x-kartu>

        {{-- Riwayat harga --}}
        <x-kartu judul="Riwayat harga khusus toko ini" padat>
            @if ($riwayat->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-ink/55">
                    Belum ada harga khusus. Semua produk memakai harga default.
                </p>
            @else
                <ul class="divide-y divide-berry/5">
                    @foreach ($riwayat as $h)
                        <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">{{ $h->product->name }}</p>
                                <p class="text-xs text-ink/50 tabular-nums">
                                    {{ rupiah($h->selling_price) }}
                                    &middot; fee {{ $h->fee_type === 'percent' ? $h->fee_value.'%' : rupiah($h->fee_value) }}
                                    &middot; berlaku {{ tanggal_indo($h->effective_from) }}
                                </p>
                            </div>

                            @can('ubah-harga')
                                <form method="POST" action="{{ route('toko.harga.hapus', [$toko, $h]) }}"
                                      onsubmit="return confirm('Hapus harga khusus ini? Toko akan kembali memakai harga default produk.')">
                                    @csrf
                                    @method('delete')
                                    <button type="submit"
                                            class="shrink-0 rounded-xl border border-strawberry/25 px-3 py-2 text-xs font-bold text-strawberry transition hover:bg-strawberry/5">
                                        Hapus
                                    </button>
                                </form>
                            @endcan
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-kartu>
    </div>

    @push('scripts')
        <script>
            function simulasi(produk) {
                return {
                    produk,
                    jumlah: {},
                    form: { product_id: '', selling_price: 0, fee_type: 'nominal', fee_value: 0 },

                    bukaForm(id, jual, jenis, nilai) {
                        this.form = { product_id: String(id), selling_price: jual, fee_type: jenis, fee_value: nilai };
                        this.$refs.formHarga?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    },

                    get formFee() {
                        const f = this.form.fee_type === 'percent'
                            ? Math.round(this.form.selling_price * this.form.fee_value / 100)
                            : this.form.fee_value;
                        return Math.min(Math.max(f, 0), this.form.selling_price);
                    },
                    get formSetoran() { return this.form.selling_price - this.formFee; },
                    get formLaba() {
                        const p = this.produk.find((x) => String(x.id) === String(this.form.product_id));
                        return this.formSetoran - (p ? p.modal : 0);
                    },

                    qty(id) { return Number(this.jumlah[id]) || 0; },

                    get totalKotor() { return this.produk.reduce((t, p) => t + this.qty(p.id) * p.jual, 0); },
                    get totalFee() { return this.produk.reduce((t, p) => t + this.qty(p.id) * p.fee, 0); },
                    get totalSetoran() { return this.totalKotor - this.totalFee; },
                    get totalLaba() { return this.produk.reduce((t, p) => t + this.qty(p.id) * p.laba, 0); },
                };
            }
        </script>
    @endpush

</x-app-layout>
