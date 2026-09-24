<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Riwayat siapa mengubah apa dan kapan.
 */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'action', 'subject_type', 'subject_id', 'description', 'ip_address',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Mencatat satu aksi. Dipanggil dari controller setelah operasi berhasil.
     */
    public static function catat(string $aksi, string $keterangan, ?Model $subjek = null): self
    {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $aksi,
            'subject_type' => $subjek?->getMorphClass(),
            'subject_id' => $subjek?->getKey(),
            'description' => $keterangan,
            'ip_address' => request()->ip(),
        ]);
    }

    /** Nama jenis data dalam Bahasa Indonesia untuk ditampilkan di halaman log. */
    public function namaSubjek(): string
    {
        return match ($this->subject_type) {
            Product::class => 'Produk',
            Store::class => 'Toko',
            Supplier::class => 'Pemasok',
            Purchase::class => 'Pembelian',
            Consignment::class => 'Pengiriman',
            Invoice::class => 'Tagihan',
            Payment::class => 'Pembayaran',
            Expense::class => 'Biaya',
            CashTransaction::class => 'Buku kas',
            StockMovement::class => 'Stok',
            StorePrice::class => 'Harga toko',
            User::class => 'Akun',
            Setting::class => 'Pengaturan',
            default => '—',
        };
    }
}
