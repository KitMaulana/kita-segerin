{{-- Saringan periode yang dipakai seluruh halaman laporan. --}}
@php
    $aksi = $jenis ? route('laporan.tampil', $jenis) : route('laporan.index');

    $daftarPreset = [
        'hari-ini' => 'Hari ini',
        'minggu-ini' => 'Minggu ini',
        'bulan-ini' => 'Bulan ini',
        'bulan-lalu' => 'Bulan lalu',
        'tahun-ini' => 'Tahun ini',
        'bebas' => 'Rentang bebas',
    ];
@endphp

<form method="GET" action="{{ $aksi }}" class="space-y-3 rounded-2xl border border-berry/10 bg-white p-4 shadow-sm"
      x-data="{ preset: '{{ $preset }}' }">

    <div class="flex flex-wrap gap-2">
        @foreach ($daftarPreset as $nilai => $label)
            <label class="cursor-pointer">
                <input type="radio" name="periode" value="{{ $nilai }}" x-model="preset" class="peer sr-only">
                <span class="inline-flex min-h-[36px] items-center rounded-xl border border-berry/15 px-3 text-xs font-bold transition
                             peer-checked:border-berry peer-checked:bg-berry peer-checked:text-white hover:bg-frost">
                    {{ $label }}
                </span>
            </label>
        @endforeach
    </div>

    <div class="grid gap-3 sm:grid-cols-3" x-show="preset === 'bebas'" x-cloak>
        <div>
            <x-input-label for="dari" value="Dari tanggal" class="text-xs" />
            <x-text-input id="dari" name="dari" type="date" class="mt-1" :value="$dari->toDateString()" />
        </div>
        <div>
            <x-input-label for="sampai" value="Sampai tanggal" class="text-xs" />
            <x-text-input id="sampai" name="sampai" type="date" class="mt-1" :value="$sampai->toDateString()" />
        </div>
    </div>

    @if (($pakaiToko ?? false) && isset($daftarToko))
        <div class="sm:max-w-xs">
            <x-input-label for="toko" value="Toko" class="text-xs" />
            <x-select id="toko" name="toko" class="mt-1">
                <option value="">Semua toko</option>
                @foreach ($daftarToko as $t)
                    <option value="{{ $t->id }}" @selected(($tokoId ?? null) == $t->id)>{{ $t->name }}</option>
                @endforeach
            </x-select>
        </div>
    @endif

    <div class="flex flex-wrap items-center gap-2 pt-1">
        <x-primary-button>Tampilkan</x-primary-button>

        <span class="text-xs text-ink/55">
            Periode: <span class="font-bold">{{ tanggal_indo($dari) }} – {{ tanggal_indo($sampai) }}</span>
        </span>

        @if ($jenis)
            <span class="ml-auto flex gap-2">
                <a href="{{ route('laporan.pdf', array_merge([$jenis], request()->query())) }}" target="_blank"
                   class="inline-flex min-h-[36px] items-center rounded-xl border border-berry/20 px-3 text-xs font-bold text-berry transition hover:bg-berry/5">
                    Cetak PDF
                </a>
                <a href="{{ route('laporan.excel', array_merge([$jenis], request()->query())) }}"
                   class="inline-flex min-h-[36px] items-center rounded-xl border border-mint/30 px-3 text-xs font-bold text-mint transition hover:bg-mint/5">
                    Ekspor Excel
                </a>
            </span>
        @endif
    </div>
</form>
