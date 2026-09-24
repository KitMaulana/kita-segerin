<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Pengaturan usaha: identitas, rekening, dan pengaturan tagihan.
 * Hanya bisa dibuka pemilik usaha.
 */
class SettingController extends Controller
{
    public function edit(): View
    {
        return view('pengaturan.edit', ['pengaturan' => Setting::semua()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_usaha' => ['required', 'string', 'max:100'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'hapus_logo' => ['boolean'],
            'bank_nama' => ['nullable', 'string', 'max:50'],
            'bank_nomor_rekening' => ['nullable', 'string', 'max:50'],
            'bank_atas_nama' => ['nullable', 'string', 'max:100'],
            'kota_ttd' => ['nullable', 'string', 'max:50'],
            'nama_penandatangan' => ['nullable', 'string', 'max:100'],
            'jatuh_tempo_hari' => ['required', 'integer', 'min:0', 'max:365'],
            'catatan_tagihan' => ['nullable', 'string', 'max:1000'],
        ], attributes: [
            'nama_usaha' => 'Nama usaha',
            'alamat' => 'Alamat',
            'telepon' => 'Telepon',
            'email' => 'Email',
            'logo' => 'Logo',
            'bank_nama' => 'Nama bank',
            'bank_nomor_rekening' => 'Nomor rekening',
            'bank_atas_nama' => 'Atas nama',
            'kota_ttd' => 'Kota penandatangan',
            'nama_penandatangan' => 'Nama penandatangan',
            'jatuh_tempo_hari' => 'Jatuh tempo default',
            'catatan_tagihan' => 'Catatan kaki tagihan',
        ]);

        DB::transaction(function () use ($request, $data) {
            $logoLama = Setting::ambil('logo_path');

            if ($request->boolean('hapus_logo') && $logoLama) {
                Storage::disk('public')->delete($logoLama);
                $data['logo_path'] = '';
            }

            if ($request->hasFile('logo')) {
                if ($logoLama) {
                    Storage::disk('public')->delete($logoLama);
                }

                $data['logo_path'] = $request->file('logo')->store('logo', 'public');
            }

            unset($data['logo'], $data['hapus_logo']);

            Setting::simpan(array_map(fn ($v) => (string) $v, $data));

            ActivityLog::catat('mengubah', 'Mengubah pengaturan usaha.');
        });

        return redirect()
            ->route('pengaturan.edit')
            ->with('sukses', 'Pengaturan usaha berhasil disimpan.');
    }
}
