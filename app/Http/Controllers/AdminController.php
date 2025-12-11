<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    /**
     * Display admin dashboard.
     */
    public function index()
    {
        $data = [
            'pageTitle' => 'Admin Panel',
            'totalJadwal' => 5, // Sample data
            'latestDate' => date('Y-m-d'),
            'totalRuangan' => 9,
            'totalProdi' => 4,
            'recentUploads' => []
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
        // Validate request
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120', // 5MB max
            'tanggal_efektif' => 'required|date',
        ]);

        // Get uploaded file
        $file = $request->file('csv_file');
        $tanggalEfektif = $request->input('tanggal_efektif');
        $semester = $request->input('semester');
        $prodi = $request->input('prodi');

        // Save file temporarily
        $fileName = 'upload_' . time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('temp', $fileName);

        // Process CSV file
        $processedData = $this->processCSV(storage_path('app/' . $filePath), $tanggalEfektif);

        // Count records
        $recordCount = count($processedData);

        // For now, just show success message
        // In real implementation, you would save to database

        // Clean up temporary file
        Storage::delete($filePath);

        return redirect()
            ->route('admin.upload')
            ->with('success', "Berhasil memproses {$recordCount} data dari file CSV.")
            ->with('processedData', $processedData);
    }

    /**
     * Process CSV file.
     */
    private function processCSV($filePath, $tanggalEfektif)
    {
        $data = [];

        // Check if file exists
        if (!file_exists($filePath)) {
            return $data;
        }

        // Open CSV file
        $file = fopen($filePath, 'r');

        // Read headers
        $headers = fgetcsv($file);

        // Read data rows
        while (($row = fgetcsv($file)) !== false) {
            if (count($row) === count($headers)) {
                $rowData = array_combine($headers, $row);

                // Add effective date
                $rowData['tanggal_efektif'] = $tanggalEfektif;

                // Process time format (07.00 - 08.00 to 07:00 and 08:00)
                if (isset($rowData['Jam']) && strpos($rowData['Jam'], '-') !== false) {
                    $times = explode('-', $rowData['Jam']);
                    $rowData['jam_mulai'] = trim(str_replace('.', ':', $times[0]));
                    $rowData['jam_selesai'] = trim(str_replace('.', ':', $times[1] ?? $times[0]));
                }

                $data[] = $rowData;
            }
        }

        fclose($file);

        return $data;
    }
}
