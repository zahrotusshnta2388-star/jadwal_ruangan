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
                'tahun_akademik' => 'required|in:2024/2025,2025/2026,2026/2027,2027/2028',
                'semester_akademik' => 'required|in:Ganjil,Genap', // GANTI
                'action' => 'required|in:append,replace'
            ]);

            $file = $request->file('csv_file');
            $tahunAkademik = $request->input('tahun_akademik');
            $semesterAkademik = $request->input('semester_akademik'); // GANTI
            $action = $request->input('action');
            $semesterFilter = $request->input('semester_filter'); // OPSIONAL
            $prodiFilter = $request->input('prodi'); // OPSIONAL

            \Log::info('=== CSV UPLOAD START ===');
            \Log::info('Tahun Akademik: ' . $tahunAkademik);
            \Log::info('Semester Akademik: ' . $semesterAkademik); // GANTI

            // Parse CSV file
            $csvData = $this->parseCSVFromUpload($file);

            if (empty($csvData)) {
                return back()->with('error', 'File CSV kosong atau format tidak sesuai.');
            }

            // Filter data jika ada filter
            if (!empty($semesterFilter) || !empty($prodiFilter)) {
                $csvData = $this->filterCSVData($csvData, $semesterFilter, $prodiFilter);
            }

            // Dapatkan tanggal periode akademik
            $periodDates = Jadwal::getAcademicPeriodDates($tahunAkademik, $semesterAkademik); // GANTI
            $tanggalMulai = $periodDates['mulai']->toDateString();
            $tanggalSelesai = $periodDates['selesai']->toDateString();

            // Import data ke database
            $importResult = Jadwal::importFromCSV(
                $csvData,
                $tahunAkademik,
                $semesterAkademik, // GANTI
                $tanggalMulai,
                $tanggalSelesai,
                $action
            );

            // AUTO-GENERATE langsung setelah import
            if ($importResult['success_count'] > 0) {
                $generatedCount = Jadwal::generateJadwalRiil($tahunAkademik, $semesterAkademik);
                \Log::info("Auto-generated {$generatedCount} real schedules");

                // Tambahkan info generate ke response
                $responseData['generated'] = $generatedCount;
            }

            // Save upload log
            $this->saveUploadLog(
                $file->getClientOriginalName(),
                $importResult,
                $tahunAkademik,
                $semesterAkademik, // GANTI
                $action
            );

            // Prepare response
            $responseData = [
                'success' => $importResult['success_count'],
                'failed' => $importResult['failed_count'],
                'total' => $importResult['total_rows'],
                'tahun_akademik' => $tahunAkademik,
                'semester' => $semesterAkademik,
                'periode' => $tanggalMulai . ' - ' . $tanggalSelesai,
                'failed_rows' => $importResult['failed_rows'] ?? [] // TAMBAHKIN INI
            ];

            return redirect()
                ->route('admin.upload')
                ->with('success', "Import berhasil! Template untuk {$semesterAkademik} {$tahunAkademik} telah dibuat.")
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
     * Update saveUploadLog untuk include semester
     */
    private function saveUploadLog($filename, $importResult, $tahunAkademik, $semesterAkademik, $action) // GANTI parameter
    {
        $logPath = storage_path('logs/uploads.log');
        $logMessage = sprintf(
            "[%s] File: %s | Tahun: %s | Semester: %s | Action: %s | Success: %d | Failed: %d | Total: %d\n",
            date('Y-m-d H:i:s'),
            $filename,
            $tahunAkademik,
            $semesterAkademik, // GANTI
            $action,
            $importResult['success_count'],
            $importResult['failed_count'],
            $importResult['total_rows']
        );

        file_put_contents($logPath, $logMessage, FILE_APPEND | LOCK_EX);
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
        \Log::info('Original Headers from CSV:', $headers);

        // Clean headers - PERBAIKAN UTAMA DI SINI
        $headers = array_map(function ($header) {
            $header = trim($header, " \t\n\r\0\x0B\"'");

            // Fix specific headers dari CSV Anda
            $header = str_replace('Dosen Koordinator', 'Koordinator', $header);
            $header = str_replace('Team Taching', 'Team Taching', $header); // Biarkan typo "Taching"

            // Handle 2 kolom Teknisi - ambil yang pertama
            if ($header === 'Teknisi,' && isset($headers[array_search($header, $headers) + 1])) {
                // Skip yang pertama jika ada duplikat
                return null;
            }

            return $header;
        }, $headers);

        // Hapus header null
        $headers = array_filter($headers, function ($header) {
            return $header !== null;
        });

        \Log::info('Cleaned Headers after fix:', array_values($headers));

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

            // Debug first row untuk verifikasi
            if ($i === 1) {
                \Log::info('FIRST ROW DATA (for debugging):', $rowData);
                \Log::info('Checking specific keys:');
                \Log::info('  Koordinator exists: ' . (isset($rowData['Koordinator']) ? 'YES' : 'NO'));
                \Log::info('  Koordinator value: ' . ($rowData['Koordinator'] ?? 'NULL'));
                \Log::info('  Team Taching 1 exists: ' . (isset($rowData['Team Taching 1']) ? 'YES' : 'NO'));
                \Log::info('  Team Taching 1 value: ' . ($rowData['Team Taching 1'] ?? 'NULL'));
                \Log::info('  Teknisi exists: ' . (isset($rowData['Teknisi']) ? 'YES' : 'NO'));
                \Log::info('  Teknisi value: ' . ($rowData['Teknisi'] ?? 'NULL'));
            }

            $data[] = $rowData;
        }

        \Log::info("CSV Parsing Results:");
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
    private function filterCSVData($data, $semesterFilter, $prodiFilter) // GANTI parameter
    {
        return array_filter($data, function ($row) use ($semesterFilter, $prodiFilter) {
            $match = true;

            if (!empty($semesterFilter)) {
                $rowSemester = intval($row['Smt'] ?? 0);
                $match = $match && ($rowSemester == $semesterFilter);
            }

            if (!empty($prodiFilter)) {
                $rowProdi = trim($row['Prodi'] ?? '');
                $match = $match && ($rowProdi == $prodiFilter);
            }

            return $match;
        });
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

    // Tambahkan method ini di AdminController
    public function generateJadwal(Request $request)
    {
        try {
            $request->validate([
                'tahun_akademik' => 'required',
                'semester' => 'required|in:Ganjil,Genap'
            ]);

            $count = Jadwal::generateJadwalRiil($request->tahun_akademik, $request->semester);

            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => 'Berhasil generate ' . $count . ' jadwal'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
