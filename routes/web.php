<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ConsignmentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlaceholderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SupplierController;
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
     | Pemasok, produk, pembelian, dan stok.
     | Pemantau boleh melihat daftar; tombol tambah/ubah disembunyikan lewat Gate.
     */
    Route::get('/pemasok', [SupplierController::class, 'index'])->name('pemasok.index');
    Route::get('/pemasok/tambah', [SupplierController::class, 'create'])->name('pemasok.create');
    Route::post('/pemasok', [SupplierController::class, 'store'])->name('pemasok.store');
    Route::get('/pemasok/{pemasok}', [SupplierController::class, 'show'])->name('pemasok.show');
    Route::get('/pemasok/{pemasok}/ubah', [SupplierController::class, 'edit'])->name('pemasok.edit');
    Route::put('/pemasok/{pemasok}', [SupplierController::class, 'update'])->name('pemasok.update');
    Route::delete('/pemasok/{pemasok}', [SupplierController::class, 'destroy'])->name('pemasok.destroy');

    Route::get('/produk', [ProductController::class, 'index'])->name('produk.index');
    Route::get('/produk/tambah', [ProductController::class, 'create'])->name('produk.create');
    Route::post('/produk', [ProductController::class, 'store'])->name('produk.store');
    Route::get('/produk/{produk}', [ProductController::class, 'show'])->name('produk.show');
    Route::get('/produk/{produk}/ubah', [ProductController::class, 'edit'])->name('produk.edit');
    Route::put('/produk/{produk}', [ProductController::class, 'update'])->name('produk.update');
    Route::delete('/produk/{produk}', [ProductController::class, 'destroy'])->name('produk.destroy');

    Route::get('/pembelian', [PurchaseController::class, 'index'])->name('pembelian.index');
    Route::get('/pembelian/tambah', [PurchaseController::class, 'create'])->name('pembelian.create');
    Route::post('/pembelian', [PurchaseController::class, 'store'])->name('pembelian.store');
    Route::get('/pembelian/{pembelian}', [PurchaseController::class, 'show'])->name('pembelian.show');
    Route::delete('/pembelian/{pembelian}', [PurchaseController::class, 'destroy'])->name('pembelian.destroy');

    Route::get('/stok', [StockController::class, 'index'])->name('stok.index');
    Route::get('/stok/penyesuaian', [StockController::class, 'createPenyesuaian'])->name('stok.penyesuaian.create');
    Route::post('/stok/penyesuaian', [StockController::class, 'storePenyesuaian'])->name('stok.penyesuaian.store');
    Route::get('/stok/{produk}', [StockController::class, 'show'])->name('stok.show');

    /*
     | Halaman sementara. Setiap baris diganti controller sungguhan pada
     | tahap yang disebut di PlaceholderController.
     */
    Route::get('/pengiriman', [ConsignmentController::class, 'index'])->name('pengiriman.index');
    Route::get('/pengiriman/tambah', [ConsignmentController::class, 'create'])->name('pengiriman.create');
    Route::post('/pengiriman', [ConsignmentController::class, 'store'])->name('pengiriman.store');
    Route::get('/pengiriman/{pengiriman}', [ConsignmentController::class, 'show'])->name('pengiriman.show');
    Route::post('/pengiriman/{pengiriman}/rekonsiliasi', [ConsignmentController::class, 'rekonsiliasi'])->name('pengiriman.rekonsiliasi');
    Route::get('/pengiriman/{pengiriman}/surat-jalan', [ConsignmentController::class, 'suratJalan'])->name('pengiriman.surat-jalan');
    Route::get('/pengiriman/{pengiriman}/struk', [ConsignmentController::class, 'struk'])->name('pengiriman.struk');
    Route::delete('/pengiriman/{pengiriman}', [ConsignmentController::class, 'destroy'])->name('pengiriman.destroy');
    Route::get('/tagihan', [InvoiceController::class, 'index'])->name('tagihan.index');
    Route::get('/tagihan/buat', [InvoiceController::class, 'create'])->name('tagihan.create');
    Route::post('/tagihan', [InvoiceController::class, 'store'])->name('tagihan.store');
    Route::get('/tagihan/{tagihan}', [InvoiceController::class, 'show'])->name('tagihan.show');
    Route::get('/tagihan/{tagihan}/cetak', [InvoiceController::class, 'cetak'])->name('tagihan.cetak');
    Route::get('/tagihan/{tagihan}/struk', [InvoiceController::class, 'struk'])->name('tagihan.struk');
    Route::patch('/tagihan/{tagihan}/batalkan', [InvoiceController::class, 'batalkan'])->name('tagihan.batalkan');
    Route::post('/tagihan/{tagihan}/pembayaran', [PaymentController::class, 'store'])->name('pembayaran.store');
    Route::get('/pembayaran/{pembayaran}/kuitansi', [PaymentController::class, 'kuitansi'])->name('pembayaran.kuitansi');
    Route::delete('/pembayaran/{pembayaran}', [PaymentController::class, 'destroy'])->name('pembayaran.destroy');
    Route::get('/toko', [StoreController::class, 'index'])->name('toko.index');
    Route::get('/toko/tambah', [StoreController::class, 'create'])->name('toko.create');
    Route::post('/toko', [StoreController::class, 'store'])->name('toko.store');
    Route::get('/toko/{toko}', [StoreController::class, 'show'])->name('toko.show');
    Route::get('/toko/{toko}/ubah', [StoreController::class, 'edit'])->name('toko.edit');
    Route::put('/toko/{toko}', [StoreController::class, 'update'])->name('toko.update');
    Route::delete('/toko/{toko}', [StoreController::class, 'destroy'])->name('toko.destroy');
    Route::get('/toko/{toko}/harga', [StoreController::class, 'harga'])->name('toko.harga');
    Route::post('/toko/{toko}/harga', [StoreController::class, 'simpanHarga'])->name('toko.harga.simpan');
    Route::delete('/toko/{toko}/harga/{harga}', [StoreController::class, 'hapusHarga'])->name('toko.harga.hapus');
    Route::get('/biaya', PlaceholderController::class)->name('biaya.index');
    Route::get('/kas', PlaceholderController::class)->name('kas.index');
    Route::get('/laporan', PlaceholderController::class)->name('laporan.index');
});

require __DIR__.'/auth.php';
