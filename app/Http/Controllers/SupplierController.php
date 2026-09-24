<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Models\ActivityLog;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $daftar = Supplier::query()
            ->withCount('products')
            ->when($request->string('cari')->trim()->value(), fn ($q, $cari) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$cari}%")->orWhere('contact_person', 'like', "%{$cari}%")
            ))
            ->when($request->string('status')->value() === 'nonaktif', fn ($q) => $q->where('is_active', false))
            ->when($request->string('status')->value() === 'aktif', fn ($q) => $q->aktif())
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('pemasok.index', ['daftar' => $daftar]);
    }

    public function create(): View
    {
        Gate::authorize('input-transaksi');

        return view('pemasok.form', ['pemasok' => new Supplier(['is_active' => true])]);
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        $pemasok = DB::transaction(function () use ($request) {
            $pemasok = Supplier::create($request->validated());

            ActivityLog::catat('membuat', "Membuat pemasok {$pemasok->name}.", $pemasok);

            return $pemasok;
        });

        return redirect()
            ->route('pemasok.index')
            ->with('sukses', "Pemasok {$pemasok->name} berhasil disimpan.");
    }

    public function show(Supplier $pemasok): View
    {
        $pemasok->load(['products' => fn ($q) => $q->orderBy('name')]);

        $pembelian = $pemasok->purchases()
            ->withCount('items')
            ->latest('purchase_date')
            ->limit(10)
            ->get();

        return view('pemasok.show', ['pemasok' => $pemasok, 'pembelian' => $pembelian]);
    }

    public function edit(Supplier $pemasok): View
    {
        Gate::authorize('input-transaksi');

        return view('pemasok.form', ['pemasok' => $pemasok]);
    }

    public function update(SupplierRequest $request, Supplier $pemasok): RedirectResponse
    {
        DB::transaction(function () use ($request, $pemasok) {
            $pemasok->update($request->validated());

            ActivityLog::catat('mengubah', "Mengubah pemasok {$pemasok->name}.", $pemasok);
        });

        return redirect()
            ->route('pemasok.index')
            ->with('sukses', "Pemasok {$pemasok->name} berhasil diperbarui.");
    }

    /**
     * Pemasok yang sudah punya transaksi hanya boleh dinonaktifkan.
     */
    public function destroy(Supplier $pemasok): RedirectResponse
    {
        Gate::authorize('hapus-transaksi');

        if ($pemasok->punyaTransaksi()) {
            return back()->with(
                'gagal',
                "Pemasok {$pemasok->name} sudah dipakai di produk atau pembelian, jadi hanya bisa dinonaktifkan."
            );
        }

        DB::transaction(function () use ($pemasok) {
            ActivityLog::catat('menghapus', "Menghapus pemasok {$pemasok->name}.", $pemasok);

            $pemasok->delete();
        });

        return redirect()
            ->route('pemasok.index')
            ->with('sukses', "Pemasok {$pemasok->name} berhasil dihapus.");
    }
}
