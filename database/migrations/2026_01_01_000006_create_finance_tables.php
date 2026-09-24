<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Biaya operasional dan buku kas.
 *
 * Pembayaran tagihan, pembelian, dan biaya otomatis membuat baris buku kas.
 * Modal pemilik, prive, dan lain-lain diinput manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('description');
            $table->string('proof_path')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['expense_category_id', 'date']);
        });

        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->enum('direction', ['in', 'out']);
            $table->enum('category', [
                'sales_payment',     // pembayaran tagihan dari toko
                'purchase',          // kulakan
                'expense',           // biaya operasional
                'owner_capital',     // modal pemilik
                'owner_withdrawal',  // prive
                'other',
            ]);
            $table->unsignedBigInteger('amount');
            $table->string('description');
            $table->nullableMorphs('reference');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['date', 'direction']);
            $table->index(['category', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
