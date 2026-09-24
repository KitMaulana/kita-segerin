<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat perubahan harga modal produk.
 */
class ProductCostHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['product_id', 'old_price', 'new_price', 'source', 'user_id'];

    protected function casts(): array
    {
        return [
            'old_price' => 'integer',
            'new_price' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function selisih(): int
    {
        return $this->new_price - $this->old_price;
    }

    public function sumberTeks(): string
    {
        return $this->source === 'purchase' ? 'Dari pembelian' : 'Diubah manual';
    }
}
