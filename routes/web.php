<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RuanganController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Storage;

// Home
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/home', [HomeController::class, 'index'])->name('home.index');

// Ruangan
Route::get('/ruangan', [RuanganController::class, 'index'])->name('ruangan.index');

// Admin
Route::prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');
    Route::get('/upload', [AdminController::class, 'upload'])->name('admin.upload');
    Route::post('/upload', [AdminController::class, 'processUpload'])->name('admin.upload.process');
    Route::get('/download-template', [AdminController::class, 'downloadTemplate'])->name('admin.download.template');
    Route::post('/generate-jadwal', [AdminController::class, 'generateJadwal'])->name('admin.generate.jadwal');
});
