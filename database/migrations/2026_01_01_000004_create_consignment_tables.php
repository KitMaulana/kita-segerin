<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengiriman titip jual dan rinciannya.
 *
 * Harga modal, harga jual, dan fee per pcs DISALIN (snapshot) ke baris item saat
 * pengiriman dibuat, supaya perubahan harga di master data tidak mengubah
 * transaksi lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consignments', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();                     // KRM/2026/09/0001
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->date('sent_date')->index();
            $table->date('settled_date')->nullable();
            $table->enum('status', ['sent', 'settled', 'invoiced', 'cancelled'])->default('sent')->index();
            // Kunci asing ke invoices ditambahkan di migrasi berikutnya
            // karena tabel invoices belum ada saat tabel ini dibuat.
            $table->unsignedBigInteger('invoice_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['status', 'sent_date']);
        });

        Schema::create('consignment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('qty_sent');
            $table->unsignedInteger('qty_sold')->default(0);
            $table->unsignedInteger('qty_returned')->default(0);
            $table->unsignedInteger('qty_damaged')->default(0);
            $table->unsignedBigInteger('unit_cost');                // snapshot harga modal
            $table->unsignedBigInteger('unit_price');               // snapshot harga jual
            $table->unsignedBigInteger('fee_per_unit');             // snapshot fee toko dalam rupiah
            $table->timestamps();

            $table->index(['consignment_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consignment_items');
        Schema::dropIfExists('consignments');
    }
};
