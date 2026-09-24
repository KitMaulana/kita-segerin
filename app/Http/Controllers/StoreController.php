<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequest;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Store;
use App\Models\StorePrice;
use App\Services\DocumentNumber;
use App\Services\FinanceCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function __construct(private readonly FinanceCalculator $hitung) {}

    public function index(Request $request): View
    {
        $daftar = Store::query()
            ->withCount('consignments')
            ->when($request->string('cari')->trim()->value(), fn ($q, $c) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$c}%")->orWhere('code', 'like', "%{$c}%")
            ))
            ->when($request->string('jenis')->value(), fn ($q, $j) => $q->where('type', $j))
            ->when($request->string('status')->value() === 'nonaktif', fn ($q) => $q->where('is_active', false))
            ->when($request->string('status')->value() === 'aktif', fn ($q) => $q->aktif())
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('toko.index', ['daftar' => $daftar]);
    }

    public function create(DocumentNumber $nomor): View
    {
        Gate::authorize('input-transaksi');

        $toko = new Store([
            'code' => DB::transaction(fn () => $nomor->kodeMaster('stores', 'TK')),
            'type' => 'koperasi',
            'is_active' => true,
        ]);

        return view('toko.form', ['toko' => $toko]);
    }

    public function store(StoreRequest $request): RedirectResponse
    {
        $toko = DB::transaction(function () use ($request) {
            $toko = Store::create($request->validated());

            ActivityLog::catat('membuat', "Membuat toko {$toko->code} — {$toko->name}.", $toko);

            return $toko;
        });

        return redirect()
            ->route('toko.show', $toko)
            ->with('sukses', "Toko {$toko->name} berhasil disimpan.");
    }

    public function show(Store $toko): View
    {
        $pengiriman = $toko->consignments()
            ->withCount('items')
            ->latest('sent_date')
            ->limit(10)
            ->get();

        return view('toko.show', [
            'toko' => $toko,
            'pengiriman' => $pengiriman,
            'piutang' => $toko->piutang(),
            'jumlahTagihanBelumLunas' => $toko->invoices()->belumLunas()->count(),
        ]);
    }

    public function edit(Store $toko): View
    {
        Gate::authorize('input-transaksi');

        return view('toko.form', ['toko' => $toko]);
    }

    public function update(StoreRequest $request, Store $toko): RedirectResponse
    {
        DB::transaction(function () use ($request, $toko) {
            $toko->update($request->validated());

            ActivityLog::catat('mengubah', "Mengubah toko {$toko->code} — {$toko->name}.", $toko);
        });

        return redirect()
            ->route('toko.show', $toko)
            ->with('sukses', "Toko {$toko->name} berhasil diperbarui.");
    }

    public function destroy(Store $toko): RedirectResponse
    {
        Gate::authorize('hapus-transaksi');

        if ($toko->punyaTransaksi()) {
            return back()->with(
                'gagal',
                "Toko {$toko->name} sudah punya pengiriman atau tagihan, jadi hanya bisa dinonaktifkan."
            );
        }

        DB::transaction(function () use ($toko) {
            ActivityLog::catat('menghapus', "Menghapus toko {$toko->code} — {$toko->name}.", $toko);

            $toko->delete();
        });

        return redirect()
            ->route('toko.index')
            ->with('sukses', "Toko {$toko->name} berhasil dihapus.");
    }

    /**
     * Tab "Harga & Fee": daftar semua produk aktif beserta harga yang berlaku
     * untuk toko ini, plus riwayat perubahan harga khusus.
     */
    public function harga(Request $request, Store $toko): View
    {
        $tanggal = $request->date('tanggal') ?? now();

        $produk = Product::aktif()->orderBy('name')->get();

        $baris = $produk->map(function (Product $p) use ($toko, $tanggal) {
            $harga = $this->hitung->resolvePrice($toko, $p, $tanggal);

            return ['produk' => $p] + $harga;
        });

        $riwayat = StorePrice::query()
            ->where('store_id', $toko->id)
            ->with('product:id,code,name')
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('toko.harga', [
            'toko' => $toko,
            'baris' => $baris,
            'riwayat' => $riwayat,
            'tanggal' => Carbon::parse($tanggal),
        ]);
    }

    /**
     * Menyimpan harga khusus toko untuk satu produk.
     */
    public function simpanHarga(Request $request, Store $toko): RedirectResponse
    {
        Gate::authorize('ubah-harga');

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'selling_price' => ['required', 'integer', 'min:0', 'max:999999999'],
            'fee_type' => ['required', 'in:nominal,percent'],
            'fee_value' => ['required', 'integer', 'min:0'],
            'effective_from' => ['required', 'date'],
        ], attributes: [
            'product_id' => 'Produk',
            'selling_price' => 'Harga jual',
            'fee_type' => 'Jenis fee',
            'fee_value' => 'Nilai fee',
            'effective_from' => 'Berlaku mulai',
        ]);

        if ($data['fee_type'] === 'percent' && $data['fee_value'] > 100) {
            return back()->withInput()->withErrors(['fee_value' => 'Fee persen tidak boleh lebih dari 100%.']);
        }

        if ($data['fee_type'] === 'nominal' && $data['fee_value'] > $data['selling_price']) {
            return back()->withInput()->withErrors(['fee_value' => 'Fee nominal tidak boleh melebihi harga jual.']);
        }

        $produk = Product::findOrFail($data['product_id']);

        DB::transaction(function () use ($toko, $produk, $data) {
            StorePrice::updateOrCreate(
                [
                    'store_id' => $toko->id,
                    'product_id' => $produk->id,
                    'effective_from' => $data['effective_from'],
                ],
                [
                    'selling_price' => $data['selling_price'],
                    'fee_type' => $data['fee_type'],
                    'fee_value' => $data['fee_value'],
                ]
            );

            ActivityLog::catat(
                'mengubah',
                "Mengatur harga khusus {$produk->name} di {$toko->name}: ".rupiah($data['selling_price'])
                .', fee '.($data['fee_type'] === 'percent' ? $data['fee_value'].'%' : rupiah($data['fee_value']))
                .', berlaku '.tanggal_indo($data['effective_from']).'.',
                $toko
            );
        });

        return redirect()
            ->route('toko.harga', $toko)
            ->with('sukses', "Harga khusus {$produk->name} tersimpan.");
    }

    /**
     * Menghapus satu harga khusus sehingga toko kembali memakai harga default produk.
     */
    public function hapusHarga(Store $toko, StorePrice $harga): RedirectResponse
    {
        Gate::authorize('ubah-harga');

        abort_unless($harga->store_id === $toko->id, 404);

        DB::transaction(function () use ($toko, $harga) {
            ActivityLog::catat(
                'menghapus',
                "Menghapus harga khusus {$harga->product->name} di {$toko->name} (berlaku ".tanggal_indo($harga->effective_from).').',
                $toko
            );

            $harga->delete();
        });

        return back()->with('sukses', 'Harga khusus dihapus. Toko kembali memakai harga default produk.');
    }
}
