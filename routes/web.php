<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DetailDataController;
use App\Http\Controllers\LaporanController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/data-detail', [DetailDataController::class, 'index'])->name('detail-data.index');

Route::prefix('laporan')->name('laporan.')->group(function () {
    Route::get('/', [LaporanController::class, 'index'])->name('index');
    Route::get('/upload', [LaporanController::class, 'create'])->name('create');
    Route::post('/upload', [LaporanController::class, 'store'])->name('store');
    Route::get('/{laporan}', [LaporanController::class, 'show'])->name('show');
    Route::delete('/{laporan}', [LaporanController::class, 'destroy'])->name('destroy');
});