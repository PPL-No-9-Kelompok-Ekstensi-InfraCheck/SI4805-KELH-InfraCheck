<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RepairProgressController;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard or login
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Authentication routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected dashboard route
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // PBI 16 — Mengelola Progres Perbaikan
    Route::get('/progres-perbaikan', [RepairProgressController::class, 'index'])->name('progress.index');
    Route::get('/progres-perbaikan/{assignment}', [RepairProgressController::class, 'show'])->name('progress.show');
    Route::get('/progres-perbaikan/{assignment}/progres/create', [RepairProgressController::class, 'create'])->name('progress.create');
    Route::post('/progres-perbaikan/{assignment}/progres', [RepairProgressController::class, 'store'])->name('progress.store');
    Route::get('/progres-perbaikan/{assignment}/progres/{progress}/edit', [RepairProgressController::class, 'edit'])->name('progress.edit');
    Route::put('/progres-perbaikan/{assignment}/progres/{progress}', [RepairProgressController::class, 'update'])->name('progress.update');
});
