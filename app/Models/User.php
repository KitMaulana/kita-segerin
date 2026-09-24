<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'username', 'email', 'password', 'role', 'is_active', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public const PERAN = [
        'owner' => 'Pemilik',
        'admin' => 'Admin',
        'viewer' => 'Pemantau',
    ];

    public const KETERANGAN_PERAN = [
        'owner' => 'Semua akses, termasuk kelola akun, pengaturan usaha, dan batalkan tagihan.',
        'admin' => 'Input transaksi, tagihan, dan pembayaran. Tidak bisa kelola akun.',
        'viewer' => 'Hanya melihat beranda dan laporan.',
    ];

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function namaPeran(): string
    {
        return self::PERAN[$this->role] ?? $this->role;
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    /** Pemilik dan admin boleh menginput transaksi. */
    public function bisaInput(): bool
    {
        return in_array($this->role, ['owner', 'admin'], true);
    }

    /**
     * Jumlah pemilik aktif selain pengguna ini. Dipakai untuk memastikan
     * selalu ada minimal satu pemilik aktif.
     */
    public static function jumlahOwnerAktifSelain(?int $kecualiId = null): int
    {
        return self::query()
            ->aktif()
            ->where('role', 'owner')
            ->when($kecualiId, fn ($q) => $q->whereKeyNot($kecualiId))
            ->count();
    }
}
