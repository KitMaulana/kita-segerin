<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\ProductCostHistory;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\CashBookService;
use App\Services\DocumentNumber;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Pembelian (kulakan) dari pemasok.
 *
 * Menyimpan pembelian otomatis menambah stok gudang (stock_movements +)
 * dan mencatat uang keluar di buku kas.
 */
class PurchaseController extends Controller
{
    public function __construct(
        private readonly StockService $stok,
        private readonly CashBookService $kas,
        private readonly DocumentNumber $nomor,
    ) {}

    public function index(Request $request): View
    {
        $daftar = Purchase::query()
            ->with('supplier:id,name')
            ->withCount('items')
            ->withSum('items', 'qty')
            ->when($request->string('cari')->trim()->value(), fn ($q, $c) => $q->where('number', 'like', "%{$c}%"))
            ->when($request->string('pemasok')->value(), fn ($q, $id) => $q->where('supplier_id', $id))
            ->when($request->date('dari'), fn ($q, $d) => $q->whereDate('purchase_date', '>=', $d))
            ->when($request->date('sampai'), fn ($q, $d) => $q->whereDate('purchase_date', '<=', $d))
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('pembelian.index', [
            'daftar' => $daftar,
            'pemasok' => Supplier::orderBy('name')->get(['id', 'name']),
            'totalPeriode' => (int) (clone $daftar)->getCollection()->sum('total'),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('input-transaksi');

        return view('pembelian.form', [
            'pemasok' => Supplier::aktif()->orderBy('name')->get(['id', 'name']),
            'produk' => $this->daftarProduk(),
        ]);
    }

    public function store(PurchaseRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $pembelian = DB::transaction(function () use ($data) {
            $total = collect($data['items'])->sum(fn ($b) => $b['qty'] * $b['unit_cost']);

            $pembelian = Purchase::create([
                'number' => $this->nomor->pembelian($data['purchase_date']),
                'supplier_id' => $data['supplier_id'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'total' => $total,
                'notes' => $data['notes'] ?? null,
                'update_cost_price' => $data['update_cost_price'] ?? false,
                'user_id' => auth()->id(),
            ]);

            foreach ($data['items'] as $baris) {
                $produk = Product::findOrFail($baris['product_id']);

                $pembelian->items()->create([
                    'product_id' => $produk->id,
                    'qty' => $baris['qty'],
                    'unit_cost' => $baris['unit_cost'],
                    'subtotal' => $baris['qty'] * $baris['unit_cost'],
                ]);

                // Stok gudang bertambah.
                $this->stok->catat(
                    product: $produk,
                    jenis: 'purchase_in',
                    qty: $baris['qty'],
                    tanggal: $pembelian->purchase_date,
                    referensi: $pembelian,
                    catatan: "Pembelian {$pembelian->number}",
                );

                // Opsi: perbarui harga modal produk sekaligus.
                if ($pembelian->update_cost_price && $produk->cost_price !== (int) $baris['unit_cost']) {
                    $lama = $produk->cost_price;

                    $produk->update(['cost_price' => $baris['unit_cost']]);

                    ProductCostHistory::create([
                        'product_id' => $produk->id,
                        'old_price' => $lama,
                        'new_price' => $baris['unit_cost'],
                        'source' => 'purchase',
                        'user_id' => auth()->id(),
                    ]);
                }
            }

            // Uang keluar tercatat di buku kas.
            $this->kas->keluar(
                kategori: 'purchase',
                jumlah: $total,
                keterangan: "Pembelian {$pembelian->number}".($pembelian->supplier ? " dari {$pembelian->supplier->name}" : ''),
                tanggal: $pembelian->purchase_date,
                referensi: $pembelian,
            );

            ActivityLog::catat('membuat', "Mencatat pembelian {$pembelian->number} senilai ".rupiah($total).'.', $pembelian);

            return $pembelian;
        });

        return redirect()
            ->route('pembelian.show', $pembelian)
            ->with('sukses', "Pembelian {$pembelian->number} tersimpan. Stok gudang sudah bertambah.");
    }

    public function show(Purchase $pembelian): View
    {
        $pembelian->load(['supplier', 'user:id,name', 'items.product:id,code,name,unit']);

        return view('pembelian.show', ['pembelian' => $pembelian]);
    }

    /**
     * Menghapus pembelian sekaligus mutasi stok dan baris buku kasnya.
     * Hanya pemilik, dan hanya bila stok gudang masih mencukupi.
     */
    public function destroy(Purchase $pembelian): RedirectResponse
    {
        Gate::authorize('hapus-transaksi');

        foreach ($pembelian->items as $baris) {
            $tersedia = $this->stok->stokGudang($baris->product_id);

            if ($tersedia < $baris->qty) {
                return back()->with(
                    'gagal',
                    "Pembelian tidak bisa dihapus: stok {$baris->product->name} tinggal {$tersedia} pcs, "
                    ."sedangkan pembelian ini menambah {$baris->qty} pcs yang sebagiannya sudah dikirim."
                );
            }
        }

        DB::transaction(function () use ($pembelian) {
            $pembelian->stockMovements()->delete();
            $this->kas->hapusUntuk($pembelian);

            ActivityLog::catat('menghapus', "Menghapus pembelian {$pembelian->number}.", $pembelian);

            $pembelian->delete();
        });

        return redirect()
            ->route('pembelian.index')
            ->with('sukses', "Pembelian {$pembelian->number} dihapus beserta mutasi stok dan catatan kasnya.");
    }

    private function daftarProduk()
    {
        return Product::aktif()
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'unit', 'cost_price']);
    }
}
