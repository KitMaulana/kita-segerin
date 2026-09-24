<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Profil milik pengguna yang sedang masuk: ubah nama, email, dan kata sandi.
 * Penghapusan akun tidak disediakan di sini; akun dikelola pemilik usaha
 * lewat menu "Akun Admin" (Tahap 3).
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $pengguna = $request->user();

        $pengguna->fill($request->validated());

        if ($pengguna->isDirty('email')) {
            $pengguna->email_verified_at = null;
        }

        $pengguna->save();

        return redirect()
            ->route('profile.edit')
            ->with('sukses', 'Profil berhasil diperbarui.');
    }
}
