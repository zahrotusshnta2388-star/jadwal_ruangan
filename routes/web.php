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
});













// Debug route
Route::get('/debug-upload', function () {
    // Buat file CSV sample
    $sampleCSV = "No,Keterangan,Prodi,Smt,gol,Kode,MK,SKS,Dosen Koordinator,Team Taching 1,Team Taching 2,Team Taching 3,Team Taching 4,Teknisi,Teknisi,Hari,Jam,Ruang\n";
    $sampleCSV .= "1,Jember,TIF,3,A,TIF130702,Matematika Diskrit,2,\"Moh. Munih Dian W., S.Kom, MT\",\"Dr. Denny Trias Utomo, S.Si., M.T.\",,,,,,,Senin,07.00 - 09.00,3.1\n";
    $sampleCSV .= "2,Jember,TIF,3,B,TIF130702,Matematika Diskrit,2,\"Moh. Munih Dian W., S.Kom, MT\",\"Dr. Denny Trias Utomo, S.Si., M.T.\",,,,,,,Senin,07.00 - 09.00,3.2\n";

    // Simpan ke storage
    Storage::put('test_sample.csv', $sampleCSV);

    // Test parse
    $controller = new \App\Http\Controllers\AdminController();
    $file = new \Illuminate\Http\UploadedFile(
        storage_path('app/test_sample.csv'),
        'test_sample.csv',
        'text/csv',
        null,
        true
    );

    $result = $controller->parseCSVFromUpload($file);

    dd([
        'sample_file_created' => true,
        'path' => storage_path('app/test_sample.csv'),
        'exists' => file_exists(storage_path('app/test_sample.csv')),
        'content' => $sampleCSV,
        'parsed_data' => $result,
        'count' => count($result),
        'first_row' => !empty($result) ? $result[0] : null
    ]);
});




Route::get('/test-my-csv', function () {
    // Ganti dengan path file CSV Anda
    $yourFilePath = 'C:/path/to/your/file.csv'; // Windows
    // $yourFilePath = '/home/user/your/file.csv'; // Linux/Mac

    if (!file_exists($yourFilePath)) {
        return "File tidak ditemukan di: {$yourFilePath}";
    }

    $content = file_get_contents($yourFilePath);

    echo "<h3>File Info:</h3>";
    echo "Path: {$yourFilePath}<br>";
    echo "Size: " . filesize($yourFilePath) . " bytes<br>";
    echo "Encoding: " . mb_detect_encoding($content) . "<br>";

    echo "<h3>First 500 characters:</h3>";
    echo "<pre>" . htmlspecialchars(substr($content, 0, 500)) . "</pre>";

    echo "<h3>Parsing test:</h3>";

    // Test parse dengan fgetcsv
    $handle = fopen($yourFilePath, 'r');
    if ($handle) {
        $headers = fgetcsv($handle);
        echo "Headers: " . implode(', ', $headers) . "<br>";

        $firstRow = fgetcsv($handle);
        echo "First row: " . implode(', ', $firstRow) . "<br>";

        fclose($handle);
    }

    // Test dengan str_getcsv
    $lines = file($yourFilePath, FILE_IGNORE_NEW_LINES);
    echo "<h4>Line count: " . count($lines) . "</h4>";

    if (!empty($lines[0])) {
        $testHeaders = str_getcsv($lines[0]);
        echo "<h4>str_getcsv Headers:</h4>";
        echo "<pre>";
        print_r($testHeaders);
        echo "</pre>";
    }
});
