<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CashTransaction;
use App\Services\CashBookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Buku kas: seluruh uang masuk dan keluar dengan saldo berjalan.
 */
class CashBookController extends Controller
{
    public function __construct(private readonly CashBookService $kas) {}

    public function index(Request $request): View
    {
        $dari = $request->date('dari') ?? now()->startOfMonth();
        $sampai = $request->date('sampai') ?? now()->endOfMonth();

        $query = CashTransaction::query()
            ->with('user:id,name')
            ->whereBetween('date', [
                Carbon::parse($dari)->toDateString(),
                Carbon::parse($sampai)->toDateString(),
            ])
            ->when($request->string('kategori')->value(), fn ($q, $k) => $q->where('category', $k))
            ->when($request->string('arah')->value(), fn ($q, $a) => $q->where('direction', $a));

        $baris = (clone $query)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        // Saldo berjalan dihitung dari saldo sebelum periode.
        $saldo = $this->kas->saldoSebelum($dari);
        $saldoAwal = $saldo;

        $baris = $baris->map(function (CashTransaction $t) use (&$saldo) {
            $saldo += $t->nilaiBertanda();

            return ['transaksi' => $t, 'saldo' => $saldo];
        })->reverse()->values();

        $ringkasan = $this->kas->ringkasanPeriode($dari, $sampai);

        return view('kas.index', [
            'baris' => $baris,
            'dari' => Carbon::parse($dari),
            'sampai' => Carbon::parse($sampai),
            'saldoAwal' => $saldoAwal,
            'saldoAkhir' => $saldoAwal + $ringkasan['selisih'],
            'totalMasuk' => $ringkasan['masuk'],
            'totalKeluar' => $ringkasan['keluar'],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('input-transaksi');

        return view('kas.form');
    }

    /**
     * Input manual untuk modal pemilik, prive, dan lain-lain.
     * Pembayaran tagihan, pembelian, dan biaya dibuat otomatis oleh menunya masing-masing.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('input-transaksi');

        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'direction' => ['required', 'in:in,out'],
            'category' => ['required', 'in:'.implode(',', CashTransaction::KATEGORI_MANUAL)],
            'amount' => ['required', 'integer', 'min:1', 'max:999999999'],
            'description' => ['required', 'string', 'max:255'],
        ], attributes: [
            'date' => 'Tanggal',
            'direction' => 'Arah',
            'category' => 'Kategori',
            'amount' => 'Jumlah',
            'description' => 'Keterangan',
        ], messages: [
            'category.in' => 'Kategori ini dibuat otomatis oleh menu lain, jadi tidak bisa diinput manual.',
        ]);

        $transaksi = DB::transaction(function () use ($data) {
            $transaksi = $this->kas->catat(
                arah: $data['direction'],
                kategori: $data['category'],
                jumlah: $data['amount'],
                keterangan: $data['description'],
                tanggal: $data['date'],
            );

            ActivityLog::catat(
                'membuat',
                'Mencatat kas '.($data['direction'] === 'in' ? 'masuk' : 'keluar').' '
                .rupiah($data['amount']).' — '.$data['description'].'.',
                $transaksi
            );

            return $transaksi;
        });

        return redirect()
            ->route('kas.index')
            ->with('sukses', 'Transaksi kas '.rupiah($transaksi->amount).' tercatat.');
    }

    public function destroy(CashTransaction $kas): RedirectResponse
    {
        Gate::authorize('hapus-transaksi');

        if ($kas->otomatis()) {
            return back()->with(
                'gagal',
                'Baris ini dibuat otomatis oleh '.$kas->namaKategori().'. Hapus dari menu asalnya supaya data tetap cocok.'
            );
        }

        DB::transaction(function () use ($kas) {
            ActivityLog::catat('menghapus', 'Menghapus transaksi kas '.rupiah($kas->amount).' — '.$kas->description.'.');

            $kas->delete();
        });

        return back()->with('sukses', 'Transaksi kas dihapus.');
    }
}
