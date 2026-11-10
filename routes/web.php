<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
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

// Protected dashboard route
Route::middleware('auth')->group(function () {
    Route::middleware('permission:dashboard.view')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

    /*
    |--------------------------------------------------------------------------
    | PBI 1 - Pengelolaan Data Pengguna
    |--------------------------------------------------------------------------
    | Setiap aksi dijaga oleh hak akses granular (PBI 3): users.view,
    | users.create, users.update, dan users.delete.
    */
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

    /*
    |--------------------------------------------------------------------------
    | PBI 3 - Pengelolaan Hak Akses Pengguna
    |--------------------------------------------------------------------------
    | Halaman dapat dilihat oleh pemilik akses Lihat/Ubah Hak Akses, sedangkan
    | penyimpanan perubahan hanya untuk pemilik akses Ubah Hak Akses.
    */
    Route::middleware('permission:access.view,access.update')->group(function () {
        Route::get('/access', [AccessController::class, 'index'])->name('access.index');
    });

    Route::middleware('permission:access.update')->group(function () {
        Route::put('/access', [AccessController::class, 'update'])->name('access.update');
    });
});
