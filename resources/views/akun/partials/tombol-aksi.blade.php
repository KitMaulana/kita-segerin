{{-- Tombol aktifkan/nonaktifkan dan atur ulang kata sandi untuk satu akun. --}}
@php
    $diriSendiri = $akun->is(auth()->user());
@endphp

<form method="POST" action="{{ route('akun.toggle', $akun) }}" class="inline"
      onsubmit="return confirm('{{ $akun->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $akun->name }}?')">
    @csrf
    @method('patch')
    <button type="submit"
            @disabled($diriSendiri && $akun->is_active)
            class="inline-flex min-h-[36px] items-center rounded-xl border px-3 text-xs font-bold transition
                   disabled:cursor-not-allowed disabled:opacity-40
                   {{ $akun->is_active
                        ? 'border-strawberry/25 text-strawberry hover:bg-strawberry/5'
                        : 'border-mint/30 text-mint hover:bg-mint/5' }}"
            @if ($diriSendiri && $akun->is_active) title="Anda tidak bisa menonaktifkan diri sendiri" @endif>
        {{ $akun->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
    </button>
</form>

<form method="POST" action="{{ route('akun.reset-password', $akun) }}" class="inline"
      onsubmit="return confirm('Buat kata sandi baru untuk {{ $akun->name }}? Kata sandi lama langsung tidak berlaku.')">
    @csrf
    @method('patch')
    <button type="submit"
            class="inline-flex min-h-[36px] items-center rounded-xl border border-berry/20 px-3 text-xs font-bold text-berry transition hover:bg-berry/5">
        Reset sandi
    </button>
</form>
