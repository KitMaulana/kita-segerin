<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data master: pemasok, produk, riwayat harga modal, toko, dan harga khusus toko.
 * Seluruh nilai uang disimpan sebagai bilangan bulat Rupiah (tanpa desimal).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();                       // mis. ES-001
            $table->string('name');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('variant', ['cup', 'stik', 'cone', 'lainnya'])->default('cup');
            $table->string('unit', 20)->default('pcs');
            $table->unsignedBigInteger('cost_price')->default(0);            // harga modal per pcs
            $table->unsignedBigInteger('default_selling_price')->default(0); // harga jual per pcs
            $table->enum('default_fee_type', ['nominal', 'percent'])->default('nominal');
            $table->unsignedBigInteger('default_fee_value')->default(0);     // rupiah atau persen
            $table->unsignedInteger('min_stock')->default(0);
            $table->string('photo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'name']);
        });

        Schema::create('product_cost_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('old_price');
            $table->unsignedBigInteger('new_price');
            $table->enum('source', ['manual', 'purchase'])->default('manual');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['product_id', 'created_at']);
        });

        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();                       // mis. TK-001
            $table->string('name');
            $table->enum('type', ['koperasi', 'kantin', 'toko', 'minimarket', 'lainnya'])->default('toko');
            $table->string('contact_person')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->unsignedSmallInteger('payment_term_days')->nullable(); // kosong = pakai default pengaturan
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'name']);
        });

        Schema::create('store_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('selling_price');
            $table->enum('fee_type', ['nominal', 'percent'])->default('nominal');
            $table->unsignedBigInteger('fee_value')->default(0);
            $table->date('effective_from');
            $table->timestamps();

            $table->unique(['store_id', 'product_id', 'effective_from'], 'store_prices_unik');
            $table->index(['store_id', 'product_id', 'effective_from'], 'store_prices_cari');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_prices');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('product_cost_histories');
        Schema::dropIfExists('products');
        Schema::dropIfExists('suppliers');
    }
};
