<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RuanganController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Public Routes - Tidak perlu login
|--------------------------------------------------------------------------
*/
// Login routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Home
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/home', [HomeController::class, 'index'])->name('home.index');

// Ruangan - BISA DIAKSES TANPA LOGIN
Route::get('/ruangan', [RuanganController::class, 'index'])->name('ruangan.index');
Route::get('/ruangan/detail/{id}', [RuanganController::class, 'detail'])->name('ruangan.detail');

// AJAX routes for ruangan (public)
Route::get('/ruangan/get-mata-kuliah', [RuanganController::class, 'getMataKuliah'])->name('ruangan.getMataKuliah');
Route::get('/ruangan/get-dosen-pengampu', [RuanganController::class, 'getDosenPengampu'])->name('ruangan.getDosenPengampu');
Route::get('/ruangan/get-teknisi', [RuanganController::class, 'getTeknisi'])->name('ruangan.getTeknisi');

/*
|--------------------------------------------------------------------------
| Protected Routes - Hanya untuk yang sudah login (tidak harus admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // CRUD Ruangan - perlu login
    Route::get('/ruangan/create', [RuanganController::class, 'create'])->name('ruangan.create');
    Route::post('/ruangan/store', [RuanganController::class, 'store'])->name('ruangan.store');
    Route::get('/ruangan/edit/{id}', [RuanganController::class, 'edit'])->name('ruangan.edit');
    Route::put('/ruangan/update/{id}', [RuanganController::class, 'update'])->name('ruangan.update');
    Route::delete('/ruangan/delete/{id}', [RuanganController::class, 'destroy'])->name('ruangan.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin Only Routes - Hanya untuk admin yang sudah login
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->group(function () {
    // Admin
    Route::prefix('admin')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('admin.index');
        Route::get('/upload', [AdminController::class, 'upload'])->name('admin.upload');
        Route::post('/upload', [AdminController::class, 'processUpload'])->name('admin.upload.process');
        Route::get('/download-template', [AdminController::class, 'downloadTemplate'])->name('admin.download.template');
        Route::post('/generate-jadwal', [AdminController::class, 'generateJadwal'])->name('admin.generate.jadwal');
    });
});
