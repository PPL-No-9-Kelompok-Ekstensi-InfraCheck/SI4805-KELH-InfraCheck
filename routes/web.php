<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DeviceTypeController;
use App\Http\Controllers\InfrastructureCategoryController;
use App\Http\Controllers\RepairProgressController;
use App\Http\Controllers\RepairScheduleController;
use App\Http\Controllers\ReportCommentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportStatusController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UserController;
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
    Route::middleware('permission:dashboard.view')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

    // PBI 1 - Pengelolaan Data Pengguna
    Route::middleware('permission:users.view')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
    });

    Route::middleware('permission:users.create')->group(function () {
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
    });

    Route::middleware('permission:users.update')->group(function () {
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    });

    Route::middleware('permission:users.delete')->group(function () {
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // PBI 3 - Pengelolaan Hak Akses Pengguna
    Route::middleware('permission:access.view,access.update')->group(function () {
        Route::get('/access', [AccessController::class, 'index'])->name('access.index');
    });

    Route::middleware('permission:access.update')->group(function () {
        Route::put('/access', [AccessController::class, 'update'])->name('access.update');
    });

    // PBI 4 & 5 - Gedung dan Ruangan
    Route::resource('buildings', BuildingController::class)
        ->except(['show'])
        ->middleware('can:manage-buildings');

    Route::resource('rooms', RoomController::class)
        ->except(['show'])
        ->middleware('can:manage-rooms');

    // PBI 6 - Kategori Infrastruktur (Admin aktif dan hak akses per aksi)
    Route::resource('categories', InfrastructureCategoryController::class)
        ->except(['show'])
        ->middleware('can:manage-categories')
        ->middlewareFor('index', 'permission:categories.view')
        ->middlewareFor(['create', 'store'], 'permission:categories.create')
        ->middlewareFor(['edit', 'update'], 'permission:categories.update')
        ->middlewareFor('destroy', 'permission:categories.delete');

    // PBI 7 & 8 - Jenis Perangkat dan Perangkat (Admin)
    Route::middleware('role:Admin')->group(function () {
        Route::resource('device-types', DeviceTypeController::class)->except('show');
        Route::resource('devices', DeviceController::class)->except('show');
    });

    // PBI 10 & 11 - Pembuatan dan Pemantauan Laporan Kerusakan (Pelapor)
    Route::get('/laporan/buat', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/laporan', [ReportController::class, 'store'])->name('reports.store');
    Route::get('/laporan/cek-duplikat', [ReportController::class, 'check'])->name('reports.check');
    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');

    // PBI 19 - Pemantauan Status Laporan (Admin dan Pimpinan)
    Route::middleware('role:Admin,Pimpinan')->group(function () {
        Route::get('/laporan/status', [ReportStatusController::class, 'index'])->name('reports.status');
    });

    // Detail laporan (ditempatkan setelah route statis /laporan/buat, /laporan/cek-duplikat, /laporan/status)
    Route::get('/laporan/{laporan}', [ReportController::class, 'show'])->name('reports.show');

    // PBI 20 - Pemberian Tanggapan pada Laporan
    Route::get('/laporan/{report}/tanggapan', [ReportCommentController::class, 'create'])->name('reports.comments.create');
    Route::post('/laporan/{report}/tanggapan', [ReportCommentController::class, 'store'])->name('reports.comments.store');
    Route::put('/laporan/{report}/tanggapan/{comment}', [ReportCommentController::class, 'update'])->name('reports.comments.update');
    Route::delete('/laporan/{report}/tanggapan/{comment}', [ReportCommentController::class, 'destroy'])->name('reports.comments.destroy');

    // PBI 13 & 15 - Verifikasi & Penugasan Teknisi (Admin)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/verifikasi', [VerificationController::class, 'index'])->name('verification.index');
        Route::post('/verifikasi/{id}/verify', [VerificationController::class, 'verify'])->name('verification.verify');
        Route::post('/verifikasi/{id}/reject', [VerificationController::class, 'reject'])->name('verification.reject');

        Route::get('/penugasan', [AssignmentController::class, 'create'])->name('assignment.create');
        Route::post('/penugasan/{id}/assign', [AssignmentController::class, 'store'])->name('assignment.store');
    });

    // PBI 16 - Mengelola Progres Perbaikan
    Route::get('/progres-perbaikan', [RepairProgressController::class, 'index'])->name('progress.index');
    Route::get('/progres-perbaikan/{assignment}', [RepairProgressController::class, 'show'])->name('progress.show');
    Route::get('/progres-perbaikan/{assignment}/progres/create', [RepairProgressController::class, 'create'])->name('progress.create');
    Route::post('/progres-perbaikan/{assignment}/progres', [RepairProgressController::class, 'store'])->name('progress.store');
    Route::get('/progres-perbaikan/{assignment}/progres/{progress}/edit', [RepairProgressController::class, 'edit'])->name('progress.edit');
    Route::put('/progres-perbaikan/{assignment}/progres/{progress}', [RepairProgressController::class, 'update'])->name('progress.update');

    // PBI 17 - Mengelola Jadwal Perbaikan
    Route::get('/jadwal-perbaikan', [RepairScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/jadwal-perbaikan/create', [RepairScheduleController::class, 'create'])->name('schedules.create');
    Route::post('/jadwal-perbaikan', [RepairScheduleController::class, 'store'])->name('schedules.store');
    Route::get('/jadwal-perbaikan/{schedule}/edit', [RepairScheduleController::class, 'edit'])->name('schedules.edit');
    Route::put('/jadwal-perbaikan/{schedule}', [RepairScheduleController::class, 'update'])->name('schedules.update');
    Route::delete('/jadwal-perbaikan/{schedule}', [RepairScheduleController::class, 'destroy'])->name('schedules.destroy');
});
