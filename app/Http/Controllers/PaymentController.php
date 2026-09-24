<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\CashBookService;
use App\Services\DocumentNumber;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * Pembayaran tagihan oleh toko. Bisa bertahap.
 */
class PaymentController extends Controller
{
    public function __construct(
        private readonly CashBookService $kas,
        private readonly DocumentNumber $nomor,
    ) {}

    public function store(Request $request, Invoice $tagihan): RedirectResponse
    {
        Gate::authorize('kelola-tagihan');

        if ($tagihan->status === 'cancelled') {
            return back()->with('gagal', 'Tagihan ini sudah dibatalkan, jadi tidak bisa menerima pembayaran.');
        }

        if ($tagihan->sisaTagihan() <= 0) {
            return back()->with('info', 'Tagihan ini sudah lunas.');
        }

        $data = $request->validate([
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'integer', 'min:1', 'max:'.$tagihan->sisaTagihan()],
            'method' => ['required', 'in:cash,transfer,qris'],
            'reference' => ['nullable', 'string', 'max:100'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ], attributes: [
            'paid_at' => 'Tanggal bayar',
            'amount' => 'Jumlah bayar',
            'method' => 'Metode',
            'reference' => 'Nomor referensi',
            'proof' => 'Bukti bayar',
        ], messages: [
            'amount.max' => 'Jumlah bayar melebihi sisa tagihan ('.rupiah($tagihan->sisaTagihan()).').',
            'paid_at.before_or_equal' => 'Tanggal bayar tidak boleh di masa depan.',
        ]);

        $pembayaran = DB::transaction(function () use ($request, $tagihan, $data) {
            $pembayaran = Payment::create([
                'number' => $this->nomor->pembayaran($data['paid_at']),
                'invoice_id' => $tagihan->id,
                'paid_at' => $data['paid_at'],
                'amount' => $data['amount'],
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'proof_path' => $request->hasFile('proof')
                    ? $request->file('proof')->store('bukti-bayar', 'public')
                    : null,
                'user_id' => auth()->id(),
            ]);

            // Uang masuk tercatat di buku kas.
            $this->kas->masuk(
                kategori: 'sales_payment',
                jumlah: $pembayaran->amount,
                keterangan: "Pembayaran {$pembayaran->number} untuk tagihan {$tagihan->number} dari {$tagihan->store->name}",
                tanggal: $pembayaran->paid_at,
                referensi: $pembayaran,
            );

            $tagihan->segarkanStatusPembayaran();

            ActivityLog::catat(
                'membuat',
                "Mencatat pembayaran {$pembayaran->number} sebesar ".rupiah($pembayaran->amount)." untuk tagihan {$tagihan->number}.",
                $pembayaran
            );

            return $pembayaran;
        });

        $tagihan->refresh();

        return redirect()
            ->route('tagihan.show', $tagihan)
            ->with('sukses', 'Pembayaran '.rupiah($pembayaran->amount).' tercatat. Status tagihan sekarang: '.$tagihan->namaStatus().'.');
    }

    /**
     * Kuitansi pembayaran dalam bentuk PDF.
     */
    public function kuitansi(Payment $pembayaran): Response
    {
        $pembayaran->load('invoice.store');

        $pdf = Pdf::loadView('tagihan.cetak.kuitansi', [
            'pembayaran' => $pembayaran,
            'pengaturan' => Setting::semua(),
        ])->setPaper('a5', 'landscape');

        return $pdf->stream('Kuitansi-'.str_replace('/', '-', $pembayaran->number).'.pdf');
    }

    /**
     * Menghapus pembayaran (hanya pemilik), sekaligus baris buku kasnya.
     */
    public function destroy(Payment $pembayaran): RedirectResponse
    {
        Gate::authorize('hapus-transaksi');

        $tagihan = $pembayaran->invoice;

        DB::transaction(function () use ($pembayaran, $tagihan) {
            $this->kas->hapusUntuk($pembayaran);

            if ($pembayaran->proof_path) {
                Storage::disk('public')->delete($pembayaran->proof_path);
            }

            ActivityLog::catat(
                'menghapus',
                "Menghapus pembayaran {$pembayaran->number} sebesar ".rupiah($pembayaran->amount).".",
                $tagihan
            );

            $pembayaran->delete();

            $tagihan->segarkanStatusPembayaran();
        });

        return redirect()
            ->route('tagihan.show', $tagihan)
            ->with('sukses', 'Pembayaran dihapus dan status tagihan diperbarui.');
    }
}
