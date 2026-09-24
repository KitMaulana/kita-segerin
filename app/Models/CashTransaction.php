<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Satu baris buku kas. Pembayaran tagihan, pembelian, dan biaya membuat baris
 * ini secara otomatis; modal pemilik, prive, dan lain-lain diinput manual.
 */
class CashTransaction extends Model
{
    protected $fillable = [
        'date', 'direction', 'category', 'amount', 'description',
        'reference_type', 'reference_id', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'integer',
        ];
    }

    public const KATEGORI = [
        'sales_payment' => 'Pembayaran tagihan toko',
        'purchase' => 'Pembelian (kulakan)',
        'expense' => 'Biaya operasional',
        'owner_capital' => 'Modal pemilik',
        'owner_withdrawal' => 'Prive (ambil pribadi)',
        'other' => 'Lain-lain',
    ];

    /** Kategori yang boleh diinput manual lewat menu Buku Kas. */
    public const KATEGORI_MANUAL = ['owner_capital', 'owner_withdrawal', 'other'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeMasuk(Builder $query): Builder
    {
        return $query->where('direction', 'in');
    }

    public function scopeKeluar(Builder $query): Builder
    {
        return $query->where('direction', 'out');
    }

    public function namaKategori(): string
    {
        return self::KATEGORI[$this->category] ?? $this->category;
    }

    /** Nilai bertanda: positif bila uang masuk, negatif bila keluar. */
    public function nilaiBertanda(): int
    {
        return $this->direction === 'in' ? $this->amount : -$this->amount;
    }

    /** Baris otomatis tidak boleh diubah/dihapus langsung dari menu Buku Kas. */
    public function otomatis(): bool
    {
        return ! in_array($this->category, self::KATEGORI_MANUAL, true);
    }
}
