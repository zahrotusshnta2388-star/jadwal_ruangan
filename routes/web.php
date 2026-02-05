<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RuanganController;
use App\Http\Controllers\AdminController;


// Ruangan - HARUS DI ATAS
Route::get('/ruangan', [RuanganController::class, 'index'])->name('ruangan.index');
Route::get('/ruangan/create', [RuanganController::class, 'create'])->name('ruangan.create');
Route::post('/ruangan/store', [RuanganController::class, 'store'])->name('ruangan.store');
Route::get('/ruangan/edit/{id}', [RuanganController::class, 'edit'])->name('ruangan.edit');
Route::put('/ruangan/update/{id}', [RuanganController::class, 'update'])->name('ruangan.update');
Route::delete('/ruangan/delete/{id}', [RuanganController::class, 'destroy'])->name('ruangan.destroy');

// Home
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/home', [HomeController::class, 'index'])->name('home.index');

// Admin
Route::prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');
    Route::get('/upload', [AdminController::class, 'upload'])->name('admin.upload');
    Route::post('/upload', [AdminController::class, 'processUpload'])->name('admin.upload.process');
    Route::get('/download-template', [AdminController::class, 'downloadTemplate'])->name('admin.download.template');
    Route::post('/generate-jadwal', [AdminController::class, 'generateJadwal'])->name('admin.generate.jadwal');
});
