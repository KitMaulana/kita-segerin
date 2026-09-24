{{-- Halaman sementara untuk menu yang dikerjakan pada tahap berikutnya. --}}
<x-app-layout :title="$judul" :subtitle="$subjudul ?? null">

    <div class="mx-auto max-w-xl rounded-2xl border border-berry/10 bg-white px-6 py-12 text-center shadow-sm">
        <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-frost text-berry">
            <x-icon :name="$ikon ?? 'lainnya'" class="h-8 w-8" />
        </div>

        <h2 class="mt-5 text-lg font-extrabold">{{ $judul }}</h2>
        <p class="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-ink/60">{{ $keterangan }}</p>

        <p class="mt-5 inline-flex items-center gap-2 rounded-full bg-mango/10 px-3 py-1.5 text-xs font-bold text-mango">
            Dikerjakan pada {{ $tahap }}
        </p>

        <div class="mt-7">
            <a href="{{ route('beranda') }}"
               class="inline-flex min-h-[44px] items-center rounded-xl bg-berry px-5 text-sm font-bold text-white transition hover:bg-berry/90
                      focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-berry">
                Kembali ke Beranda
            </a>
        </div>
    </div>

</x-app-layout>
