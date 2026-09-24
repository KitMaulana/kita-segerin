<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Pengaturan usaha dalam bentuk pasangan kunci–nilai.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public const CACHE_KEY = 'pengaturan_usaha';

    /**
     * Nilai bawaan bila pengaturan belum pernah disimpan.
     *
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'nama_usaha' => 'KITAA SEGERIN',
        'alamat' => '',
        'telepon' => '',
        'email' => '',
        'logo_path' => '',
        'bank_nama' => '',
        'bank_nomor_rekening' => '',
        'bank_atas_nama' => '',
        'kota_ttd' => '',
        'nama_penandatangan' => '',
        'jatuh_tempo_hari' => '7',
        'catatan_tagihan' => '',
    ];

    /**
     * Seluruh pengaturan sebagai array, dengan cache agar tidak bolak-balik ke database.
     *
     * @return array<string, string>
     */
    public static function semua(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return array_merge(self::DEFAULTS, self::query()->pluck('value', 'key')->all());
        });
    }

    public static function ambil(string $kunci, ?string $bawaan = null): ?string
    {
        return self::semua()[$kunci] ?? $bawaan ?? self::DEFAULTS[$kunci] ?? null;
    }

    /**
     * @param  array<string, string|null>  $nilai
     */
    public static function simpan(array $nilai): void
    {
        foreach ($nilai as $kunci => $isi) {
            self::updateOrCreate(['key' => $kunci], ['value' => $isi]);
        }

        self::bersihkanCache();
    }

    public static function bersihkanCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::bersihkanCache());
        static::deleted(fn () => self::bersihkanCache());
    }
}
