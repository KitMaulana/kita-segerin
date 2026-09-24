<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Kelola akun pengguna aplikasi. Hanya bisa dibuka pemilik usaha.
 */
class UserController extends Controller
{
    // Otorisasi 'can:kelola-akun' dipasang pada grup route di routes/web.php.

    public function index(Request $request): View
    {
        $akun = User::query()
            ->when($request->string('cari')->trim()->value(), function ($q, $cari) {
                $q->where(fn ($w) => $w->where('name', 'like', "%{$cari}%")
                    ->orWhere('username', 'like', "%{$cari}%")
                    ->orWhere('email', 'like', "%{$cari}%"));
            })
            ->when($request->string('peran')->value(), fn ($q, $peran) => $q->where('role', $peran))
            ->orderByRaw("CASE role WHEN 'owner' THEN 1 WHEN 'admin' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('akun.index', [
            'daftar' => $akun,
            'jumlahOwnerAktif' => User::jumlahOwnerAktifSelain(),
        ]);
    }

    public function create(): View
    {
        return view('akun.form', ['akun' => new User(['role' => 'admin', 'is_active' => true])]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $akun = DB::transaction(function () use ($request) {
            $akun = User::create($request->validated());

            ActivityLog::catat('membuat', "Membuat akun {$akun->username} sebagai {$akun->namaPeran()}.", $akun);

            return $akun;
        });

        return redirect()
            ->route('akun.index')
            ->with('sukses', "Akun {$akun->name} berhasil dibuat.");
    }

    public function edit(User $akun): View
    {
        return view('akun.form', ['akun' => $akun]);
    }

    public function update(UserRequest $request, User $akun): RedirectResponse
    {
        $data = $request->validated();

        // Kata sandi hanya diganti bila diisi.
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $this->pastikanMasihAdaOwnerAktif($akun, $data['role'], $data['is_active']);

        DB::transaction(function () use ($akun, $data) {
            $sebelum = ['role' => $akun->role, 'is_active' => $akun->is_active];

            $akun->update($data);

            $catatan = [];

            if ($sebelum['role'] !== $akun->role) {
                $catatan[] = 'peran menjadi '.$akun->namaPeran();
            }

            if ($sebelum['is_active'] !== $akun->is_active) {
                $catatan[] = $akun->is_active ? 'diaktifkan' : 'dinonaktifkan';
            }

            ActivityLog::catat(
                'mengubah',
                "Mengubah akun {$akun->username}".($catatan ? ' ('.implode(', ', $catatan).')' : '').'.',
                $akun
            );
        });

        return redirect()
            ->route('akun.index')
            ->with('sukses', "Akun {$akun->name} berhasil diperbarui.");
    }

    /**
     * Mengaktifkan atau menonaktifkan akun lewat tombol cepat di daftar.
     */
    public function toggle(User $akun): RedirectResponse
    {
        $this->pastikanMasihAdaOwnerAktif($akun, $akun->role, ! $akun->is_active);

        DB::transaction(function () use ($akun) {
            $akun->update(['is_active' => ! $akun->is_active]);

            ActivityLog::catat(
                'mengubah',
                ($akun->is_active ? 'Mengaktifkan' : 'Menonaktifkan')." akun {$akun->username}.",
                $akun
            );
        });

        return back()->with(
            'sukses',
            "Akun {$akun->name} ".($akun->is_active ? 'diaktifkan.' : 'dinonaktifkan.')
        );
    }

    /**
     * Membuat kata sandi sementara baru dan menampilkannya sekali kepada pemilik.
     */
    public function resetPassword(User $akun): RedirectResponse
    {
        $sandiBaru = Str::lower(Str::random(10));

        DB::transaction(function () use ($akun, $sandiBaru) {
            $akun->forceFill([
                'password' => Hash::make($sandiBaru),
                'remember_token' => null,
            ])->save();

            ActivityLog::catat('mengubah', "Mengatur ulang kata sandi akun {$akun->username}.", $akun);
        });

        return back()->with('sukses', "Kata sandi baru untuk {$akun->name}: {$sandiBaru} — catat sekarang, tidak ditampilkan lagi.");
    }

    /**
     * Memastikan selalu ada minimal satu pemilik aktif.
     *
     * @throws ValidationException
     */
    private function pastikanMasihAdaOwnerAktif(User $akun, string $peranBaru, bool $aktifBaru): void
    {
        $masihOwnerAktif = $peranBaru === 'owner' && $aktifBaru;

        if ($masihOwnerAktif || User::jumlahOwnerAktifSelain($akun->id) > 0) {
            return;
        }

        $pesan = $akun->is(auth()->user())
            ? 'Anda tidak bisa menonaktifkan atau menurunkan peran diri sendiri karena tidak ada pemilik aktif lain.'
            : 'Harus selalu ada minimal satu pemilik yang aktif.';

        throw ValidationException::withMessages(['role' => $pesan]);
    }
}
