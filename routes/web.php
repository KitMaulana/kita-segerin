<?php

use App\Http\Controllers\PlaceholderController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute aplikasi
|--------------------------------------------------------------------------
|
| Seluruh halaman wajib login kecuali halaman masuk. Slug URL memakai Bahasa
| Indonesia, sedangkan nama route dipakai sebagai kunci menu di
| config/navigation.php.
|
*/

Route::redirect('/', '/beranda')->name('home');

// Catatan: middleware penolak akun nonaktif dan Gate per peran dipasang pada Tahap 3.
Route::middleware('auth')->group(function () {

    Route::view('/beranda', 'beranda')->name('beranda');
    Route::view('/lainnya', 'lainnya')->name('lainnya');

    // Profil sendiri (bawaan Breeze, slug diterjemahkan).
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profile.update');

    /*
     | Halaman sementara. Setiap baris diganti controller sungguhan pada
     | tahap yang disebut di PlaceholderController.
     */
    Route::get('/pengiriman', PlaceholderController::class)->name('pengiriman.index');
    Route::get('/tagihan', PlaceholderController::class)->name('tagihan.index');
    Route::get('/toko', PlaceholderController::class)->name('toko.index');
    Route::get('/produk', PlaceholderController::class)->name('produk.index');
    Route::get('/stok', PlaceholderController::class)->name('stok.index');
    Route::get('/pembelian', PlaceholderController::class)->name('pembelian.index');
    Route::get('/pemasok', PlaceholderController::class)->name('pemasok.index');
    Route::get('/biaya', PlaceholderController::class)->name('biaya.index');
    Route::get('/kas', PlaceholderController::class)->name('kas.index');
    Route::get('/laporan', PlaceholderController::class)->name('laporan.index');
    Route::get('/akun', PlaceholderController::class)->name('akun.index');
    Route::get('/pengaturan', PlaceholderController::class)->name('pengaturan.edit');
    Route::get('/log-aktivitas', PlaceholderController::class)->name('log-aktivitas.index');
});

require __DIR__.'/auth.php';
