<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\Admin\AssignmentController;

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

    // ==============================================================
    // PBI 13 & 15: Verifikasi & Penugasan Teknisi (Andi)
    // ==============================================================
    Route::prefix('admin')->name('admin.')->group(function () {
        // PBI 13: Verifikasi Laporan Kerusakan
        Route::get('/verifikasi', [VerificationController::class, 'index'])->name('verification.index');
        Route::post('/verifikasi/{id}/verify', [VerificationController::class, 'verify'])->name('verification.verify');
        Route::post('/verifikasi/{id}/reject', [VerificationController::class, 'reject'])->name('verification.reject');

        // PBI 15: Penugasan Laporan kepada Teknisi
        Route::get('/penugasan', [AssignmentController::class, 'create'])->name('assignment.create');
        Route::post('/penugasan/{id}/assign', [AssignmentController::class, 'store'])->name('assignment.store');
    });
});
