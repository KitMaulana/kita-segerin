<?php

namespace App\Services;

use App\Models\ConsignmentItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Perhitungan stok gudang, stok yang dititipkan di toko, dan nilai persediaan.
 *
 * Stok gudang selalu dihitung dari SUM(stock_movements.qty) — tidak pernah
 * disimpan sebagai angka tersendiri, supaya tidak bisa selisih.
 */
class StockService
{
    /**
     * Mencatat satu mutasi stok.
     */
    public function catat(
        Product|int $product,
        string $jenis,
        int $qty,
        Carbon|string|null $tanggal = null,
        ?Model $referensi = null,
        ?string $catatan = null,
    ): StockMovement {
        return StockMovement::create([
            'product_id' => $product instanceof Product ? $product->getKey() : $product,
            'date' => Carbon::parse($tanggal ?? now())->toDateString(),
            'type' => $jenis,
            'qty' => $qty,
            'reference_type' => $referensi?->getMorphClass(),
            'reference_id' => $referensi?->getKey(),
            'notes' => $catatan,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Stok gudang satu produk pada saat ini (atau sampai tanggal tertentu).
     */
    public function stokGudang(Product|int $product, Carbon|string|null $sampaiTanggal = null): int
    {
        return (int) StockMovement::query()
            ->gudang()
            ->where('product_id', $product instanceof Product ? $product->getKey() : $product)
            ->when($sampaiTanggal, fn ($q) => $q->whereDate('date', '<=', Carbon::parse($sampaiTanggal)->toDateString()))
            ->sum('qty');
    }

    /**
     * Stok gudang seluruh produk, sebagai array [product_id => qty].
     *
     * @return array<int, int>
     */
    public function stokGudangSemua(Carbon|string|null $sampaiTanggal = null): array
    {
        return StockMovement::query()
            ->gudang()
            ->when($sampaiTanggal, fn ($q) => $q->whereDate('date', '<=', Carbon::parse($sampaiTanggal)->toDateString()))
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(qty) as total')
            ->pluck('total', 'product_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * Stok yang sedang dititipkan di toko: barang sudah dikirim tetapi belum
     * direkonsiliasi. Tetap dihitung sebagai persediaan milik KITAA SEGERIN.
     */
    public function stokDiToko(Product|int $product): int
    {
        return (int) ConsignmentItem::query()
            ->where('product_id', $product instanceof Product ? $product->getKey() : $product)
            ->whereHas('consignment', fn ($q) => $q->where('status', 'sent'))
            ->selectRaw('COALESCE(SUM(qty_sent - qty_sold - qty_returned - qty_damaged), 0) as sisa')
            ->value('sisa');
    }

    /**
     * Stok di toko untuk seluruh produk, sebagai array [product_id => qty].
     *
     * @return array<int, int>
     */
    public function stokDiTokoSemua(): array
    {
        return ConsignmentItem::query()
            ->whereHas('consignment', fn ($q) => $q->where('status', 'sent'))
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(qty_sent - qty_sold - qty_returned - qty_damaged) as sisa')
            ->pluck('sisa', 'product_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * Ringkasan persediaan seluruh produk aktif.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function ringkasanPersediaan(bool $termasukNonaktif = false): Collection
    {
        $gudang = $this->stokGudangSemua();
        $diToko = $this->stokDiTokoSemua();

        return Product::query()
            ->when(! $termasukNonaktif, fn ($q) => $q->aktif())
            ->with('supplier:id,name')
            ->orderBy('name')
            ->get()
            ->map(function (Product $p) use ($gudang, $diToko) {
                $stokGudang = $gudang[$p->id] ?? 0;
                $stokToko = $diToko[$p->id] ?? 0;
                $total = $stokGudang + $stokToko;

                return [
                    'produk' => $p,
                    'stok_gudang' => $stokGudang,
                    'stok_di_toko' => $stokToko,
                    'stok_total' => $total,
                    'nilai_gudang' => $stokGudang * $p->cost_price,
                    'nilai_di_toko' => $stokToko * $p->cost_price,
                    'nilai_total' => $total * $p->cost_price,
                    'menipis' => $p->min_stock > 0 && $stokGudang <= $p->min_stock,
                ];
            });
    }

    /**
     * Nilai persediaan total (gudang + titipan) dinilai dengan harga modal terkini.
     */
    public function nilaiPersediaan(): int
    {
        return (int) $this->ringkasanPersediaan(termasukNonaktif: true)->sum('nilai_total');
    }

    /**
     * Produk yang stok gudangnya sudah di bawah atau sama dengan stok minimum.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function stokMenipis(): Collection
    {
        return $this->ringkasanPersediaan()->filter(fn ($b) => $b['menipis'])->values();
    }

    /**
     * Memastikan stok gudang cukup untuk dikirim.
     *
     * @return array{cukup: bool, tersedia: int, diminta: int}
     */
    public function periksaKetersediaan(Product|int $product, int $qty): array
    {
        $tersedia = $this->stokGudang($product);

        return [
            'cukup' => $tersedia >= $qty,
            'tersedia' => $tersedia,
            'diminta' => $qty,
        ];
    }
}
