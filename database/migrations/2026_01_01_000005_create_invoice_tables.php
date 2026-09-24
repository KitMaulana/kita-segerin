<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tagihan setoran, rinciannya (snapshot untuk cetak ulang), dan pembayaran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();                     // INV/KS/2026/09/0001
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->date('invoice_date')->index();
            $table->date('due_date')->index();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedBigInteger('gross_total')->default(0);  // penjualan kotor
            $table->unsignedBigInteger('fee_total')->default(0);    // total fee toko
            $table->unsignedBigInteger('amount_due')->default(0);   // setoran yang ditagih
            $table->unsignedBigInteger('amount_paid')->default(0);
            $table->enum('status', ['unpaid', 'partial', 'paid', 'cancelled'])->default('unpaid')->index();
            $table->text('notes')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['status', 'due_date']);
        });

        Schema::table('consignments', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('consignment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');                         // disalin agar cetakan lama tetap sama
            $table->unsignedInteger('qty_sold');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('fee_per_unit');
            $table->unsignedBigInteger('net_per_unit');             // harga jual − fee
            $table->unsignedBigInteger('subtotal');                 // qty_sold × net_per_unit
            $table->unsignedBigInteger('unit_cost')->default(0);    // untuk hitung HPP di laporan
            $table->timestamps();

            $table->index(['invoice_id', 'product_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();                     // BYR/2026/09/0001
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->date('paid_at')->index();
            $table->unsignedBigInteger('amount');
            $table->enum('method', ['cash', 'transfer', 'qris'])->default('cash');
            $table->string('reference')->nullable();                // nomor transaksi / catatan
            $table->string('proof_path')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['invoice_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');

        Schema::table('consignments', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });

        Schema::dropIfExists('invoices');
    }
};
