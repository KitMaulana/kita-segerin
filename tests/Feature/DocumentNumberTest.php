<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Services\DocumentNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DocumentNumberTest extends TestCase
{
    use RefreshDatabase;

    private DocumentNumber $nomor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->nomor = app(DocumentNumber::class);
    }

    public function test_pola_nomor_setiap_jenis_dokumen(): void
    {
        DB::transaction(function () {
            $this->assertSame('BLI/2026/09/0001', $this->nomor->pembelian('2026-09-23'));
            $this->assertSame('KRM/2026/09/0001', $this->nomor->pengiriman('2026-09-23'));
            $this->assertSame('INV/KS/2026/09/0001', $this->nomor->tagihan('2026-09-23'));
            $this->assertSame('BYR/2026/09/0001', $this->nomor->pembayaran('2026-09-23'));
        });
    }

    public function test_nomor_bertambah_urut(): void
    {
        foreach (range(1, 3) as $i) {
            DB::transaction(function () use ($i) {
                $nomor = $this->nomor->pembelian('2026-09-23');

                $this->assertSame('BLI/2026/09/'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), $nomor);

                Purchase::create([
                    'number' => $nomor,
                    'purchase_date' => '2026-09-23',
                    'total' => 0,
                ]);
            });
        }
    }

    public function test_nomor_dimulai_ulang_setiap_bulan(): void
    {
        DB::transaction(function () {
            Purchase::create([
                'number' => $this->nomor->pembelian('2026-09-30'),
                'purchase_date' => '2026-09-30',
                'total' => 0,
            ]);
        });

        DB::transaction(function () {
            $this->assertSame('BLI/2026/10/0001', $this->nomor->pembelian('2026-10-01'));
        });
    }

    public function test_kode_master_berurutan(): void
    {
        DB::transaction(function () {
            $this->assertSame('ES-001', $this->nomor->kodeMaster('products', 'ES'));
            $this->assertSame('TK-001', $this->nomor->kodeMaster('stores', 'TK'));
        });
    }

    public function test_jenis_dokumen_tidak_dikenal_ditolak(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        DB::transaction(fn () => $this->nomor->berikutnya('entah-apa'));
    }
}
