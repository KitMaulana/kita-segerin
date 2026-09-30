<x-app-layout :title="'Tagihan '.$tagihan->number" :subtitle="$tagihan->store->name">

    <x-slot:actions>
        {{-- Tombol Cetak A4 — membuka invoice PDF di tab baru --}}
        <a href="{{ route('tagihan.cetak', $tagihan) }}"
           target="_blank"
           title="Buka invoice PDF ukuran A4"
           class="inline-flex items-center gap-1.5 rounded-xl bg-berry px-3.5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-berry/85 active:scale-95">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 shrink-0">
                <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 003 3.5v13A1.5 1.5 0 004.5 18h11a1.5 1.5 0 001.5-1.5V7.621a1.5 1.5 0 00-.44-1.06l-4.12-4.122A1.5 1.5 0 0011.378 2H4.5zm4.75 6.75a.75.75 0 011.5 0v2.546l.943-1.048a.75.75 0 111.114 1.004l-2.25 2.5a.75.75 0 01-1.114 0l-2.25-2.5a.75.75 0 111.114-1.004l.943 1.048V8.75z" clip-rule="evenodd" />
            </svg>
            Invoice PDF
        </a>

        {{-- Tombol Struk 58mm --}}
        <a href="{{ route('tagihan.struk', $tagihan) }}"
           target="_blank"
           title="Buka struk untuk printer 58mm"
           class="inline-flex items-center gap-1.5 rounded-xl border border-ink/20 bg-white px-3.5 py-2 text-xs font-bold text-ink/70 shadow-sm transition hover:bg-frost active:scale-95">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 shrink-0">
                <path fill-rule="evenodd" d="M5 2.75C5 1.784 5.784 1 6.75 1h6.5c.966 0 1.75.784 1.75 1.75v3.552c.377.046.752.097 1.126.153A2.212 2.212 0 0118 8.653v4.097A2.25 2.25 0 0115.75 15h-.241l.305 1.984A1.75 1.75 0 0114.084 19H5.915a1.75 1.75 0 01-1.73-2.016L4.492 15H4.25A2.25 2.25 0 012 12.75V8.653c0-1.082.775-2.034 1.874-2.198.374-.056.75-.107 1.126-.153V2.75zM6.5 4.5v-.75a.25.25 0 01.25-.25h6.5a.25.25 0 01.25.25V4.5a49.146 49.146 0 00-7 0zm-.741 8.067l.649 4.214h7.184l.65-4.214a49.752 49.752 0 00-8.483 0z" clip-rule="evenodd" />
            </svg>
            Struk 58mm
        </a>
    </x-slot:actions>

    <div class="mx-auto max-w-3xl space-y-5">

        @if ($tagihan->status === 'cancelled')
            <div class="rounded-xl border border-strawberry/30 bg-strawberry/5 px-4 py-3">
                <p class="text-sm font-bold text-strawberry">Tagihan ini dibatalkan.</p>
                @if ($tagihan->cancel_reason)
                    <p class="mt-1 text-sm text-ink/60">Alasan: {{ $tagihan->cancel_reason }}</p>
                @endif
            </div>
        @elseif ($tagihan->lewatJatuhTempo())
            <div class="rounded-xl border border-strawberry/30 bg-strawberry/5 px-4 py-3 text-sm font-semibold text-strawberry">
                Sudah lewat jatuh tempo {{ abs($tagihan->umurPiutangHari()) }} hari
                (jatuh tempo {{ tanggal_indo($tagihan->due_date) }}).
            </div>
        @endif

        {{-- Ringkasan --}}
        <x-kartu>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold text-ink/50">Total setoran</p>
                    <p class="text-3xl font-extrabold tabular-nums text-berry">{{ rupiah($tagihan->amount_due) }}</p>
                    <p class="mt-1 text-xs capitalize text-ink/55">{{ terbilang($tagihan->amount_due) }}</p>
                </div>

                <div class="text-right">
                    <x-lencana :warna="$tagihan->warnaStatus()">{{ $tagihan->namaStatus() }}</x-lencana>
                    <p class="mt-2 text-xs text-ink/55">Dibayar</p>
                    <p class="text-lg font-extrabold tabular-nums text-mint">{{ rupiah($tagihan->amount_paid) }}</p>
                    @if ($tagihan->sisaTagihan() > 0 && $tagihan->status !== 'cancelled')
                        <p class="mt-1 text-xs font-bold tabular-nums text-mango">
                            Sisa {{ rupiah($tagihan->sisaTagihan()) }}
                        </p>
                    @endif
                </div>
            </div>

            @if ($tagihan->amount_due > 0 && $tagihan->status !== 'cancelled')
                <div class="mt-4 h-2.5 w-full overflow-hidden rounded-full bg-frost">
                    <div class="h-full rounded-full bg-mint transition-all"
                         style="width: {{ min(100, round($tagihan->amount_paid / max(1, $tagihan->amount_due) * 100)) }}%"></div>
                </div>
            @endif

            <dl class="mt-5 grid gap-4 sm:grid-cols-3">
                @foreach ([
                    'Tanggal tagihan' => tanggal_indo($tagihan->invoice_date),
                    'Jatuh tempo' => tanggal_indo($tagihan->due_date),
                    'Periode' => tanggal_singkat($tagihan->period_start).' – '.tanggal_singkat($tagihan->period_end),
                ] as $label => $nilai)
                    <div>
                        <dt class="text-xs font-semibold text-ink/50">{{ $label }}</dt>
                        <dd class="mt-0.5 text-sm font-semibold">{{ $nilai }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($tagihan->store->nomorWhatsapp() && $tagihan->status !== 'cancelled')
                <a href="https://wa.me/{{ $tagihan->store->nomorWhatsapp() }}?text={{ rawurlencode($pesanWhatsapp) }}"
                   target="_blank" rel="noopener"
                   class="mt-5 inline-flex min-h-[44px] items-center gap-2 rounded-xl bg-mint px-5 text-sm font-bold text-white transition hover:bg-mint/90">
                    Kirim via WhatsApp
                </a>
            @endif
        </x-kartu>

        {{-- Rincian --}}
        <x-kartu judul="Rincian setoran" padat>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-berry/10 text-left text-xs uppercase tracking-wide text-ink/50">
                        <tr>
                            <th class="px-4 py-3 font-bold sm:px-5">Produk</th>
                            <th class="px-2 py-3 text-right font-bold">Terjual</th>
                            <th class="px-3 py-3 text-right font-bold">Harga jual</th>
                            <th class="px-3 py-3 text-right font-bold">Fee/pcs</th>
                            <th class="px-3 py-3 text-right font-bold">Setoran/pcs</th>
                            <th class="px-4 py-3 text-right font-bold sm:px-5">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-berry/5">
                        @foreach ($tagihan->items as $baris)
                            <tr>
                                <td class="px-4 py-3 font-semibold sm:px-5">{{ $baris->product_name }}</td>
                                <td class="px-2 py-3 text-right tabular-nums">{{ angka($baris->qty_sold) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ rupiah($baris->unit_price) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums text-mango">{{ rupiah($baris->fee_per_unit) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ rupiah($baris->net_per_unit) }}</td>
                                <td class="px-4 py-3 text-right font-bold tabular-nums sm:px-5">{{ rupiah($baris->subtotal) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-berry/10 bg-frost/60">
                        <tr>
                            <td colspan="5" class="px-4 py-2 text-right text-xs sm:px-5">Total penjualan kotor</td>
                            <td class="px-4 py-2 text-right tabular-nums sm:px-5">{{ rupiah($tagihan->gross_total) }}</td>
                        </tr>
                        <tr>
                            <td colspan="5" class="px-4 py-2 text-right text-xs text-mango sm:px-5">Total fee toko</td>
                            <td class="px-4 py-2 text-right tabular-nums text-mango sm:px-5">− {{ rupiah($tagihan->fee_total) }}</td>
                        </tr>
                        <tr class="font-extrabold">
                            <td colspan="5" class="px-4 py-3 text-right sm:px-5">TOTAL SETORAN</td>
                            <td class="px-4 py-3 text-right text-base tabular-nums text-berry sm:px-5">{{ rupiah($tagihan->amount_due) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-kartu>

        {{-- Pengiriman yang ditagih --}}
        <x-kartu judul="Pengiriman yang ditagih" padat>
            <ul class="divide-y divide-berry/5">
                @foreach ($tagihan->consignments as $kirim)
                    <li>
                        <a href="{{ route('pengiriman.show', $kirim) }}"
                           class="flex items-center justify-between gap-3 px-4 py-3 transition hover:bg-frost sm:px-5">
                            <span class="text-sm font-semibold tabular-nums">{{ $kirim->number }}</span>
                            <span class="text-xs text-ink/50">
                                {{ tanggal_singkat($kirim->sent_date) }} – {{ tanggal_singkat($kirim->settled_date) }}
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-kartu>

        {{-- Pembayaran --}}
        <x-kartu judul="Pembayaran" :keterangan="$tagihan->payments->count().' pembayaran tercatat'" padat>
            @if ($tagihan->payments->isEmpty())
                <p class="px-5 py-6 text-center text-sm text-ink/55">Belum ada pembayaran.</p>
            @else
                <ul class="divide-y divide-berry/5">
                    @foreach ($tagihan->payments as $bayar)
                        <li class="flex items-center gap-3 px-4 py-3 sm:px-5">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-mint/10 text-mint">
                                <x-icon name="kas" class="h-4.5 w-4.5" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold tabular-nums">{{ rupiah($bayar->amount) }}</p>
                                <p class="text-xs text-ink/50">
                                    {{ $bayar->number }} &middot; {{ tanggal_indo($bayar->paid_at) }}
                                    &middot; {{ $bayar->namaMetode() }}
                                    @if ($bayar->reference) &middot; {{ $bayar->reference }} @endif
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                @if ($bayar->proof_path)
                                    <a href="{{ Storage::url($bayar->proof_path) }}" target="_blank"
                                       class="rounded-lg border border-berry/20 px-2.5 py-1.5 text-xs font-bold text-berry">Bukti</a>
                                @endif
                                <a href="{{ route('pembayaran.kuitansi', $bayar) }}" target="_blank"
                                   class="rounded-lg border border-berry/20 px-2.5 py-1.5 text-xs font-bold text-berry">Kuitansi</a>
                                @can('hapus-transaksi')
                                    <form method="POST" action="{{ route('pembayaran.destroy', $bayar) }}"
                                          onsubmit="return confirm('Hapus pembayaran {{ $bayar->number }}?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit"
                                                class="rounded-lg border border-strawberry/25 px-2.5 py-1.5 text-xs font-bold text-strawberry">
                                            Hapus
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-kartu>

        {{-- Catat pembayaran --}}
        @if ($tagihan->sisaTagihan() > 0 && $tagihan->status !== 'cancelled')
            @can('kelola-tagihan')
                <x-kartu judul="Catat pembayaran"
                         :keterangan="'Sisa tagihan '.rupiah($tagihan->sisaTagihan()).'. Pembayaran boleh bertahap.'">
                    <form method="POST" action="{{ route('pembayaran.store', $tagihan) }}"
                          enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">
                        @csrf

                        <div>
                            <x-input-label for="amount" value="Jumlah bayar" />
                            <x-text-input id="amount" name="amount" type="number" min="1"
                                          :max="$tagihan->sisaTagihan()" step="1"
                                          class="mt-1.5 tabular-nums"
                                          :value="old('amount', $tagihan->sisaTagihan())" required />
                            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="paid_at" value="Tanggal bayar" />
                            <x-text-input id="paid_at" name="paid_at" type="date" class="mt-1.5"
                                          :value="old('paid_at', now()->toDateString())"
                                          :max="now()->toDateString()" required />
                            <x-input-error :messages="$errors->get('paid_at')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="method" value="Metode" />
                            <x-select id="method" name="method" class="mt-1.5">
                                @foreach (App\Models\Payment::METODE as $nilai => $label)
                                    <option value="{{ $nilai }}" @selected(old('method') === $nilai)>{{ $label }}</option>
                                @endforeach
                            </x-select>
                            <x-input-error :messages="$errors->get('method')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="reference" value="Nomor referensi (opsional)" />
                            <x-text-input id="reference" name="reference" class="mt-1.5" :value="old('reference')"
                                          placeholder="mis. nomor transaksi transfer" />
                            <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="proof" value="Bukti bayar (opsional)" />
                            <input id="proof" name="proof" type="file" accept=".jpg,.jpeg,.png,.pdf"
                                   class="mt-1.5 block w-full text-sm text-ink/70 file:mr-3 file:min-h-[44px] file:rounded-xl
                                          file:border-0 file:bg-berry/10 file:px-4 file:text-sm file:font-bold file:text-berry">
                            <x-input-error :messages="$errors->get('proof')" class="mt-2" />
                            <p class="mt-1.5 text-xs text-ink/50">JPG, PNG, atau PDF. Maksimal 2 MB.</p>
                        </div>

                        <div class="sm:col-span-2">
                            <x-primary-button>Catat pembayaran</x-primary-button>
                        </div>
                    </form>
                </x-kartu>
            @endcan
        @endif

        {{-- Batalkan --}}
        @can('batalkan-tagihan')
            @if ($tagihan->status !== 'cancelled')
                <x-kartu judul="Batalkan tagihan"
                         keterangan="Pengiriman terkait akan kembali ke status sudah direkonsiliasi dan bisa ditagih ulang.">
                    @if (! $tagihan->bisaDibatalkan())
                        <p class="rounded-xl bg-frost px-4 py-3 text-sm text-ink/60">
                            Tagihan ini sudah ada pembayarannya. Hapus dulu seluruh pembayaran sebelum membatalkan.
                        </p>
                    @else
                        <form method="POST" action="{{ route('tagihan.batalkan', $tagihan) }}"
                              onsubmit="return confirm('Batalkan tagihan {{ $tagihan->number }}?')">
                            @csrf
                            @method('patch')

                            <x-input-label for="cancel_reason" value="Alasan pembatalan (wajib)" />
                            <textarea id="cancel_reason" name="cancel_reason" rows="2" required
                                      class="mt-1.5 block w-full rounded-xl border-berry/20 bg-white px-3 py-2 text-sm shadow-sm focus:border-strawberry focus:ring-2 focus:ring-strawberry/30"
                                      placeholder="mis. salah memilih pengiriman">{{ old('cancel_reason') }}</textarea>
                            <x-input-error :messages="$errors->get('cancel_reason')" class="mt-2" />

                            <x-danger-button class="mt-3">Batalkan tagihan</x-danger-button>
                        </form>
                    @endif
                </x-kartu>
            @endif
        @endcan

        <x-tautan-tombol gaya="kedua" :href="route('tagihan.index')">Kembali ke daftar</x-tautan-tombol>
    </div>

</x-app-layout>
