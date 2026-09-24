<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Beranda (dashboard) dengan infografis Chart.js.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $beranda) {}

    public function index(Request $request): View
    {
        $bulan = $this->bulanTerpilih($request);

        return view('beranda', $this->beranda->data($bulan) + [
            'daftarBulan' => $this->daftarBulan(),
            'bulanTerpilih' => $bulan->format('Y-m'),
        ]);
    }

    /**
     * Versi cetak infografis, A4 landscape lewat CSS print peramban.
     */
    public function cetak(Request $request): View
    {
        $bulan = $this->bulanTerpilih($request);

        return view('beranda-cetak', $this->beranda->data($bulan) + [
            'pengaturan' => Setting::semua(),
        ]);
    }

    private function bulanTerpilih(Request $request): Carbon
    {
        $nilai = $request->string('bulan')->value();

        if ($nilai && preg_match('/^\d{4}-\d{2}$/', $nilai)) {
            return Carbon::createFromFormat('Y-m-d', $nilai.'-01')->startOfMonth();
        }

        return now()->startOfMonth();
    }

    /**
     * Dua belas bulan terakhir untuk kotak pilihan.
     *
     * @return array<string, string>
     */
    private function daftarBulan(): array
    {
        return collect(range(0, 11))
            ->mapWithKeys(function (int $mundur) {
                $b = now()->startOfMonth()->subMonthsNoOverflow($mundur);

                return [$b->format('Y-m') => $b->locale('id')->translatedFormat('F Y')];
            })
            ->all();
    }
}
