<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Riwayat aksi penting di aplikasi. Hanya bisa dibuka pemilik usaha.
 */
class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $log = ActivityLog::query()
            ->with('user:id,name,username')
            ->when($request->string('pengguna')->value(), fn ($q, $id) => $q->where('user_id', $id))
            ->when($request->string('aksi')->value(), fn ($q, $aksi) => $q->where('action', $aksi))
            ->when($request->date('dari'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->date('sampai'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest('created_at')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('log-aktivitas.index', [
            'daftar' => $log,
            'pengguna' => User::orderBy('name')->get(['id', 'name']),
            'daftarAksi' => ActivityLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
