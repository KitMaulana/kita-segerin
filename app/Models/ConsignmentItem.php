<?php

namespace App\Models;

use App\Services\FinanceCalculator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris produk dalam satu pengiriman titip jual.
 * unit_cost, unit_price, dan fee_per_unit adalah salinan harga saat dikirim.
 */
class ConsignmentItem extends Model
{
    protected $fillable = [
        'consignment_id', 'product_id', 'qty_sent', 'qty_sold', 'qty_returned', 'qty_damaged',
        'unit_cost', 'unit_price', 'fee_per_unit',
    ];

    protected function casts(): array
    {
        return [
            'qty_sent' => 'integer',
            'qty_sold' => 'integer',
            'qty_returned' => 'integer',
            'qty_damaged' => 'integer',
            'unit_cost' => 'integer',
            'unit_price' => 'integer',
            'fee_per_unit' => 'integer',
        ];
    }

    public function consignment(): BelongsTo
    {
        return $this->belongsTo(Consignment::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Hasil perhitungan baris ini (penjualan kotor, fee, setoran, HPP, laba, dst).
     *
     * @return array<string, int|float>
     */
    public function hitung(): array
    {
        return app(FinanceCalculator::class)->hitungBaris(
            qtySent: $this->qty_sent,
            qtySold: $this->qty_sold,
            qtyReturned: $this->qty_returned,
            qtyDamaged: $this->qty_damaged,
            unitCost: $this->unit_cost,
            unitPrice: $this->unit_price,
            feePerUnit: $this->fee_per_unit,
        );
    }

    public function sisaBelumDicatat(): int
    {
        return $this->qty_sent - ($this->qty_sold + $this->qty_returned + $this->qty_damaged);
    }
}
