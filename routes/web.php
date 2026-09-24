<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\PlaceholderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
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

Route::middleware(['auth', 'aktif'])->group(function () {

    Route::view('/beranda', 'beranda')->name('beranda');
    Route::view('/lainnya', 'lainnya')->name('lainnya');

    // Profil sendiri.
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profile.update');

    /*
     | Khusus pemilik: kelola akun, pengaturan usaha, dan log aktivitas.
     */
    Route::middleware('can:kelola-akun')->group(function () {
        Route::get('/akun', [UserController::class, 'index'])->name('akun.index');
        Route::get('/akun/tambah', [UserController::class, 'create'])->name('akun.create');
        Route::post('/akun', [UserController::class, 'store'])->name('akun.store');
        Route::get('/akun/{akun}/ubah', [UserController::class, 'edit'])->name('akun.edit');
        Route::put('/akun/{akun}', [UserController::class, 'update'])->name('akun.update');
        Route::patch('/akun/{akun}/status', [UserController::class, 'toggle'])->name('akun.toggle');
        Route::patch('/akun/{akun}/reset-sandi', [UserController::class, 'resetPassword'])->name('akun.reset-password');
    });

    Route::middleware('can:kelola-pengaturan')->group(function () {
        Route::get('/pengaturan', [SettingController::class, 'edit'])->name('pengaturan.edit');
        Route::put('/pengaturan', [SettingController::class, 'update'])->name('pengaturan.update');
    });

    Route::middleware('can:lihat-log')->group(function () {
        Route::get('/log-aktivitas', [ActivityLogController::class, 'index'])->name('log-aktivitas.index');
    });

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
});

require __DIR__.'/auth.php';
