<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConsignmentRequest;
use App\Models\ActivityLog;
use App\Models\Consignment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Store;
use App\Services\DocumentNumber;
use App\Services\FinanceCalculator;
use App\Services\StockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Pengiriman titip jual dan rekonsiliasinya.
 */
class ConsignmentController extends Controller
{
    public function __construct(
        private readonly StockService $stok,
        private readonly FinanceCalculator $hitung,
        private readonly DocumentNumber $nomor,
    ) {}

    public function index(Request $request): View
    {
        $daftar = Consignment::query()
            ->with('store:id,code,name')
            ->withCount('items')
            ->when($request->string('cari')->trim()->value(), fn ($q, $c) => $q->where('number', 'like', "%{$c}%"))
            ->when($request->string('toko')->value(), fn ($q, $id) => $q->where('store_id', $id))
            ->when($request->string('status')->value(), fn ($q, $s) => $q->where('status', $s))
            ->when($request->date('dari'), fn ($q, $d) => $q->whereDate('sent_date', '>=', $d))
            ->when($request->date('sampai'), fn ($q, $d) => $q->whereDate('sent_date', '<=', $d))
            ->latest('sent_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('pengiriman.index', [
            'daftar' => $daftar,
            'toko' => Store::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('input-transaksi');

        return view('pengiriman.form', [
            'toko' => Store::aktif()->orderBy('name')->get(['id', 'name']),
            'produk' => Product::aktif()->orderBy('name')->get(['id', 'code', 'name', 'unit']),
            'stokGudang' => $this->stok->stokGudangSemua(),
            'tokoTerpilih' => $request->integer('toko') ?: null,
        ]);
    }

    public function store(ConsignmentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $pengiriman = DB::transaction(function () use ($data) {
            $toko = Store::findOrFail($data['store_id']);

            $pengiriman = Consignment::create([
                'number' => $this->nomor->pengiriman($data['sent_date']),
                'store_id' => $toko->id,
                'sent_date' => $data['sent_date'],
                'status' => 'sent',
                'notes' => $data['notes'] ?? null,
                'user_id' => auth()->id(),
            ]);

            foreach ($data['items'] as $baris) {
                $produk = Product::findOrFail($baris['product_id']);

                // Snapshot harga: perubahan harga nanti tidak mengubah kiriman ini.
                $harga = $this->hitung->resolvePrice($toko, $produk, $pengiriman->sent_date);

                $pengiriman->items()->create([
                    'product_id' => $produk->id,
                    'qty_sent' => $baris['qty_sent'],
                    'unit_cost' => $harga['unit_cost'],
                    'unit_price' => $harga['unit_price'],
                    'fee_per_unit' => $harga['fee_per_unit'],
                ]);

                // Stok gudang berkurang.
                $this->stok->catat(
                    product: $produk,
                    jenis: 'consignment_out',
                    qty: -$baris['qty_sent'],
                    tanggal: $pengiriman->sent_date,
                    referensi: $pengiriman,
                    catatan: "Pengiriman {$pengiriman->number} ke {$toko->name}",
                );
            }

            ActivityLog::catat('membuat', "Membuat pengiriman {$pengiriman->number} ke {$toko->name}.", $pengiriman);

            return $pengiriman;
        });

        return redirect()
            ->route('pengiriman.show', $pengiriman)
            ->with('sukses', "Pengiriman {$pengiriman->number} tersimpan. Stok gudang sudah berkurang.");
    }

    public function show(Consignment $pengiriman): View
    {
        $pengiriman->load(['store', 'user:id,name', 'invoice', 'items.product:id,code,name,unit']);

        return view('pengiriman.show', [
            'pengiriman' => $pengiriman,
            'baris' => $this->barisHitung($pengiriman),
            'total' => $this->totalHitung($pengiriman),
        ]);
    }

    /**
     * Menyimpan hasil rekonsiliasi: terjual, retur, dan rusak per produk.
     */
    public function rekonsiliasi(Request $request, Consignment $pengiriman): RedirectResponse
    {
        Gate::authorize('input-transaksi');

        if (! $pengiriman->bisaDirekonsiliasi()) {
            return back()->with('gagal', 'Pengiriman ini sudah ditagih atau dibatalkan, jadi tidak bisa diubah.');
        }

        $data = $request->validate([
            'settled_date' => ['required', 'date', 'before_or_equal:today'],
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.qty_sold' => ['required', 'integer', 'min:0'],
            'items.*.qty_returned' => ['required', 'integer', 'min:0'],
            'items.*.qty_damaged' => ['required', 'integer', 'min:0'],
        ], attributes: [
            'settled_date' => 'Tanggal rekonsiliasi',
            'items.*.qty_sold' => 'Jumlah terjual',
            'items.*.qty_returned' => 'Jumlah retur',
            'items.*.qty_damaged' => 'Jumlah rusak',
        ]);

        $itemById = $pengiriman->items->keyBy('id');
        $galat = [];

        foreach ($data['items'] as $i => $isi) {
            $item = $itemById->get($isi['id']);

            if (! $item) {
                $galat["items.{$i}.id"] = 'Baris produk tidak dikenali.';

                continue;
            }

            $sisa = $item->qty_sent - ($isi['qty_sold'] + $isi['qty_returned'] + $isi['qty_damaged']);

            if ($sisa < 0) {
                $galat["items.{$i}.qty_sold"] =
                    "Total terjual, retur, dan rusak {$item->product->name} melebihi jumlah yang dikirim ({$item->qty_sent}).";
            }
        }

        if ($galat) {
            return back()->withInput()->withErrors($galat);
        }

        DB::transaction(function () use ($pengiriman, $data, $itemById) {
            // Mutasi stok lama dari rekonsiliasi sebelumnya dihapus agar tidak dobel.
            $pengiriman->stockMovements()
                ->whereIn('type', ['return_in', 'damaged'])
                ->delete();

            foreach ($data['items'] as $isi) {
                /** @var \App\Models\ConsignmentItem $item */
                $item = $itemById->get($isi['id']);

                $item->update([
                    'qty_sold' => $isi['qty_sold'],
                    'qty_returned' => $isi['qty_returned'],
                    'qty_damaged' => $isi['qty_damaged'],
                ]);

                // Retur kembali ke gudang.
                if ($isi['qty_returned'] > 0) {
                    $this->stok->catat(
                        product: $item->product_id,
                        jenis: 'return_in',
                        qty: $isi['qty_returned'],
                        tanggal: $data['settled_date'],
                        referensi: $pengiriman,
                        catatan: "Retur dari {$pengiriman->store->name} ({$pengiriman->number})",
                    );
                }

                // Rusak hanya dicatat; barangnya sudah keluar gudang saat dikirim.
                if ($isi['qty_damaged'] > 0) {
                    $this->stok->catat(
                        product: $item->product_id,
                        jenis: 'damaged',
                        qty: -$isi['qty_damaged'],
                        tanggal: $data['settled_date'],
                        referensi: $pengiriman,
                        catatan: "Rusak/meleleh di {$pengiriman->store->name} ({$pengiriman->number})",
                    );
                }
            }

            $pengiriman->update([
                'status' => 'settled',
                'settled_date' => $data['settled_date'],
            ]);

            ActivityLog::catat(
                'mengubah',
                "Merekonsiliasi pengiriman {$pengiriman->number} ke {$pengiriman->store->name}.",
                $pengiriman
            );
        });

        $pengiriman->refresh()->load('items');

        return redirect()
            ->route('pengiriman.show', $pengiriman)
            ->with('sukses', 'Rekonsiliasi tersimpan. Setoran '.rupiah($this->totalHitung($pengiriman)['setoran']).' siap ditagih.');
    }

    /**
     * Surat jalan / nota titip dalam bentuk PDF A4.
     */
    public function suratJalan(Consignment $pengiriman): Response
    {
        $pengiriman->load(['store', 'items.product:id,code,name,unit']);

        $pdf = Pdf::loadView('pengiriman.cetak.surat-jalan', [
            'pengiriman' => $pengiriman,
            'pengaturan' => Setting::semua(),
        ])->setPaper('a4');

        return $pdf->stream("Surat-Jalan-{$this->namaBerkas($pengiriman->number)}.pdf");
    }

    /**
     * Versi struk 58 mm untuk printer termal, dicetak lewat CSS print peramban.
     */
    public function struk(Consignment $pengiriman): View
    {
        $pengiriman->load(['store', 'items.product:id,code,name,unit']);

        return view('pengiriman.cetak.struk', [
            'pengiriman' => $pengiriman,
            'pengaturan' => Setting::semua(),
        ]);
    }

    public function destroy(Consignment $pengiriman): RedirectResponse
    {
        Gate::authorize('hapus-transaksi');

        if ($pengiriman->status === 'invoiced') {
            return back()->with('gagal', 'Pengiriman yang sudah ditagih tidak bisa dihapus. Batalkan tagihannya lebih dulu.');
        }

        DB::transaction(function () use ($pengiriman) {
            $pengiriman->stockMovements()->delete();

            ActivityLog::catat('menghapus', "Menghapus pengiriman {$pengiriman->number}.", $pengiriman);

            $pengiriman->delete();
        });

        return redirect()
            ->route('pengiriman.index')
            ->with('sukses', "Pengiriman {$pengiriman->number} dihapus beserta mutasi stoknya.");
    }

    /**
     * Hitungan per baris produk pada satu pengiriman.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function barisHitung(Consignment $pengiriman)
    {
        return $pengiriman->items->map(function ($item) {
            return [
                'item' => $item,
                'qty_kirim' => $item->qty_sent,
                'qty_terjual' => $item->qty_sold,
                'qty_retur' => $item->qty_returned,
                'qty_rusak' => $item->qty_damaged,
            ] + $item->hitung();
        });
    }

    /**
     * @return array<string, int|float>
     */
    private function totalHitung(Consignment $pengiriman): array
    {
        return $this->hitung->jumlahkanBaris($this->barisHitung($pengiriman));
    }

    private function namaBerkas(string $nomor): string
    {
        return str_replace('/', '-', $nomor);
    }
}
