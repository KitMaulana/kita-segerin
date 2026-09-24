<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute masuk dan kata sandi
|--------------------------------------------------------------------------
|
| Tidak ada pendaftaran mandiri: akun hanya dibuat oleh pemilik usaha lewat
| menu "Akun Admin" (Tahap 3). Verifikasi email juga tidak dipakai karena
| email bersifat opsional.
|
*/

Route::middleware('guest')->group(function () {
    Route::get('masuk', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('masuk', [AuthenticatedSessionController::class, 'store']);

    Route::get('lupa-sandi', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('lupa-sandi', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('atur-sandi/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('atur-sandi', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('konfirmasi-sandi', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('konfirmasi-sandi', [ConfirmablePasswordController::class, 'store']);

    Route::put('sandi', [PasswordController::class, 'update'])->name('password.update');

    Route::post('keluar', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
