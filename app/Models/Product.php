<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'supplier_id', 'variant', 'unit',
        'cost_price', 'default_selling_price',
        'default_fee_type', 'default_fee_value',
        'min_stock', 'photo_path', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'integer',
            'default_selling_price' => 'integer',
            'default_fee_value' => 'integer',
            'min_stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public const VARIAN = [
        'cup' => 'Cup',
        'stik' => 'Stik',
        'cone' => 'Cone',
        'lainnya' => 'Lainnya',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function costHistories(): HasMany
    {
        return $this->hasMany(ProductCostHistory::class)->latest('created_at');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function storePrices(): HasMany
    {
        return $this->hasMany(StorePrice::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function consignmentItems(): HasMany
    {
        return $this->hasMany(ConsignmentItem::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function namaVarian(): string
    {
        return self::VARIAN[$this->variant] ?? $this->variant;
    }

    /**
     * Produk yang sudah dipakai transaksi hanya boleh dinonaktifkan, tidak dihapus.
     */
    public function punyaTransaksi(): bool
    {
        return $this->purchaseItems()->exists()
            || $this->consignmentItems()->exists()
            || $this->stockMovements()->exists();
    }
}
