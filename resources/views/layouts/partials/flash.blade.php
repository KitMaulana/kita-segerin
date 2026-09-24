{{-- Pesan singkat hasil aksi (berhasil / gagal). --}}
@php
    $pesan = [
        'sukses' => ['teks' => session('sukses'), 'kelas' => 'border-mint/30 bg-mint/10 text-mint'],
        'gagal' => ['teks' => session('gagal'), 'kelas' => 'border-strawberry/30 bg-strawberry/10 text-strawberry'],
        'info' => ['teks' => session('info'), 'kelas' => 'border-berry/20 bg-berry/5 text-berry'],
        'peringatan' => ['teks' => session('peringatan'), 'kelas' => 'border-mango/30 bg-mango/10 text-mango'],
    ];
@endphp

@foreach ($pesan as $jenis => $data)
    @if ($data['teks'])
        <div role="status"
             x-data="{ tampil: true }"
             x-show="tampil"
             x-transition.opacity
             class="mb-4 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm font-semibold {{ $data['kelas'] }}">
            <span class="flex-1">{{ $data['teks'] }}</span>
            <button type="button" @click="tampil = false"
                    class="shrink-0 rounded px-1 text-lg leading-none opacity-60 hover:opacity-100"
                    aria-label="Tutup pesan">&times;</button>
        </div>
    @endif
@endforeach

@if ($errors->any() && ! $errors->has('login'))
    <div role="alert" class="mb-4 rounded-xl border border-strawberry/30 bg-strawberry/10 px-4 py-3 text-sm text-strawberry">
        <p class="font-bold">Ada {{ $errors->count() }} isian yang perlu diperbaiki:</p>
        <ul class="mt-1 list-inside list-disc space-y-0.5">
            @foreach ($errors->all() as $pesanError)
                <li>{{ $pesanError }}</li>
            @endforeach
        </ul>
    </div>
@endif
