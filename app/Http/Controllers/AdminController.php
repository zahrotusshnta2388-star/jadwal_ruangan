<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Jadwal;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /**
     * Display admin dashboard.
     */
    public function index()
    {
        // Get statistics from database
        $totalJadwal = Jadwal::count();
        $latestJadwal = Jadwal::latest('created_at')->first();
        $uniqueRooms = Jadwal::distinct('ruangan')->count('ruangan');
        $uniqueProdi = Jadwal::distinct('prodi')->count('prodi');

        $data = [
            'pageTitle' => 'Admin Panel',
            'totalJadwal' => $totalJadwal,
            'latestDate' => $latestJadwal ? $latestJadwal->tanggal : 'Belum ada data',
            'totalRuangan' => $uniqueRooms,
            'totalProdi' => $uniqueProdi,
            'recentUploads' => $this->getRecentUploads()
        ];

        return view('admin.index', $data);
    }

    /**
     * Display upload form.
     */
    public function upload()
    {
        return view('admin.upload', [
            'pageTitle' => 'Upload Data CSV'
        ]);
    }

    /**
     * Process CSV upload.
     */
    public function processUpload(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'csv_file' => 'required|file|mimes:csv,txt|max:10240',
                'tanggal_efektif' => 'required|date',
                'action' => 'required|in:append,replace'
            ]);

            // Get file
            $file = $request->file('csv_file');

            // Debug: Log file info
            \Log::info('=== CSV UPLOAD START ===');
            \Log::info('File original name:', [$file->getClientOriginalName()]);
            \Log::info('File size:', [$file->getSize()]);
            \Log::info('File mime type:', [$file->getMimeType()]);
            \Log::info('File extension:', [$file->getClientOriginalExtension()]);
            \Log::info('File real path:', [$file->getRealPath()]);

            // Method 1: Simpan file temporary dengan cara yang benar
            $tempPath = $file->store('temp');
            \Log::info('File stored at:', [$tempPath]);
            \Log::info('Storage path:', [storage_path('app/' . $tempPath)]);
            \Log::info('File exists?', [file_exists(storage_path('app/' . $tempPath))]);

            // Baca file langsung dari uploaded file
            $csvContent = file_get_contents($file->getRealPath());
            \Log::info('File content length:', [strlen($csvContent)]);
            \Log::info('First 200 chars:', [substr($csvContent, 0, 200)]);

            // Method 2: Parse langsung dari uploaded file
            $csvData = $this->parseCSVFromUpload($file);

            \Log::info('Parsed data count:', [count($csvData)]);

            if (empty($csvData)) {
                // Coba method alternatif
                $csvData = $this->parseCSVAlternative($file);
                \Log::info('Alternative parse count:', [count($csvData)]);

                if (empty($csvData)) {
                    // Clean up
                    Storage::delete($tempPath);

                    return back()
                        ->with('error', 'File CSV kosong atau format tidak sesuai. ' .
                            'Pastikan file memiliki header dan data.')
                        ->with('debug_info', [
                            'file_name' => $file->getClientOriginalName(),
                            'file_size' => $file->getSize(),
                            'temp_path' => $tempPath,
                            'content_sample' => substr($csvContent, 0, 500)
                        ]);
                }
            }

            // Get other inputs
            $tanggalEfektif = $request->input('tanggal_efektif');
            $action = $request->input('action');
            $semester = $request->input('semester');
            $prodi = $request->input('prodi');

            // Filter data jika ada filter
            if (!empty($semester) || !empty($prodi)) {
                $csvData = $this->filterCSVData($csvData, $semester, $prodi);
            }

            // Jika action = replace, hapus data lama untuk tanggal tersebut
            if ($action === 'replace') {
                $deletedCount = Jadwal::deleteByDate($tanggalEfektif);
                \Log::info("Deleted {$deletedCount} old records for date: {$tanggalEfektif}");
            }

            // Import data ke database
            $importResult = Jadwal::importFromCSV($csvData, $tanggalEfektif);

            // Save upload log
            $this->saveUploadLog($file->getClientOriginalName(), $importResult, $tanggalEfektif, $action);

            // Clean up temporary file
            Storage::delete($tempPath);

            \Log::info('Import result:', [$importResult]);
            \Log::info('=== CSV UPLOAD END ===');

            // Prepare response data
            $responseData = [
                'success' => $importResult['success_count'],
                'failed' => $importResult['failed_count'],
                'total' => $importResult['total_rows'],
                'failed_rows' => array_slice($importResult['failed_rows'], 0, 10)
            ];

            // Return with success message
            return redirect()
                ->route('admin.upload')
                ->with('success', "Import berhasil! {$importResult['success_count']} dari {$importResult['total_rows']} data berhasil diimport.")
                ->with('import_result', $responseData);
        } catch (\Exception $e) {
            \Log::error('CSV Upload Error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            return back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Parse CSV langsung dari uploaded file
     */
    private function parseCSVFromUpload($uploadedFile)
    {
        $data = [];

        // Baca seluruh konten file
        $content = file_get_contents($uploadedFile->getRealPath());

        // Remove BOM jika ada
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);

        // Deteksi delimiter dari baris pertama
        $lines = explode("\n", $content);
        if (empty($lines)) {
            return $data;
        }

        $firstLine = trim($lines[0]);
        $delimiter = $this->detectDelimiter($firstLine);

        \Log::info("Detected delimiter: '{$delimiter}'");

        // Parse menggunakan str_getcsv
        $headers = str_getcsv($firstLine, $delimiter);
        \Log::info('Original Headers:', $headers);

        // Clean headers - PERBAIKAN DI SINI
        $headers = array_map(function ($header) {
            $header = trim($header, " \t\n\r\0\x0B\"'");
            // Fix common typos in headers
            $header = str_replace('Taching', 'Teaching', $header); // Fix typo
            return $header;
        }, $headers);

        \Log::info('Cleaned Headers:', $headers);

        // Parse data rows
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) {
                continue;
            }

            $row = str_getcsv($line, $delimiter);

            // Jika jumlah kolom tidak sama, adjust
            if (count($row) > count($headers)) {
                $row = array_slice($row, 0, count($headers));
            } elseif (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), '');
            }

            // Clean values
            $row = array_map(function ($value) {
                return trim($value, " \t\n\r\0\x0B\"'");
            }, $row);

            // Combine dengan headers
            $rowData = array_combine($headers, $row);

            // Debug first row
            if ($i === 1) {
                \Log::info('First data row after combine:', $rowData);
                \Log::info('Available keys:', array_keys($rowData));
            }

            $data[] = $rowData;
        }

        \Log::info("Total rows parsed: " . count($data));

        return $data;
    }

    /**
     * Parse CSV alternative method
     */
    private function parseCSVAlternative($uploadedFile)
    {
        $data = [];

        // Coba dengan fgetcsv
        $handle = fopen($uploadedFile->getRealPath(), 'r');
        if (!$handle) {
            return $data;
        }

        // Baca header
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return $data;
        }

        \Log::info('Alternative parse headers:', $headers);

        // Clean headers
        $headers = array_map(function ($header) {
            return trim($header, " \t\n\r\0\x0B\"'");
        }, $headers);

        // Baca data
        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Adjust row length
            if (count($row) > count($headers)) {
                $row = array_slice($row, 0, count($headers));
            } elseif (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), '');
            }

            // Combine dengan headers
            $rowData = array_combine($headers, $row);
            $data[] = $rowData;
        }

        fclose($handle);

        return $data;
    }

    /**
     * Simple CSV parser untuk testing
     */
    private function simpleCSVParser($filePath)
    {
        $data = [];

        if (!file_exists($filePath)) {
            \Log::error("File tidak ditemukan: {$filePath}");
            return $data;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (count($lines) < 2) {
            return $data;
        }

        // Header
        $headers = str_getcsv($lines[0]);

        // Data
        for ($i = 1; $i < count($lines); $i++) {
            $row = str_getcsv($lines[$i]);
            if (count($row) === count($headers)) {
                $data[] = array_combine($headers, $row);
            }
        }

        return $data;
    }


    /**
     * Debug CSV file content
     */
    private function debugCSVFile($filePath)
    {
        if (!file_exists($filePath)) {
            return ['error' => 'File tidak ditemukan'];
        }

        $debugInfo = [
            'file_size' => filesize($filePath),
            'file_exists' => true,
            'first_100_chars' => '',
            'file_content_sample' => ''
        ];

        // Baca beberapa karakter pertama
        $handle = fopen($filePath, 'r');
        if ($handle) {
            $firstLine = fgets($handle);
            $debugInfo['first_line'] = $firstLine;
            $debugInfo['first_line_length'] = strlen($firstLine);

            // Baca seluruh file untuk analisis
            rewind($handle);
            $fullContent = file_get_contents($filePath);
            $debugInfo['file_content_sample'] = substr($fullContent, 0, 500);

            // Hitung baris
            $lineCount = 0;
            rewind($handle);
            while (!feof($handle)) {
                fgets($handle);
                $lineCount++;
            }
            $debugInfo['line_count'] = $lineCount;

            fclose($handle);
        }

        return $debugInfo;
    }

    /**
     * Parse CSV file
     */
    /**
     * Parse CSV file dengan berbagai format
     */
    private function parseCSV($filePath)
    {
        $data = [];

        if (!file_exists($filePath)) {
            \Log::error('CSV File not found: ' . $filePath);
            return $data;
        }

        // Baca seluruh file untuk deteksi encoding
        $fileContent = file_get_contents($filePath);

        // Deteksi encoding dan convert ke UTF-8 jika perlu
        $encoding = mb_detect_encoding($fileContent, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        \Log::info('Detected encoding: ' . ($encoding ?: 'unknown'));

        if ($encoding && $encoding !== 'UTF-8') {
            $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
            file_put_contents($filePath, $fileContent);
        }

        // Remove BOM (Byte Order Mark) jika ada
        $bom = pack('H*', 'EFBBBF');
        $fileContent = preg_replace("/^$bom/", '', $fileContent);
        file_put_contents($filePath, $fileContent);

        $file = fopen($filePath, 'r');
        if (!$file) {
            \Log::error('Cannot open CSV file: ' . $filePath);
            return $data;
        }

        // Baca baris pertama untuk deteksi delimiter
        $firstLine = fgets($file);
        \Log::info('First line of CSV:', [$firstLine]);

        // Reset pointer ke awal
        rewind($file);

        // Deteksi delimiter
        $delimiter = $this->detectDelimiter($firstLine);
        \Log::info('Detected delimiter: ' . $delimiter);

        // Baca header
        $headers = fgetcsv($file, 0, $delimiter);
        \Log::info('CSV Headers:', $headers);

        if (!$headers || empty(array_filter($headers))) {
            \Log::error('CSV headers are empty or invalid');
            fclose($file);
            return $data;
        }

        // Bersihkan headers
        $headers = array_map(function ($header) {
            $header = trim($header);
            // Remove quotes jika ada
            $header = trim($header, '"\'');
            // Remove BOM jika masih ada
            $header = str_replace("\xEF\xBB\xBF", '', $header);
            return $header;
        }, $headers);

        \Log::info('Cleaned Headers:', $headers);

        // Baca data rows
        $rowNumber = 1;
        while (($row = fgetcsv($file, 0, $delimiter)) !== false) {
            $rowNumber++;

            // Skip empty rows
            if (empty(array_filter($row, function ($value) {
                return trim($value) !== '';
            }))) {
                \Log::info("Skipping empty row: {$rowNumber}");
                continue;
            }

            // Jika jumlah kolom tidak sama, adjust
            if (count($row) > count($headers)) {
                // Trim row jika terlalu panjang
                $row = array_slice($row, 0, count($headers));
            } elseif (count($row) < count($headers)) {
                // Pad row jika terlalu pendek
                $row = array_pad($row, count($headers), '');
            }

            // Bersihkan nilai
            $row = array_map(function ($value) {
                return trim($value, " \t\n\r\0\x0B\"'");
            }, $row);

            // Combine dengan headers
            $rowData = array_combine($headers, $row);

            // Debug baris pertama
            if ($rowNumber === 2) {
                \Log::info('First data row:', $rowData);
            }

            $data[] = $rowData;
        }

        \Log::info("Total rows parsed: " . count($data));

        fclose($file);
        return $data;
    }

    /**
     * Deteksi delimiter CSV
     */
    private function detectDelimiter($firstLine)
    {
        $delimiters = [
            ',' => 0,
            ';' => 0,
            "\t" => 0,
            '|' => 0
        ];

        foreach ($delimiters as $delimiter => &$count) {
            $count = substr_count($firstLine, $delimiter);
        }

        // Pilih delimiter dengan count tertinggi
        $detectedDelimiter = array_search(max($delimiters), $delimiters);

        // Default koma jika tidak ada delimiter yang terdeteksi
        return $detectedDelimiter ?: ',';
    }

    /**
     * Filter CSV data based on semester and prodi
     */
    private function filterCSVData($data, $semester, $prodi)
    {
        return array_filter($data, function ($row) use ($semester, $prodi) {
            $match = true;

            if (!empty($semester)) {
                $rowSemester = intval($row['Smt'] ?? 0);
                $match = $match && ($rowSemester == $semester);
            }

            if (!empty($prodi)) {
                $rowProdi = trim($row['Prodi'] ?? '');
                $match = $match && ($rowProdi == $prodi);
            }

            return $match;
        });
    }

    /**
     * Save upload log
     */
    private function saveUploadLog($filename, $importResult, $tanggal, $action)
    {
        $logPath = storage_path('logs/uploads.log');
        $logMessage = sprintf(
            "[%s] File: %s | Tanggal: %s | Action: %s | Success: %d | Failed: %d | Total: %d\n",
            date('Y-m-d H:i:s'),
            $filename,
            $tanggal,
            $action,
            $importResult['success_count'],
            $importResult['failed_count'],
            $importResult['total_rows']
        );

        file_put_contents($logPath, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get recent uploads from log
     */
    private function getRecentUploads()
    {
        $logPath = storage_path('logs/uploads.log');

        if (!file_exists($logPath)) {
            return [];
        }

        $logs = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = array_slice(array_reverse($logs), 0, 10); // Get last 10

        $recentUploads = [];
        foreach ($logs as $log) {
            if (preg_match('/\[(.*?)\] File: (.*?) \| Tanggal: (.*?) \| Action: (.*?) \| Success: (\d+) \| Failed: (\d+) \| Total: (\d+)/', $log, $matches)) {
                $recentUploads[] = [
                    'timestamp' => $matches[1],
                    'filename' => $matches[2],
                    'tanggal' => $matches[3],
                    'action' => $matches[4],
                    'success' => $matches[5],
                    'failed' => $matches[6],
                    'total' => $matches[7]
                ];
            }
        }

        return $recentUploads;
    }

    /**
     * Download template CSV
     */
    public function downloadTemplate()
    {
        $template = "No,Keterangan,Prodi,Smt,gol,Kode,MK,SKS,Dosen Koordinator,Team Taching 1,Team Taching 2,Team Taching 3,Team Taching 4,Teknisi,Teknisi,Hari,Jam,Ruang\n";
        $template .= "1,Jember,TIF,3,A,TIF130702,Matematika Diskrit,2,Moh. Munih Dian W., S.Kom, MT,Dr. Denny Trias Utomo, S.Si., M.T.,,,,,,,Senin,07.00 - 09.00,3.1\n";
        $template .= "2,Jember,TIF,3,B,TIF130702,Matematika Diskrit,2,Moh. Munih Dian W., S.Kom, MT,Dr. Denny Trias Utomo, S.Si., M.T.,,,,,,,Senin,07.00 - 09.00,3.2\n";

        return response($template, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_jadwal.csv"'
        ]);
    }
}
