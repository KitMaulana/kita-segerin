<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rincian tagihan. Nama produk dan seluruh harga disalin agar cetak ulang
 * tagihan lama tetap menampilkan angka yang sama.
 */
class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'consignment_id', 'product_id', 'product_name',
        'qty_sold', 'unit_price', 'fee_per_unit', 'net_per_unit', 'subtotal', 'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'qty_sold' => 'integer',
            'unit_price' => 'integer',
            'fee_per_unit' => 'integer',
            'net_per_unit' => 'integer',
            'subtotal' => 'integer',
            'unit_cost' => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function consignment(): BelongsTo
    {
        return $this->belongsTo(Consignment::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function penjualanKotor(): int
    {
        return $this->qty_sold * $this->unit_price;
    }

    public function totalFee(): int
    {
        return $this->qty_sold * $this->fee_per_unit;
    }

    public function hpp(): int
    {
        return $this->qty_sold * $this->unit_cost;
    }
}
