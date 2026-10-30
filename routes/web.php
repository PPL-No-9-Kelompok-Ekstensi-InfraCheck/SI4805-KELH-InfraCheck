<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportStatusController;

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
});

// Pemantauan status laporan (PBI 19) — hanya Admin dan Pimpinan.
Route::middleware(['auth', 'role:Admin,Pimpinan'])->group(function () {
    Route::get('/laporan/status', [ReportStatusController::class, 'index'])->name('reports.status');
    Route::get('/laporan/{report}', [ReportStatusController::class, 'show'])->name('reports.show');
});
