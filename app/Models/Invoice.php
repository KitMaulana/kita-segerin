<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'number', 'store_id', 'invoice_date', 'due_date', 'period_start', 'period_end',
        'gross_total', 'fee_total', 'amount_due', 'amount_paid', 'status',
        'notes', 'cancel_reason', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
            'gross_total' => 'integer',
            'fee_total' => 'integer',
            'amount_due' => 'integer',
            'amount_paid' => 'integer',
        ];
    }

    public const STATUS = [
        'unpaid' => 'Belum dibayar',
        'partial' => 'Sebagian',
        'paid' => 'Lunas',
        'cancelled' => 'Dibatalkan',
    ];

    public const WARNA_STATUS = [
        'unpaid' => 'mango',
        'partial' => 'berry',
        'paid' => 'mint',
        'cancelled' => 'strawberry',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('paid_at');
    }

    public function consignments(): HasMany
    {
        return $this->hasMany(Consignment::class);
    }

    public function scopeBelumLunas(Builder $query): Builder
    {
        return $query->whereIn('status', ['unpaid', 'partial']);
    }

    public function scopeJatuhTempo(Builder $query): Builder
    {
        return $query->belumLunas()->whereDate('due_date', '<', now()->toDateString());
    }

    public function namaStatus(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    public function warnaStatus(): string
    {
        return $this->lewatJatuhTempo() ? 'strawberry' : (self::WARNA_STATUS[$this->status] ?? 'ink');
    }

    public function sisaTagihan(): int
    {
        return max(0, $this->amount_due - $this->amount_paid);
    }

    public function lewatJatuhTempo(): bool
    {
        return in_array($this->status, ['unpaid', 'partial'], true)
            && $this->due_date->isBefore(now()->startOfDay());
    }

    public function umurPiutangHari(): int
    {
        return (int) $this->due_date->startOfDay()->diffInDays(now()->startOfDay(), absolute: false);
    }

    public function bisaDibatalkan(): bool
    {
        return $this->status !== 'cancelled' && $this->payments()->doesntExist();
    }

    /**
     * Menghitung ulang amount_paid dan status dari seluruh pembayaran yang tercatat.
     */
    public function segarkanStatusPembayaran(): void
    {
        if ($this->status === 'cancelled') {
            return;
        }

        $dibayar = (int) $this->payments()->sum('amount');

        $this->forceFill([
            'amount_paid' => $dibayar,
            'status' => match (true) {
                $dibayar <= 0 => 'unpaid',
                $dibayar >= $this->amount_due => 'paid',
                default => 'partial',
            },
        ])->save();
    }
}
