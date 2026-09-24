<x-app-layout title="Buat Tagihan" subtitle="Pilih pengiriman yang sudah direkonsiliasi">

    <div class="mx-auto max-w-3xl space-y-5">

        {{-- Langkah 1: pilih toko --}}
        <x-kartu judul="1. Pilih toko">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div class="min-w-0 flex-1">
                    <x-input-label for="toko" value="Toko" />
                    <x-select id="toko" name="toko" class="mt-1.5" required>
                        <option value="">— Pilih toko —</option>
                        @foreach ($toko as $t)
                            <option value="{{ $t->id }}" @selected($tokoTerpilih?->id === $t->id)>{{ $t->name }}</option>
                        @endforeach
                    </x-select>
                </div>
                <x-primary-button>Tampilkan pengiriman</x-primary-button>
            </form>
        </x-kartu>

        @if ($tokoTerpilih)
            <form method="POST" action="{{ route('tagihan.store') }}"
                  x-data="formTagihan({{ Js::from($pengiriman->map(fn ($k) => [
                      'id' => $k->id,
                      'nomor' => $k->number,
                      'setoran' => $k->items->sum(fn ($i) => $i->qty_sold * ($i->unit_price - $i->fee_per_unit)),
                      'kotor' => $k->items->sum(fn ($i) => $i->qty_sold * $i->unit_price),
                      'fee' => $k->items->sum(fn ($i) => $i->qty_sold * $i->fee_per_unit),
                  ])->values()) }})">
                @csrf
                <input type="hidden" name="store_id" value="{{ $tokoTerpilih->id }}">

                {{-- Langkah 2: pilih pengiriman --}}
                <x-kartu judul="2. Pilih pengiriman yang ditagih"
                         :keterangan="'Pengiriman '.$tokoTerpilih->name.' yang sudah direkonsiliasi dan belum ditagih.'">
                    <x-slot:aksi>
                        <button type="button" @click="pilihSemua()"
                                class="inline-flex min-h-[36px] items-center rounded-xl border border-berry/20 px-3 text-xs font-bold text-berry transition hover:bg-berry/5">
                            Pilih semua
                        </button>
                    </x-slot:aksi>

                    <x-input-error :messages="$errors->get('consignment_ids')" class="mb-3" />

                    @if ($pengiriman->isEmpty())
                        <div class="rounded-xl bg-frost px-4 py-8 text-center">
                            <p class="text-sm font-semibold">Belum ada pengiriman yang siap ditagih.</p>
                            <p class="mx-auto mt-1 max-w-sm text-xs leading-relaxed text-ink/55">
                                Pengiriman baru bisa ditagih setelah direkonsiliasi (diisi jumlah terjual, retur, dan rusak).
                            </p>
                            <a href="{{ route('pengiriman.index', ['toko' => $tokoTerpilih->id, 'status' => 'sent']) }}"
                               class="mt-4 inline-flex min-h-[44px] items-center rounded-xl border border-berry/20 px-4 text-sm font-bold text-berry">
                                Lihat pengiriman menunggu rekonsiliasi
                            </a>
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach ($pengiriman as $kirim)
                                @php
                                    $setoranKirim = $kirim->items->sum(fn ($i) => $i->qty_sold * ($i->unit_price - $i->fee_per_unit));
                                @endphp
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-berry/15 p-3 transition hover:bg-frost has-[:checked]:border-berry has-[:checked]:bg-berry/5">
                                    <input type="checkbox" name="consignment_ids[]" value="{{ $kirim->id }}"
                                           x-model.number="dipilih"
                                           class="mt-0.5 h-5 w-5 rounded border-berry/30 text-berry focus:ring-berry/40">

                                    <span class="min-w-0 flex-1">
                                        <span class="flex flex-wrap items-baseline justify-between gap-2">
                                            <span class="text-sm font-bold tabular-nums">{{ $kirim->number }}</span>
                                            <span class="text-sm font-extrabold tabular-nums text-berry">{{ rupiah($setoranKirim) }}</span>
                                        </span>
                                        <span class="mt-0.5 block text-xs text-ink/55">
                                            Kirim {{ tanggal_singkat($kirim->sent_date) }}
                                            &middot; rekonsiliasi {{ tanggal_singkat($kirim->settled_date) }}
                                            &middot; {{ angka($kirim->items->sum('qty_sold')) }} pcs terjual
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <dl class="mt-4 grid grid-cols-3 gap-3 rounded-xl bg-frost p-4">
                            <div>
                                <dt class="text-[11px] text-ink/55">Penjualan kotor</dt>
                                <dd class="text-sm font-extrabold tabular-nums" x-text="rupiah(totalKotor)"></dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-ink/55">Fee toko</dt>
                                <dd class="text-sm font-extrabold tabular-nums text-mango" x-text="rupiah(totalFee)"></dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-ink/55">Total setoran</dt>
                                <dd class="text-base font-extrabold tabular-nums text-berry" x-text="rupiah(totalSetoran)"></dd>
                            </div>
                        </dl>
                    @endif
                </x-kartu>

                @if ($pengiriman->isNotEmpty())
                    {{-- Langkah 3: tanggal --}}
                    <x-kartu judul="3. Tanggal tagihan" class="mt-5">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="invoice_date" value="Tanggal tagihan" />
                                <x-text-input id="invoice_date" name="invoice_date" type="date" class="mt-1.5"
                                              x-model="tanggalTagihan" :value="old('invoice_date', now()->toDateString())" required />
                                <x-input-error :messages="$errors->get('invoice_date')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="due_date" value="Jatuh tempo" />
                                <x-text-input id="due_date" name="due_date" type="date" class="mt-1.5"
                                              x-model="tanggalTempo"
                                              :value="old('due_date', now()->addDays($jatuhTempoHari)->toDateString())" required />
                                <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
                                <p class="mt-1.5 text-xs text-ink/50">
                                    Tempo bayar {{ $tokoTerpilih->name }}: {{ $jatuhTempoHari }} hari.
                                </p>
                            </div>

                            <div class="sm:col-span-2">
                                <x-input-label for="notes" value="Catatan" />
                                <textarea id="notes" name="notes" rows="2"
                                          class="mt-1.5 block w-full rounded-xl border-berry/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-berry focus:ring-2 focus:ring-berry/30">{{ old('notes') }}</textarea>
                                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                            </div>
                        </div>
                    </x-kartu>

                    <div class="mt-5 flex flex-wrap gap-3">
                        <x-primary-button ::disabled="dipilih.length === 0">Buat tagihan</x-primary-button>
                        <x-tautan-tombol gaya="kedua" :href="route('tagihan.index')">Batal</x-tautan-tombol>
                    </div>

                    <p class="mt-3 px-1 text-xs leading-relaxed text-ink/45">
                        Pengiriman yang ditagih akan terkunci dan tidak bisa diubah lagi. Untuk mengoreksi,
                        tagihan harus dibatalkan lebih dulu oleh pemilik.
                    </p>
                @endif
            </form>
        @endif
    </div>

    @push('scripts')
        <script>
            function formTagihan(pengiriman) {
                return {
                    pengiriman,
                    dipilih: [],
                    tanggalTagihan: '{{ old('invoice_date', now()->toDateString()) }}',
                    tanggalTempo: '{{ old('due_date', now()->addDays($jatuhTempoHari ?? 7)->toDateString()) }}',

                    pilihSemua() {
                        this.dipilih = this.dipilih.length === this.pengiriman.length
                            ? []
                            : this.pengiriman.map((k) => k.id);
                    },

                    terpilih() {
                        return this.pengiriman.filter((k) => this.dipilih.includes(k.id));
                    },

                    get totalKotor() { return this.terpilih().reduce((t, k) => t + k.kotor, 0); },
                    get totalFee() { return this.terpilih().reduce((t, k) => t + k.fee, 0); },
                    get totalSetoran() { return this.terpilih().reduce((t, k) => t + k.setoran, 0); },
                };
            }
        </script>
    @endpush

</x-app-layout>
