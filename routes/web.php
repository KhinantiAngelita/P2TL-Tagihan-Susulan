<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DetailDataController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\P2tlController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest routes (login, register, forgot password)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated routes (dashboard & laporan)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/laporan/{laporan}', [DetailDataController::class, 'show'])->name('laporan.show');

    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/', [LaporanController::class, 'index'])->name('index');
        Route::get('/upload', [LaporanController::class, 'create'])->name('create');
        Route::post('/upload', [LaporanController::class, 'store'])->name('store');
        //Route::get('/{laporan}', [LaporanController::class, 'show'])->name('show');
        Route::get('/{laporan}/riwayat', [LaporanController::class, 'riwayat'])->name('riwayat');
        Route::post('/{laporan}/aktifkan', [LaporanController::class, 'aktifkan'])->name('aktifkan');
        Route::delete('/{laporan}', [LaporanController::class, 'destroy'])->name('destroy');
    });

    // ---- Route baru: export & CRUD baris data detail (halaman "Detail Laporan") ----
        Route::get('/{laporan}/export', [LaporanController::class, 'export'])->name('export');

        Route::get('/data-detail', [DetailDataController::class, 'index'])->name('detail-data.index');

        Route::get('/detail/{detail}', [DetailDataController::class, 'showDetail'])->name('detail-data.show');
        Route::get('/detail/{detail}/edit', [DetailDataController::class, 'edit'])->name('detail-data.edit');
        Route::put('/detail/{detail}', [DetailDataController::class, 'update'])->name('detail-data.update');
        Route::delete('/detail/{detail}', [DetailDataController::class, 'destroy'])->name('detail-data.destroy');

    
});