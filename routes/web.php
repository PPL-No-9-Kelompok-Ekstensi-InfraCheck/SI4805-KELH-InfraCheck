<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RepairProgressController;
use App\Http\Controllers\RepairScheduleController;
use App\Http\Controllers\Technician\ProgressController;
use App\Http\Controllers\Technician\ScheduleController;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard or login
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Authentication routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ==========================================
    // PBI 16 — Mengelola Progres Perbaikan
    // ==========================================
    Route::get('/teknisi/tugas', [RepairProgressController::class, 'index'])->name('progress.index');
    Route::get('/teknisi/tugas/{assignment}', [ProgressController::class, 'show'])->name('progress.show');
    Route::post('/teknisi/tugas/{assignment}/progres', [ProgressController::class, 'store'])->name('progress.store');

    // Kompatibilitas URL progres
    Route::get('/progres-perbaikan', [RepairProgressController::class, 'index']);
    Route::get('/progres-perbaikan/{assignment}', [ProgressController::class, 'show']);
    Route::get('/progres-perbaikan/{assignment}/progres/create', [RepairProgressController::class, 'create'])->name('progress.create');
    Route::post('/progres-perbaikan/{assignment}/progres', [ProgressController::class, 'store']);
    Route::get('/progres-perbaikan/{assignment}/progres/{progress}/edit', [RepairProgressController::class, 'edit'])->name('progress.edit');
    Route::put('/progres-perbaikan/{assignment}/progres/{progress}', [RepairProgressController::class, 'update'])->name('progress.update');

    // ==========================================
    // PBI 17 — Menjadwalkan Perbaikan
    // ==========================================
    Route::get('/teknisi/jadwal', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::post('/teknisi/jadwal/{assignment}', [ScheduleController::class, 'store'])->name('schedule.store');
    Route::put('/teknisi/jadwal/{assignment}', [ScheduleController::class, 'update'])->name('schedule.update');

    // Kompatibilitas URL jadwal umum
    Route::get('/jadwal-perbaikan', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/jadwal-perbaikan/create', [ScheduleController::class, 'create'])->name('schedules.create');
    Route::post('/jadwal-perbaikan', [ScheduleController::class, 'storeScheduleFromAdmin'])->name('schedules.store');
    Route::get('/jadwal-perbaikan/{schedule}/edit', [ScheduleController::class, 'edit'])->name('schedules.edit');
    Route::put('/jadwal-perbaikan/{schedule}', [ScheduleController::class, 'updateScheduleFromAdmin'])->name('schedules.update');
    Route::delete('/jadwal-perbaikan/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');
});
