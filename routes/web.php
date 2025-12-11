<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RuanganController;
use App\Http\Controllers\AdminController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Remove or comment any existing auth routes
// Route::get('/', function () {
//     return view('welcome');
// });

// Simple routes without auth
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/home', [HomeController::class, 'index'])->name('home.index');

// Ruangan routes
Route::get('/ruangan', [RuanganController::class, 'index'])->name('ruangan.index');

// Admin routes
Route::prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');
    Route::get('/upload', [AdminController::class, 'upload'])->name('admin.upload');
    Route::post('/upload', [AdminController::class, 'processUpload'])->name('admin.upload.process');
});
