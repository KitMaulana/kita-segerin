<?php

namespace App\Providers;

use App\Models\CashTransaction;
use App\Models\Consignment;
use App\Models\ConsignmentItem;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Services\DashboardService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Model yang perubahannya membuat angka beranda berubah.
     *
     * @var array<int, class-string<Model>>
     */
    private const MODEL_TRANSAKSI = [
        Purchase::class,
        StockMovement::class,
        Consignment::class,
        ConsignmentItem::class,
        Invoice::class,
        Payment::class,
        Expense::class,
        CashTransaction::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Nama hari dan bulan tampil dalam Bahasa Indonesia.
        Carbon::setLocale(config('app.locale'));
        Date::setLocale(config('app.locale'));
        setlocale(LC_TIME, 'id_ID.UTF-8', 'id_ID', 'Indonesian');

        // Pengaman agar salah ketik nama kolom langsung ketahuan saat pengembangan.
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        $this->bersihkanCacheBerandaSaatTransaksi();

        Vite::prefetch(concurrency: 3);
    }

    /**
     * Cache beranda dihapus setiap ada transaksi baru, diubah, atau dihapus.
     */
    private function bersihkanCacheBerandaSaatTransaksi(): void
    {
        foreach (self::MODEL_TRANSAKSI as $model) {
            $model::saved(fn () => DashboardService::bersihkanCache());
            $model::deleted(fn () => DashboardService::bersihkanCache());
        }
    }
}
