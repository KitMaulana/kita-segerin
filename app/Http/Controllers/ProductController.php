<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\ProductCostHistory;
use App\Models\Supplier;
use App\Services\DocumentNumber;
use App\Services\FinanceCalculator;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly FinanceCalculator $hitung,
        private readonly StockService $stok,
    ) {}

    public function index(Request $request): View
    {
        $daftar = Product::query()
            ->with('supplier:id,name')
            ->when($request->string('cari')->trim()->value(), fn ($q, $cari) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$cari}%")->orWhere('code', 'like', "%{$cari}%")
            ))
            ->when($request->string('varian')->value(), fn ($q, $v) => $q->where('variant', $v))
            ->when($request->string('status')->value() === 'nonaktif', fn ($q) => $q->where('is_active', false))
            ->when($request->string('status')->value() === 'aktif', fn ($q) => $q->aktif())
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $stokGudang = $this->stok->stokGudangSemua();

        return view('produk.index', [
            'daftar' => $daftar,
            'stokGudang' => $stokGudang,
            'hitung' => $this->hitung,
        ]);
    }

    public function create(DocumentNumber $nomor): View
    {
        Gate::authorize('input-transaksi');

        $produk = new Product([
            'code' => DB::transaction(fn () => $nomor->kodeMaster('products', 'ES')),
            'variant' => 'cup',
            'unit' => 'pcs',
            'default_fee_type' => 'nominal',
            'is_active' => true,
        ]);

        return view('produk.form', ['produk' => $produk, 'pemasok' => $this->daftarPemasok()]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $produk = DB::transaction(function () use ($request) {
            $data = $this->siapkanData($request);

            $produk = Product::create($data);

            // Harga modal awal ikut dicatat supaya riwayat lengkap sejak awal.
            ProductCostHistory::create([
                'product_id' => $produk->id,
                'old_price' => 0,
                'new_price' => $produk->cost_price,
                'source' => 'manual',
                'user_id' => auth()->id(),
            ]);

            ActivityLog::catat('membuat', "Membuat produk {$produk->code} — {$produk->name}.", $produk);

            return $produk;
        });

        return redirect()
            ->route('produk.show', $produk)
            ->with('sukses', "Produk {$produk->name} berhasil disimpan.");
    }

    public function show(Product $produk): View
    {
        $produk->load(['supplier:id,name', 'costHistories.user:id,name']);

        return view('produk.show', [
            'produk' => $produk,
            'ringkasan' => $this->hitung->ringkasanPerPcs(
                $produk->cost_price,
                $produk->default_selling_price,
                $produk->default_fee_type,
                $produk->default_fee_value,
            ),
            'stokGudang' => $this->stok->stokGudang($produk),
            'stokDiToko' => $this->stok->stokDiToko($produk),
        ]);
    }

    public function edit(Product $produk): View
    {
        Gate::authorize('input-transaksi');

        return view('produk.form', ['produk' => $produk, 'pemasok' => $this->daftarPemasok()]);
    }

    public function update(ProductRequest $request, Product $produk): RedirectResponse
    {
        DB::transaction(function () use ($request, $produk) {
            $hargaModalLama = $produk->cost_price;

            $produk->update($this->siapkanData($request, $produk));

            // Setiap perubahan harga modal tercatat di riwayat.
            if ($produk->cost_price !== $hargaModalLama) {
                ProductCostHistory::create([
                    'product_id' => $produk->id,
                    'old_price' => $hargaModalLama,
                    'new_price' => $produk->cost_price,
                    'source' => 'manual',
                    'user_id' => auth()->id(),
                ]);

                ActivityLog::catat(
                    'mengubah',
                    "Mengubah harga modal {$produk->name} dari ".rupiah($hargaModalLama).' menjadi '.rupiah($produk->cost_price).'.',
                    $produk
                );
            }

            ActivityLog::catat('mengubah', "Mengubah produk {$produk->code} — {$produk->name}.", $produk);
        });

        return redirect()
            ->route('produk.show', $produk)
            ->with('sukses', "Produk {$produk->name} berhasil diperbarui.");
    }

    public function destroy(Product $produk): RedirectResponse
    {
        Gate::authorize('hapus-transaksi');

        if ($produk->punyaTransaksi()) {
            return back()->with(
                'gagal',
                "Produk {$produk->name} sudah punya transaksi, jadi hanya bisa dinonaktifkan."
            );
        }

        DB::transaction(function () use ($produk) {
            ActivityLog::catat('menghapus', "Menghapus produk {$produk->code} — {$produk->name}.", $produk);

            if ($produk->photo_path) {
                Storage::disk('public')->delete($produk->photo_path);
            }

            $produk->delete();
        });

        return redirect()
            ->route('produk.index')
            ->with('sukses', "Produk {$produk->name} berhasil dihapus.");
    }

    /**
     * @return array<string, mixed>
     */
    private function siapkanData(ProductRequest $request, ?Product $produk = null): array
    {
        $data = $request->validated();

        if ($request->boolean('hapus_foto') && $produk?->photo_path) {
            Storage::disk('public')->delete($produk->photo_path);
            $data['photo_path'] = null;
        }

        if ($request->hasFile('photo')) {
            if ($produk?->photo_path) {
                Storage::disk('public')->delete($produk->photo_path);
            }

            $data['photo_path'] = $request->file('photo')->store('produk', 'public');
        }

        unset($data['photo'], $data['hapus_foto']);

        return $data;
    }

    private function daftarPemasok()
    {
        return Supplier::aktif()->orderBy('name')->get(['id', 'name']);
    }
}
