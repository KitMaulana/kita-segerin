<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Consignment extends Model
{
    protected $fillable = [
        'number', 'store_id', 'sent_date', 'settled_date', 'status', 'invoice_id', 'notes', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'sent_date' => 'date',
            'settled_date' => 'date',
        ];
    }

    public const STATUS = [
        'sent' => 'Terkirim',
        'settled' => 'Sudah direkonsiliasi',
        'invoiced' => 'Sudah ditagih',
        'cancelled' => 'Dibatalkan',
    ];

    public const WARNA_STATUS = [
        'sent' => 'mango',
        'settled' => 'berry',
        'invoiced' => 'mint',
        'cancelled' => 'strawberry',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ConsignmentItem::class);
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function scopeBelumDitagih(Builder $query): Builder
    {
        return $query->where('status', 'settled')->whereNull('invoice_id');
    }

    public function namaStatus(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    public function warnaStatus(): string
    {
        return self::WARNA_STATUS[$this->status] ?? 'ink';
    }

    /**
     * Pengiriman terkunci bila sudah masuk tagihan atau dibatalkan.
     */
    public function terkunci(): bool
    {
        return in_array($this->status, ['invoiced', 'cancelled'], true);
    }

    public function bisaDirekonsiliasi(): bool
    {
        return in_array($this->status, ['sent', 'settled'], true);
    }

    /**
     * Berapa hari pengiriman ini belum direkonsiliasi.
     */
    public function umurHari(): int
    {
        return (int) $this->sent_date->startOfDay()->diffInDays(now()->startOfDay());
    }
}
