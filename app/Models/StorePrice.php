<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Harga jual dan fee khusus satu toko untuk satu produk, berlaku mulai tanggal tertentu.
 */
class StorePrice extends Model
{
    protected $fillable = [
        'store_id', 'product_id', 'selling_price', 'fee_type', 'fee_value', 'effective_from',
    ];

    protected function casts(): array
    {
        return [
            'selling_price' => 'integer',
            'fee_value' => 'integer',
            'effective_from' => 'date',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
