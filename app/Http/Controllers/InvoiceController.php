<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Consignment;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\Store;
use App\Services\DocumentNumber;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Tagihan setoran ke toko.
 */
class InvoiceController extends Controller
{
    public function __construct(private readonly DocumentNumber $nomor) {}

    public function index(Request $request): View
    {
        $daftar = Invoice::query()
            ->with('store:id,code,name')
            ->withCount('items')
            ->when($request->string('cari')->trim()->value(), fn ($q, $c) => $q->where('number', 'like', "%{$c}%"))
            ->when($request->string('toko')->value(), fn ($q, $id) => $q->where('store_id', $id))
            ->when($request->string('status')->value(), function ($q, $s) {
                return $s === 'jatuh_tempo' ? $q->jatuhTempo() : $q->where('status', $s);
            })
            ->when($request->date('dari'), fn ($q, $d) => $q->whereDate('invoice_date', '>=', $d))
            ->when($request->date('sampai'), fn ($q, $d) => $q->whereDate('invoice_date', '<=', $d))
            ->latest('invoice_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $ringkasan = Invoice::query()
            ->where('status', '!=', 'cancelled')
            ->selectRaw('
                COALESCE(SUM(amount_due), 0) as total_tagihan,
                COALESCE(SUM(amount_paid), 0) as total_dibayar
            ')
            ->first();

        return view('tagihan.index', [
            'daftar' => $daftar,
            'toko' => Store::orderBy('name')->get(['id', 'name']),
            'totalTagihan' => (int) $ringkasan->total_tagihan,
            'totalDibayar' => (int) $ringkasan->total_dibayar,
            'jumlahJatuhTempo' => Invoice::jatuhTempo()->count(),
        ]);
    }

    /**
     * Halaman pilih pengiriman yang mau ditagih.
     */
    public function create(Request $request): View
    {
        Gate::authorize('kelola-tagihan');

        $toko = $request->integer('toko')
            ? Store::find($request->integer('toko'))
            : null;

        $pengiriman = $toko
            ? Consignment::belumDitagih()
                ->where('store_id', $toko->id)
                ->with('items.product:id,code,name')
                ->orderBy('sent_date')
                ->get()
            : collect();

        return view('tagihan.form', [
            'toko' => Store::aktif()->orderBy('name')->get(['id', 'name']),
            'tokoTerpilih' => $toko,
            'pengiriman' => $pengiriman,
            'jatuhTempoHari' => $toko?->tempoBayar() ?? (int) Setting::ambil('jatuh_tempo_hari', '7'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('kelola-tagihan');

        $data = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'consignment_ids' => ['required', 'array', 'min:1'],
            'consignment_ids.*' => ['integer', 'exists:consignments,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], attributes: [
            'store_id' => 'Toko',
            'invoice_date' => 'Tanggal tagihan',
            'due_date' => 'Jatuh tempo',
            'consignment_ids' => 'Pengiriman yang ditagih',
            'notes' => 'Catatan',
        ], messages: [
            'consignment_ids.required' => 'Pilih minimal satu pengiriman untuk ditagih.',
            'due_date.after_or_equal' => 'Jatuh tempo tidak boleh sebelum tanggal tagihan.',
        ]);

        $toko = Store::findOrFail($data['store_id']);

        // Hanya pengiriman milik toko ini yang sudah settled dan belum ditagih.
        $pengiriman = Consignment::belumDitagih()
            ->where('store_id', $toko->id)
            ->whereIn('id', $data['consignment_ids'])
            ->with('items.product:id,name')
            ->get();

        if ($pengiriman->count() !== count($data['consignment_ids'])) {
            return back()->withInput()->withErrors([
                'consignment_ids' => 'Ada pengiriman yang sudah ditagih atau bukan milik toko ini. Muat ulang halaman lalu coba lagi.',
            ]);
        }

        $tagihan = DB::transaction(function () use ($toko, $data, $pengiriman) {
            $tagihan = Invoice::create([
                'number' => $this->nomor->tagihan($data['invoice_date']),
                'store_id' => $toko->id,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'],
                'period_start' => $pengiriman->min('sent_date'),
                'period_end' => $pengiriman->max('settled_date') ?? $pengiriman->max('sent_date'),
                'gross_total' => 0,
                'fee_total' => 0,
                'amount_due' => 0,
                'amount_paid' => 0,
                'status' => 'unpaid',
                'notes' => $data['notes'] ?? null,
                'user_id' => auth()->id(),
            ]);

            $kotor = 0;
            $fee = 0;
            $setoran = 0;

            foreach ($pengiriman as $kirim) {
                foreach ($kirim->items as $item) {
                    if ($item->qty_sold <= 0) {
                        continue;
                    }

                    $netto = $item->unit_price - $item->fee_per_unit;
                    $subtotal = $item->qty_sold * $netto;

                    // Snapshot lengkap supaya cetak ulang selalu sama.
                    $tagihan->items()->create([
                        'consignment_id' => $kirim->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'qty_sold' => $item->qty_sold,
                        'unit_price' => $item->unit_price,
                        'fee_per_unit' => $item->fee_per_unit,
                        'net_per_unit' => $netto,
                        'subtotal' => $subtotal,
                        'unit_cost' => $item->unit_cost,
                    ]);

                    $kotor += $item->qty_sold * $item->unit_price;
                    $fee += $item->qty_sold * $item->fee_per_unit;
                    $setoran += $subtotal;
                }

                // Pengiriman terkunci setelah masuk tagihan.
                $kirim->update(['status' => 'invoiced', 'invoice_id' => $tagihan->id]);
            }

            $tagihan->update([
                'gross_total' => $kotor,
                'fee_total' => $fee,
                'amount_due' => $setoran,
            ]);

            ActivityLog::catat(
                'membuat',
                "Membuat tagihan {$tagihan->number} untuk {$toko->name} senilai ".rupiah($setoran).'.',
                $tagihan
            );

            return $tagihan;
        });

        return redirect()
            ->route('tagihan.show', $tagihan)
            ->with('sukses', "Tagihan {$tagihan->number} dibuat. Total setoran ".rupiah($tagihan->amount_due).'.');
    }

    public function show(Invoice $tagihan): View
    {
        $tagihan->load([
            'store',
            'user:id,name',
            'items',
            'payments.user:id,name',
            'consignments:id,number,sent_date,settled_date,invoice_id',
        ]);

        return view('tagihan.show', [
            'tagihan' => $tagihan,
            'pesanWhatsapp' => $this->pesanWhatsapp($tagihan),
        ]);
    }

    /**
     * Tagihan PDF ukuran A4.
     */
    public function cetak(Invoice $tagihan): Response
    {
        $tagihan->load(['store', 'items', 'payments']);

        $pdf = Pdf::loadView('tagihan.cetak.a4', [
            'tagihan'     => $tagihan,
            'pengaturan'  => Setting::semua(),
        ])->setPaper('a4');

        return $pdf->stream('Invoice-'.str_replace('/', '-', $tagihan->number).'.pdf');
    }

    /**
     * Versi struk 58 mm.
     */
    public function struk(Invoice $tagihan): View
    {
        $tagihan->load(['store', 'items']);

        return view('tagihan.cetak.struk', [
            'tagihan' => $tagihan,
            'pengaturan' => Setting::semua(),
        ]);
    }

    /**
     * Membatalkan tagihan. Hanya pemilik, dan hanya bila belum ada pembayaran.
     */
    public function batalkan(Request $request, Invoice $tagihan): RedirectResponse
    {
        Gate::authorize('batalkan-tagihan');

        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'min:5', 'max:500'],
        ], attributes: ['cancel_reason' => 'Alasan pembatalan'], messages: [
            'cancel_reason.required' => 'Alasan pembatalan wajib diisi.',
            'cancel_reason.min' => 'Tulis alasan pembatalan minimal 5 karakter.',
        ]);

        if (! $tagihan->bisaDibatalkan()) {
            return back()->with(
                'gagal',
                $tagihan->status === 'cancelled'
                    ? 'Tagihan ini sudah dibatalkan.'
                    : 'Tagihan yang sudah ada pembayarannya tidak bisa dibatalkan. Hapus pembayarannya lebih dulu.'
            );
        }

        DB::transaction(function () use ($tagihan, $data) {
            // Pengiriman terkait kembali ke status settled agar bisa ditagih ulang.
            $tagihan->consignments()->update(['status' => 'settled', 'invoice_id' => null]);

            $tagihan->update([
                'status' => 'cancelled',
                'cancel_reason' => $data['cancel_reason'],
            ]);

            ActivityLog::catat(
                'membatalkan',
                "Membatalkan tagihan {$tagihan->number}. Alasan: {$data['cancel_reason']}",
                $tagihan
            );
        });

        return redirect()
            ->route('tagihan.show', $tagihan)
            ->with('sukses', "Tagihan {$tagihan->number} dibatalkan. Pengiriman terkait bisa ditagih ulang.");
    }

    /**
     * Ringkasan tagihan untuk dikirim lewat WhatsApp.
     */
    private function pesanWhatsapp(Invoice $tagihan): string
    {
        $namaUsaha = Setting::ambil('nama_usaha', config('app.name'));

        $baris = [
            "Halo {$tagihan->store->name}, berikut tagihan setoran dari {$namaUsaha}:",
            '',
            "Nomor: {$tagihan->number}",
            'Tanggal: '.tanggal_indo($tagihan->invoice_date),
            'Jatuh tempo: '.tanggal_indo($tagihan->due_date),
            'Total setoran: '.rupiah($tagihan->amount_due),
        ];

        if ($tagihan->amount_paid > 0) {
            $baris[] = 'Sudah dibayar: '.rupiah($tagihan->amount_paid);
            $baris[] = 'Sisa: '.rupiah($tagihan->sisaTagihan());
        }

        if (Setting::ambil('bank_nama')) {
            $baris[] = '';
            $baris[] = 'Pembayaran dapat ditransfer ke:';
            $baris[] = Setting::ambil('bank_nama').' '.Setting::ambil('bank_nomor_rekening')
                .' a.n. '.Setting::ambil('bank_atas_nama');
        }

        $baris[] = '';
        $baris[] = 'Terima kasih.';

        return implode("\n", $baris);
    }

    /**
     * Tanggal jatuh tempo default untuk satu toko.
     */
    public static function jatuhTempoDefault(Store $toko, Carbon|string|null $tanggalTagihan = null): Carbon
    {
        return Carbon::parse($tanggalTagihan ?? now())->addDays($toko->tempoBayar());
    }
}
