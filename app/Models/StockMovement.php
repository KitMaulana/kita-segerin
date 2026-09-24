<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Satu baris mutasi stok gudang. Nilai qty bertanda: positif masuk, negatif keluar.
 * Stok gudang = SUM(qty) per produk.
 */
class StockMovement extends Model
{
    protected $fillable = ['product_id', 'date', 'type', 'qty', 'reference_type', 'reference_id', 'notes', 'user_id'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'qty' => 'integer',
        ];
    }

    public const JENIS = [
        'purchase_in' => 'Masuk dari pembelian',
        'consignment_out' => 'Keluar untuk titip jual',
        'return_in' => 'Kembali dari toko (retur)',
        'damaged' => 'Rusak / meleleh',
        'adjustment' => 'Penyesuaian manual',
    ];

    /**
     * Jenis mutasi yang benar-benar mengubah stok gudang.
     * "damaged" tidak ikut karena barangnya sudah keluar saat pengiriman.
     */
    public const MEMENGARUHI_GUDANG = ['purchase_in', 'consignment_out', 'return_in', 'adjustment'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeGudang(Builder $query): Builder
    {
        return $query->whereIn('type', self::MEMENGARUHI_GUDANG);
    }

    public function namaJenis(): string
    {
        return self::JENIS[$this->type] ?? $this->type;
    }
}
