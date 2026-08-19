<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DetailDataController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\P2tlController;
use App\Http\Controllers\TrendController;
use App\Http\Controllers\EditTargetController;
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

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifikasi/{id}/baca', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'readAll'])->name('notifications.readAll');

    /*
    |----------------------------------------------------------------------
    | Trend — Trend kWh & Trend Rp TS (filter Tahun/ULP, mode Bulanan/Kumulatif)
    |----------------------------------------------------------------------
    */
    Route::prefix('trend')->name('trend.')->group(function () {
        Route::get('/kwh', [TrendController::class, 'kwh'])->name('kwh');
        Route::get('/ts', [TrendController::class, 'ts'])->name('ts');
    });

    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/', [LaporanController::class, 'index'])->name('index');
        Route::get('/upload', [LaporanController::class, 'create'])->name('create');
        Route::post('/upload', [LaporanController::class, 'store'])->name('store');
        Route::get('/{laporan}/riwayat', [LaporanController::class, 'riwayat'])->name('riwayat');
        Route::post('/{laporan}/aktifkan', [LaporanController::class, 'aktifkan'])->name('aktifkan');
        Route::get('/{laporan}/export', [LaporanController::class, 'export'])->name('export');

        // Wildcard routes selalu paling bawah di dalam group ini
        Route::get('/{laporan}', [DetailDataController::class, 'show'])->name('show');
        Route::delete('/{laporan}', [LaporanController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('data-detail')->name('detail-data.')->group(function () {
        Route::get('/', [DetailDataController::class, 'index'])->name('index');
        Route::get('/{detail}', [DetailDataController::class, 'showDetail'])->name('show');
        Route::get('/{detail}/edit', [DetailDataController::class, 'edit'])->name('edit');
        Route::put('/{detail}', [DetailDataController::class, 'update'])->name('update');
        Route::delete('/{detail}', [DetailDataController::class, 'destroy'])->name('destroy');
    });

    Route::middleware('super_admin')->prefix('manajemen-user')->name('admin.users.')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::get('/{user}', [UserManagementController::class, 'show'])->name('show');
        Route::patch('/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('toggle');
    });

    Route::get('/edit-target', [EditTargetController::class, 'index'])->name('edit-target.index');
    Route::post('/edit-target', [EditTargetController::class, 'update'])->name('edit-target.update');
    
    Route::prefix('trend')->name('trend.')->group(function () {
    Route::get('/kwh', [TrendController::class, 'kwh'])->name('kwh');
    Route::get('/ts', [TrendController::class, 'ts'])->name('ts');
    Route::get('/pencapaian', [TrendController::class, 'pencapaian'])->name('pencapaian');
    });
});

