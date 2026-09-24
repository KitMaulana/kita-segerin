<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Payment extends Model
{
    protected $fillable = [
        'number', 'invoice_id', 'paid_at', 'amount', 'method', 'reference', 'proof_path', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'date',
            'amount' => 'integer',
        ];
    }

    public const METODE = [
        'cash' => 'Tunai',
        'transfer' => 'Transfer bank',
        'qris' => 'QRIS',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashTransactions(): MorphMany
    {
        return $this->morphMany(CashTransaction::class, 'reference');
    }

    public function namaMetode(): string
    {
        return self::METODE[$this->method] ?? $this->method;
    }
}
