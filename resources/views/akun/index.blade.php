<x-app-layout title="Akun Admin" subtitle="Kelola siapa saja yang boleh memakai aplikasi">

    <x-slot:actions>
        <x-tautan-tombol :href="route('akun.create')" class="hidden lg:inline-flex">
            Tambah akun
        </x-tautan-tombol>
    </x-slot:actions>

    <div class="space-y-4">

        {{-- Pencarian & saringan --}}
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="min-w-0 flex-1 sm:max-w-xs">
                <x-input-label for="cari" value="Cari akun" class="sr-only" />
                <x-text-input id="cari" name="cari" type="search" :value="request('cari')"
                              placeholder="Cari nama atau username" />
            </div>

            <div class="w-40">
                <x-select name="peran" aria-label="Saring peran">
                    <option value="">Semua peran</option>
                    @foreach (App\Models\User::PERAN as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(request('peran') === $nilai)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </div>

            <x-primary-button>Cari</x-primary-button>

            <x-tautan-tombol :href="route('akun.create')" class="ml-auto lg:hidden">Tambah</x-tautan-tombol>
        </form>

        <x-kartu padat>
            @if ($daftar->isEmpty())
                <x-kosong ikon="akun" judul="Akun tidak ditemukan"
                          aksi-teks="Tambah akun" :aksi-url="route('akun.create')">
                    Belum ada akun yang cocok dengan pencarian Anda.
                </x-kosong>
            @else
                {{-- Tampilan kartu untuk HP --}}
                <ul class="divide-y divide-berry/5 lg:hidden">
                    @foreach ($daftar as $akun)
                        <li class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-bold">{{ $akun->name }}</p>
                                    <p class="truncate text-sm text-ink/55">{{ '@'.$akun->username }}</p>
                                </div>
                                <x-lencana :warna="$akun->is_active ? 'mint' : 'strawberry'">
                                    {{ $akun->is_active ? 'Aktif' : 'Nonaktif' }}
                                </x-lencana>
                            </div>

                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-ink/55">
                                <x-lencana warna="berry">{{ $akun->namaPeran() }}</x-lencana>
                                <span>Masuk terakhir: {{ $akun->last_login_at ? tanggal_indo($akun->last_login_at, true) : 'belum pernah' }}</span>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <x-tautan-tombol gaya="kedua" :href="route('akun.edit', $akun)" class="text-xs">Ubah</x-tautan-tombol>
                                @include('akun.partials.tombol-aksi', ['akun' => $akun])
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- Tampilan tabel untuk layar besar --}}
                <div class="hidden overflow-x-auto lg:block">
                    <table class="w-full text-sm">
                        <thead class="border-b border-berry/10 text-left text-xs uppercase tracking-wide text-ink/50">
                            <tr>
                                <th class="px-5 py-3 font-bold">Nama</th>
                                <th class="px-5 py-3 font-bold">Username</th>
                                <th class="px-5 py-3 font-bold">Peran</th>
                                <th class="px-5 py-3 font-bold">Status</th>
                                <th class="px-5 py-3 font-bold">Masuk terakhir</th>
                                <th class="px-5 py-3 text-right font-bold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-berry/5">
                            @foreach ($daftar as $akun)
                                <tr class="hover:bg-frost/60">
                                    <td class="px-5 py-3 font-semibold">
                                        {{ $akun->name }}
                                        @if ($akun->is(auth()->user()))
                                            <span class="ml-1 text-xs font-normal text-ink/45">(Anda)</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-ink/70">{{ '@'.$akun->username }}</td>
                                    <td class="px-5 py-3"><x-lencana warna="berry">{{ $akun->namaPeran() }}</x-lencana></td>
                                    <td class="px-5 py-3">
                                        <x-lencana :warna="$akun->is_active ? 'mint' : 'strawberry'">
                                            {{ $akun->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </x-lencana>
                                    </td>
                                    <td class="px-5 py-3 text-ink/60">
                                        {{ $akun->last_login_at ? tanggal_indo($akun->last_login_at, true) : '—' }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex justify-end gap-2">
                                            <x-tautan-tombol gaya="kedua" :href="route('akun.edit', $akun)" class="!min-h-[36px] text-xs">
                                                Ubah
                                            </x-tautan-tombol>
                                            @include('akun.partials.tombol-aksi', ['akun' => $akun])
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-kartu>

        {{ $daftar->links() }}

        <p class="px-1 text-xs text-ink/45">
            Aplikasi selalu menyisakan minimal satu pemilik yang aktif. Saat ini ada
            {{ $jumlahOwnerAktif }} pemilik aktif.
        </p>
    </div>

</x-app-layout>
