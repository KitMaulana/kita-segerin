<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Halaman stok: gudang, titipan di toko, nilai persediaan, dan penyesuaian manual.
 */
class StockController extends Controller
{
    public function __construct(private readonly StockService $stok) {}

    public function index(Request $request): View
    {
        $ringkasan = $this->stok->ringkasanPersediaan(
            termasukNonaktif: $request->boolean('termasuk_nonaktif')
        );

        if ($cari = $request->string('cari')->trim()->value()) {
            $ringkasan = $ringkasan->filter(
                fn ($b) => str_contains(strtolower($b['produk']->name.' '.$b['produk']->code), strtolower($cari))
            )->values();
        }

        if ($request->string('saring')->value() === 'menipis') {
            $ringkasan = $ringkasan->filter(fn ($b) => $b['menipis'])->values();
        }

        return view('stok.index', [
            'ringkasan' => $ringkasan,
            'totalGudang' => (int) $ringkasan->sum('stok_gudang'),
            'totalDiToko' => (int) $ringkasan->sum('stok_di_toko'),
            'totalNilai' => (int) $ringkasan->sum('nilai_total'),
            'jumlahMenipis' => $ringkasan->where('menipis', true)->count(),
        ]);
    }

    /**
     * Riwayat mutasi satu produk.
     */
    public function show(Request $request, Product $produk): View
    {
        $mutasi = StockMovement::query()
            ->where('product_id', $produk->id)
            ->with('user:id,name')
            ->when($request->string('jenis')->value(), fn ($q, $j) => $q->where('type', $j))
            ->latest('date')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('stok.show', [
            'produk' => $produk,
            'mutasi' => $mutasi,
            'stokGudang' => $this->stok->stokGudang($produk),
            'stokDiToko' => $this->stok->stokDiToko($produk),
        ]);
    }

    public function createPenyesuaian(): View
    {
        Gate::authorize('input-transaksi');

        return view('stok.penyesuaian', [
            'produk' => Product::aktif()->orderBy('name')->get(['id', 'code', 'name', 'unit']),
            'stokGudang' => $this->stok->stokGudangSemua(),
        ]);
    }

    public function storePenyesuaian(Request $request): RedirectResponse
    {
        Gate::authorize('input-transaksi');

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'stok_baru' => ['required', 'integer', 'min:0', 'max:1000000'],
            'notes' => ['required', 'string', 'min:5', 'max:255'],
        ], attributes: [
            'product_id' => 'Produk',
            'date' => 'Tanggal',
            'stok_baru' => 'Stok sebenarnya',
            'notes' => 'Alasan penyesuaian',
        ], messages: [
            'notes.required' => 'Alasan penyesuaian wajib diisi supaya selisih stok bisa ditelusuri.',
            'notes.min' => 'Tulis alasan penyesuaian minimal 5 karakter.',
        ]);

        $produk = Product::findOrFail($data['product_id']);
        $stokSekarang = $this->stok->stokGudang($produk);
        $selisih = $data['stok_baru'] - $stokSekarang;

        if ($selisih === 0) {
            return back()
                ->withInput()
                ->with('info', "Stok {$produk->name} sudah sesuai ({$stokSekarang} {$produk->unit}), tidak ada yang perlu disesuaikan.");
        }

        DB::transaction(function () use ($produk, $data, $selisih, $stokSekarang) {
            $this->stok->catat(
                product: $produk,
                jenis: 'adjustment',
                qty: $selisih,
                tanggal: $data['date'],
                catatan: $data['notes'],
            );

            ActivityLog::catat(
                'mengubah',
                "Menyesuaikan stok {$produk->name} dari {$stokSekarang} menjadi {$data['stok_baru']} {$produk->unit}. Alasan: {$data['notes']}",
                $produk
            );
        });

        return redirect()
            ->route('stok.index')
            ->with('sukses', "Stok {$produk->name} disesuaikan menjadi {$data['stok_baru']} {$produk->unit}.");
    }
}
