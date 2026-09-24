<x-app-layout :title="'Pengiriman '.$pengiriman->number" :subtitle="$pengiriman->store->name">

    <x-slot:actions>
        <x-tautan-tombol gaya="kedua" :href="route('pengiriman.surat-jalan', $pengiriman)" target="_blank"
                         class="!min-h-[36px] text-xs">
            Surat jalan
        </x-tautan-tombol>
        <x-tautan-tombol gaya="kedua" :href="route('pengiriman.struk', $pengiriman)" target="_blank"
                         class="!min-h-[36px] text-xs">
            Struk 58mm
        </x-tautan-tombol>
    </x-slot:actions>

    <div class="mx-auto max-w-4xl space-y-5">

        {{-- Status --}}
        <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-berry/10 bg-white px-4 py-3.5 shadow-sm">
            <x-lencana :warna="$pengiriman->warnaStatus()">{{ $pengiriman->namaStatus() }}</x-lencana>

            <span class="text-sm text-ink/60">
                Dikirim {{ tanggal_indo($pengiriman->sent_date) }}
                @if ($pengiriman->settled_date)
                    &middot; direkonsiliasi {{ tanggal_indo($pengiriman->settled_date) }}
                @endif
            </span>

            @if ($pengiriman->invoice)
                <a href="{{ route('tagihan.show', $pengiriman->invoice) }}"
                   class="ml-auto text-sm font-bold text-berry underline underline-offset-2">
                    Lihat tagihan {{ $pengiriman->invoice->number }}
                </a>
            @endif
        </div>

        @if ($pengiriman->terkunci())
            <div class="rounded-xl border border-mango/30 bg-mango/10 px-4 py-3 text-sm font-semibold text-mango">
                Pengiriman ini terkunci karena sudah masuk tagihan. Untuk mengoreksi, batalkan tagihannya lebih dulu.
            </div>
        @endif

        {{-- Ringkasan hitungan --}}
        <x-kartu judul="Ringkasan" keterangan="Dihitung dari harga yang disalin saat pengiriman dibuat">
            <dl class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ([
                    ['Penjualan kotor', rupiah($total['penjualan_kotor']), 'text-ink'],
                    ['Fee toko', rupiah($total['total_fee_toko']), 'text-mango'],
                    ['Setoran', rupiah($total['setoran']), 'text-berry'],
                    ['HPP', rupiah($total['hpp']), 'text-ink/70'],
                    ['Laba kotor', rupiah($total['laba_kotor']), $total['laba_kotor'] >= 0 ? 'text-mint' : 'text-strawberry'],
                    ['Kerugian rusak', rupiah($total['kerugian_rusak']), 'text-strawberry'],
                ] as [$label, $nilai, $warna])
                    <div class="rounded-xl bg-frost px-3 py-2.5">
                        <dt class="text-[11px] leading-tight text-ink/55">{{ $label }}</dt>
                        <dd class="mt-0.5 text-sm font-extrabold tabular-nums {{ $warna }}">{{ $nilai }}</dd>
                    </div>
                @endforeach
            </dl>

            {{-- Tingkat laku: batang berujung bulat seperti es krim stik --}}
            <div class="mt-4">
                <div class="flex items-baseline justify-between">
                    <span class="text-xs font-bold text-ink/60">Tingkat laku</span>
                    <span class="text-sm font-extrabold tabular-nums text-berry">{{ persen($total['tingkat_laku']) }}</span>
                </div>
                <div class="mt-1.5 h-3 w-full overflow-hidden rounded-full bg-frost">
                    <div class="h-full rounded-full bg-berry transition-all"
                         style="width: {{ min(100, $total['tingkat_laku']) }}%"></div>
                </div>
                <p class="mt-1 text-[11px] text-ink/45">
                    {{ angka($total['qty_terjual']) }} terjual dari {{ angka($total['qty_kirim']) }} dikirim
                </p>
            </div>
        </x-kartu>

        {{-- Form rekonsiliasi --}}
        @if ($pengiriman->bisaDirekonsiliasi() && auth()->user()->bisaInput())
            <form method="POST" action="{{ route('pengiriman.rekonsiliasi', $pengiriman) }}"
                  x-data="rekonsiliasi({{ Js::from($baris->map(fn ($b) => [
                      'id' => $b['item']->id,
                      'nama' => $b['item']->product->name,
                      'kode' => $b['item']->product->code,
                      'kirim' => $b['qty_kirim'],
                      'terjual' => $b['qty_terjual'],
                      'retur' => $b['qty_retur'],
                      'rusak' => $b['qty_rusak'],
                      'jual' => $b['item']->unit_price,
                      'fee' => $b['item']->fee_per_unit,
                      'modal' => $b['item']->unit_cost,
                  ])->values()) }})">
                @csrf

                <x-kartu judul="Rekonsiliasi" keterangan="Isi jumlah terjual, retur, dan rusak per produk.">
                    <x-slot:aksi>
                        <button type="button" @click="terjualSemua()"
                                class="inline-flex min-h-[36px] items-center rounded-xl border border-mint/30 px-3 text-xs font-bold text-mint transition hover:bg-mint/5">
                            Terjual semua
                        </button>
                        <button type="button" @click="kosongkan()"
                                class="inline-flex min-h-[36px] items-center rounded-xl border border-berry/20 px-3 text-xs font-bold text-berry transition hover:bg-berry/5">
                            Kosongkan
                        </button>
                    </x-slot:aksi>

                    <div class="mb-4">
                        <x-input-label for="settled_date" value="Tanggal rekonsiliasi" />
                        <x-text-input id="settled_date" name="settled_date" type="date" class="mt-1.5 sm:max-w-xs"
                                      :value="old('settled_date', $pengiriman->settled_date?->toDateString() ?? now()->toDateString())"
                                      :max="now()->toDateString()" required />
                        <x-input-error :messages="$errors->get('settled_date')" class="mt-2" />
                        <p class="mt-1.5 text-xs text-ink/50">Tanggal ini yang dipakai laporan laba rugi.</p>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(b, i) in baris" :key="b.id">
                            <div class="rounded-xl border p-3"
                                 :class="sisa(b) < 0 ? 'border-strawberry/40 bg-strawberry/5' : 'border-berry/10'">
                                <input type="hidden" :name="'items[' + i + '][id]'" :value="b.id">

                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold" x-text="b.nama"></p>
                                        <p class="text-xs text-ink/50">
                                            <span x-text="b.kode"></span> &middot; dikirim <span class="font-bold" x-text="b.kirim"></span> pcs
                                        </p>
                                    </div>
                                    <span class="shrink-0 rounded-lg px-2 py-1 text-xs font-bold tabular-nums"
                                          :class="sisa(b) === 0 ? 'bg-mint/10 text-mint' : (sisa(b) < 0 ? 'bg-strawberry/10 text-strawberry' : 'bg-mango/10 text-mango')">
                                        Sisa: <span x-text="sisa(b)"></span>
                                    </span>
                                </div>

                                <div class="mt-3 grid grid-cols-3 gap-2">
                                    <div>
                                        <label class="block text-[11px] font-bold text-ink/60" :for="'sold-' + i">Terjual</label>
                                        <input :id="'sold-' + i" :name="'items[' + i + '][qty_sold]'" type="number" min="0" step="1"
                                               x-model.number="b.terjual"
                                               class="mt-1 block min-h-[44px] w-full rounded-xl border-berry/20 bg-white px-2.5 text-sm tabular-nums shadow-sm focus:border-berry focus:ring-2 focus:ring-berry/30">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-ink/60" :for="'ret-' + i">Retur</label>
                                        <input :id="'ret-' + i" :name="'items[' + i + '][qty_returned]'" type="number" min="0" step="1"
                                               x-model.number="b.retur"
                                               class="mt-1 block min-h-[44px] w-full rounded-xl border-berry/20 bg-white px-2.5 text-sm tabular-nums shadow-sm focus:border-berry focus:ring-2 focus:ring-berry/30">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-ink/60" :for="'rsk-' + i">Rusak</label>
                                        <input :id="'rsk-' + i" :name="'items[' + i + '][qty_damaged]'" type="number" min="0" step="1"
                                               x-model.number="b.rusak"
                                               class="mt-1 block min-h-[44px] w-full rounded-xl border-berry/20 bg-white px-2.5 text-sm tabular-nums shadow-sm focus:border-berry focus:ring-2 focus:ring-berry/30">
                                    </div>
                                </div>

                                <p class="mt-2 text-right text-xs tabular-nums text-ink/60">
                                    Setoran baris: <span class="font-bold text-berry" x-text="rupiah(setoranBaris(b))"></span>
                                </p>
                            </div>
                        </template>
                    </div>

                    <div class="mt-4 rounded-xl bg-frost p-4">
                        <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div>
                                <dt class="text-[11px] text-ink/55">Penjualan kotor</dt>
                                <dd class="text-sm font-extrabold tabular-nums" x-text="rupiah(totalKotor)"></dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-ink/55">Fee toko</dt>
                                <dd class="text-sm font-extrabold tabular-nums text-mango" x-text="rupiah(totalFee)"></dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-ink/55">Setoran</dt>
                                <dd class="text-sm font-extrabold tabular-nums text-berry" x-text="rupiah(totalSetoran)"></dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-ink/55">Laba kotor</dt>
                                <dd class="text-sm font-extrabold tabular-nums"
                                    :class="totalLaba >= 0 ? 'text-mint' : 'text-strawberry'"
                                    x-text="rupiah(totalLaba)"></dd>
                            </div>
                        </dl>
                    </div>

                    <p x-show="adaSisaNegatif" x-cloak
                       class="mt-3 rounded-xl bg-strawberry/10 px-4 py-2.5 text-sm font-semibold text-strawberry">
                        Ada baris yang jumlahnya melebihi kiriman. Perbaiki dulu sebelum menyimpan.
                    </p>

                    <div class="mt-4">
                        <x-primary-button ::disabled="adaSisaNegatif">Simpan rekonsiliasi</x-primary-button>
                    </div>
                </x-kartu>
            </form>
        @endif

        {{-- Rincian --}}
        <x-kartu judul="Rincian produk" padat>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-berry/10 text-left text-xs uppercase tracking-wide text-ink/50">
                        <tr>
                            <th class="px-4 py-3 font-bold sm:px-5">Produk</th>
                            <th class="px-2 py-3 text-right font-bold">Kirim</th>
                            <th class="px-2 py-3 text-right font-bold">Terjual</th>
                            <th class="px-2 py-3 text-right font-bold">Retur</th>
                            <th class="px-2 py-3 text-right font-bold">Rusak</th>
                            <th class="px-3 py-3 text-right font-bold">Harga jual</th>
                            <th class="px-3 py-3 text-right font-bold">Fee/pcs</th>
                            <th class="px-3 py-3 text-right font-bold">Setoran</th>
                            <th class="px-4 py-3 text-right font-bold sm:px-5">Laba kotor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-berry/5">
                        @foreach ($baris as $b)
                            <tr>
                                <td class="px-4 py-3 sm:px-5">
                                    <span class="font-semibold">{{ $b['item']->product->name }}</span>
                                    <span class="block text-xs text-ink/45">{{ $b['item']->product->code }}</span>
                                </td>
                                <td class="px-2 py-3 text-right tabular-nums">{{ angka($b['qty_kirim']) }}</td>
                                <td class="px-2 py-3 text-right font-semibold tabular-nums text-mint">{{ angka($b['qty_terjual']) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums">{{ angka($b['qty_retur']) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums {{ $b['qty_rusak'] > 0 ? 'text-strawberry' : '' }}">
                                    {{ angka($b['qty_rusak']) }}
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ rupiah($b['item']->unit_price) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums text-mango">{{ rupiah($b['item']->fee_per_unit) }}</td>
                                <td class="px-3 py-3 text-right font-semibold tabular-nums text-berry">{{ rupiah($b['setoran']) }}</td>
                                <td class="px-4 py-3 text-right font-bold tabular-nums sm:px-5 {{ $b['laba_kotor'] >= 0 ? 'text-mint' : 'text-strawberry' }}">
                                    {{ rupiah($b['laba_kotor']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-berry/10 bg-frost/60 font-bold">
                        <tr>
                            <td class="px-4 py-3 sm:px-5">Total</td>
                            <td class="px-2 py-3 text-right tabular-nums">{{ angka($total['qty_kirim']) }}</td>
                            <td class="px-2 py-3 text-right tabular-nums">{{ angka($total['qty_terjual']) }}</td>
                            <td class="px-2 py-3 text-right tabular-nums">{{ angka($total['qty_retur']) }}</td>
                            <td class="px-2 py-3 text-right tabular-nums">{{ angka($total['qty_rusak']) }}</td>
                            <td colspan="2" class="px-3 py-3 text-right text-xs text-ink/50">
                                Fee {{ rupiah($total['total_fee_toko']) }}
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums text-berry">{{ rupiah($total['setoran']) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums sm:px-5 {{ $total['laba_kotor'] >= 0 ? 'text-mint' : 'text-strawberry' }}">
                                {{ rupiah($total['laba_kotor']) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-kartu>

        @if ($pengiriman->notes)
            <x-kartu judul="Catatan">
                <p class="whitespace-pre-line text-sm leading-relaxed">{{ $pengiriman->notes }}</p>
            </x-kartu>
        @endif

        <div class="flex flex-wrap gap-3">
            <x-tautan-tombol gaya="kedua" :href="route('pengiriman.index')">Kembali ke daftar</x-tautan-tombol>

            @if ($pengiriman->status === 'settled' && auth()->user()->bisaInput())
                <x-tautan-tombol :href="route('tagihan.create', ['toko' => $pengiriman->store_id])">
                    Buat tagihan
                </x-tautan-tombol>
            @endif

            @can('hapus-transaksi')
                @if ($pengiriman->status !== 'invoiced')
                    <form method="POST" action="{{ route('pengiriman.destroy', $pengiriman) }}"
                          onsubmit="return confirm('Hapus pengiriman {{ $pengiriman->number }}? Mutasi stoknya ikut dihapus.')">
                        @csrf
                        @method('delete')
                        <x-danger-button>Hapus pengiriman</x-danger-button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    @push('scripts')
        <script>
            function rekonsiliasi(barisAwal) {
                return {
                    baris: barisAwal,

                    sisa(b) {
                        return b.kirim - ((Number(b.terjual) || 0) + (Number(b.retur) || 0) + (Number(b.rusak) || 0));
                    },

                    terjualSemua() {
                        this.baris.forEach((b) => { b.terjual = b.kirim; b.retur = 0; b.rusak = 0; });
                    },

                    kosongkan() {
                        this.baris.forEach((b) => { b.terjual = 0; b.retur = 0; b.rusak = 0; });
                    },

                    setoranBaris(b) {
                        const terjual = Number(b.terjual) || 0;
                        return terjual * b.jual - terjual * b.fee;
                    },

                    get adaSisaNegatif() { return this.baris.some((b) => this.sisa(b) < 0); },

                    get totalKotor() { return this.baris.reduce((t, b) => t + (Number(b.terjual) || 0) * b.jual, 0); },
                    get totalFee() { return this.baris.reduce((t, b) => t + (Number(b.terjual) || 0) * b.fee, 0); },
                    get totalSetoran() { return this.totalKotor - this.totalFee; },
                    get totalLaba() {
                        return this.baris.reduce((t, b) => t + (Number(b.terjual) || 0) * (b.jual - b.fee - b.modal), 0);
                    },
                };
            }
        </script>
    @endpush

</x-app-layout>
