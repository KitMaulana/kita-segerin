<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\CashBookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Biaya operasional. Setiap biaya otomatis mencatat uang keluar di buku kas.
 */
class ExpenseController extends Controller
{
    public function __construct(private readonly CashBookService $kas) {}

    public function index(Request $request): View
    {
        $query = Expense::query()
            ->with(['category:id,name', 'user:id,name'])
            ->when($request->string('kategori')->value(), fn ($q, $id) => $q->where('expense_category_id', $id))
            ->when($request->string('cari')->trim()->value(), fn ($q, $c) => $q->where('description', 'like', "%{$c}%"))
            ->when($request->date('dari'), fn ($q, $d) => $q->whereDate('date', '>=', $d))
            ->when($request->date('sampai'), fn ($q, $d) => $q->whereDate('date', '<=', $d));

        $total = (int) (clone $query)->sum('amount');

        $perKategori = (clone $query)
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->groupBy('expense_categories.name')
            ->selectRaw('expense_categories.name as nama, SUM(expenses.amount) as jumlah')
            ->orderByDesc('jumlah')
            ->pluck('jumlah', 'nama');

        return view('biaya.index', [
            'daftar' => $query->latest('date')->latest('id')->paginate(20)->withQueryString(),
            'kategori' => ExpenseCategory::aktif()->orderBy('name')->get(),
            'total' => $total,
            'perKategori' => $perKategori,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('input-transaksi');

        return view('biaya.form', [
            'biaya' => new Expense(['date' => now()->toDateString()]),
            'kategori' => ExpenseCategory::aktif()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('input-transaksi');

        $data = $this->validasi($request);

        $biaya = DB::transaction(function () use ($request, $data) {
            $biaya = Expense::create([
                ...$data,
                'proof_path' => $request->hasFile('proof')
                    ? $request->file('proof')->store('bukti-biaya', 'public')
                    : null,
                'user_id' => auth()->id(),
            ]);

            $this->kas->keluar(
                kategori: 'expense',
                jumlah: $biaya->amount,
                keterangan: $biaya->category->name.': '.$biaya->description,
                tanggal: $biaya->date,
                referensi: $biaya,
            );

            ActivityLog::catat('membuat', 'Mencatat biaya '.rupiah($biaya->amount).' — '.$biaya->description.'.', $biaya);

            return $biaya;
        });

        return redirect()
            ->route('biaya.index')
            ->with('sukses', 'Biaya '.rupiah($biaya->amount).' tercatat dan masuk buku kas.');
    }

    public function edit(Expense $biaya): View
    {
        Gate::authorize('input-transaksi');

        return view('biaya.form', [
            'biaya' => $biaya,
            'kategori' => ExpenseCategory::aktif()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Expense $biaya): RedirectResponse
    {
        Gate::authorize('input-transaksi');

        $data = $this->validasi($request);

        DB::transaction(function () use ($request, $biaya, $data) {
            if ($request->boolean('hapus_bukti') && $biaya->proof_path) {
                Storage::disk('public')->delete($biaya->proof_path);
                $data['proof_path'] = null;
            }

            if ($request->hasFile('proof')) {
                if ($biaya->proof_path) {
                    Storage::disk('public')->delete($biaya->proof_path);
                }

                $data['proof_path'] = $request->file('proof')->store('bukti-biaya', 'public');
            }

            $biaya->update($data);

            // Baris buku kas lama diganti agar tetap cocok dengan biaya ini.
            $this->kas->hapusUntuk($biaya);
            $this->kas->keluar(
                kategori: 'expense',
                jumlah: $biaya->amount,
                keterangan: $biaya->category->name.': '.$biaya->description,
                tanggal: $biaya->date,
                referensi: $biaya,
            );

            ActivityLog::catat('mengubah', 'Mengubah biaya '.rupiah($biaya->amount).' — '.$biaya->description.'.', $biaya);
        });

        return redirect()
            ->route('biaya.index')
            ->with('sukses', 'Biaya berhasil diperbarui.');
    }

    public function destroy(Expense $biaya): RedirectResponse
    {
        Gate::authorize('hapus-transaksi');

        DB::transaction(function () use ($biaya) {
            $this->kas->hapusUntuk($biaya);

            if ($biaya->proof_path) {
                Storage::disk('public')->delete($biaya->proof_path);
            }

            ActivityLog::catat('menghapus', 'Menghapus biaya '.rupiah($biaya->amount).' — '.$biaya->description.'.', $biaya);

            $biaya->delete();
        });

        return redirect()
            ->route('biaya.index')
            ->with('sukses', 'Biaya dihapus beserta catatan kasnya.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request): array
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'amount' => ['required', 'integer', 'min:1', 'max:999999999'],
            'description' => ['required', 'string', 'max:255'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ], attributes: [
            'date' => 'Tanggal',
            'expense_category_id' => 'Kategori biaya',
            'amount' => 'Jumlah',
            'description' => 'Keterangan',
            'proof' => 'Bukti',
        ]);

        // Berkas bukti disimpan terpisah lewat Storage, bukan sebagai kolom.
        unset($data['proof']);

        return $data;
    }
}
