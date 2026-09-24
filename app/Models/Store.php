<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'type', 'contact_person', 'phone',
        'address', 'payment_term_days', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_term_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public const JENIS = [
        'koperasi' => 'Koperasi sekolah',
        'kantin' => 'Kantin',
        'toko' => 'Toko',
        'minimarket' => 'Minimarket',
        'lainnya' => 'Lainnya',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(StorePrice::class);
    }

    public function consignments(): HasMany
    {
        return $this->hasMany(Consignment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function namaJenis(): string
    {
        return self::JENIS[$this->type] ?? $this->type;
    }

    /**
     * Tempo bayar toko ini; bila kosong memakai default pengaturan usaha.
     */
    public function tempoBayar(): int
    {
        return $this->payment_term_days ?? (int) Setting::ambil('jatuh_tempo_hari', '7');
    }

    /**
     * Nomor WhatsApp dalam format internasional tanpa tanda baca, mis. 6281234567890.
     */
    public function nomorWhatsapp(): ?string
    {
        if (blank($this->phone)) {
            return null;
        }

        $angka = preg_replace('/\D/', '', $this->phone);

        if (str_starts_with($angka, '0')) {
            $angka = '62'.substr($angka, 1);
        } elseif (! str_starts_with($angka, '62')) {
            $angka = '62'.$angka;
        }

        return $angka;
    }

    public function punyaTransaksi(): bool
    {
        return $this->consignments()->exists() || $this->invoices()->exists();
    }

    /**
     * Sisa tagihan yang belum dibayar toko ini.
     */
    public function piutang(): int
    {
        return (int) $this->invoices()
            ->whereIn('status', ['unpaid', 'partial'])
            ->selectRaw('COALESCE(SUM(amount_due - amount_paid), 0) as sisa')
            ->value('sisa');
    }
}
