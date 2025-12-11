<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Jadwal extends Model
{
    use HasFactory;

    protected $table = 'jadwals';

    protected $fillable = [
        'tanggal',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'ruangan',
        'keterangan',
        'prodi',
        'semester',
        'golongan',
        'kode_mk',
        'mata_kuliah',
        'sks',
        'dosen_koordinator',
        'team_teaching',
        'teknisi',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jam_mulai' => 'string', // Simpan sebagai string
        'jam_selesai' => 'string',
        'semester' => 'integer',
        'sks' => 'integer',
    ];

    /**
     * Import data dari array CSV
     */
    public static function importFromCSV($data, $tanggalEfektif)
    {
        $importedCount = 0;
        $failedRows = [];

        \Log::info('=== IMPORT FROM CSV START ===');
        \Log::info('Total rows to import: ' . count($data));

        foreach ($data as $index => $row) {
            try {
                // Debug: Lihat keys yang ada di row
                if ($index === 0) {
                    \Log::info('First row keys:', array_keys($row));
                }

                // Skip jika data tidak lengkap
                if (empty($row['Prodi']) || empty($row['Ruang']) || empty($row['Jam'])) {
                    $failedRows[] = [
                        'row' => $index + 2,
                        'reason' => 'Data tidak lengkap (Prodi/Ruang/Jam kosong)',
                        'data' => $row
                    ];
                    \Log::warning("Row {$index} skipped: Data tidak lengkap");
                    continue;
                }

                // Parse jam dari format "07.00 - 08.00" menjadi "07:00" dan "08:00"
                $jamParsed = self::parseJam($row['Jam']);

                // Debug: Check team teaching columns
                $teamTeachingData = self::parseTeamTeaching($row);
                \Log::info("Row {$index} team teaching:", [$teamTeachingData]);

                // Simpan ke database
                $jadwal = self::create([
                    'tanggal' => $tanggalEfektif,
                    'hari' => $row['Hari'] ?? 'Senin',
                    'jam_mulai' => $jamParsed['mulai'],
                    'jam_selesai' => $jamParsed['selesai'],
                    'ruangan' => trim($row['Ruang']),
                    'keterangan' => $row['Keterangan'] ?? null,
                    'prodi' => $row['Prodi'],
                    'semester' => intval($row['Smt'] ?? 1),
                    'golongan' => $row['gol'] ?? 'A',
                    'kode_mk' => $row['Kode'] ?? '',
                    'mata_kuliah' => $row['MK'] ?? '',
                    'sks' => intval($row['SKS'] ?? 1),
                    'dosen_koordinator' => $row['Dosen Koordinator'] ?? '',
                    'team_teaching' => $teamTeachingData,
                    'teknisi' => $row['Teknisi'] ?? $row['Teknisi,'] ?? null, // Ada typo di header CSV
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $importedCount++;
                \Log::info("Row {$index} imported successfully. ID: {$jadwal->id}");
            } catch (\Exception $e) {
                $failedRows[] = [
                    'row' => $index + 2,
                    'reason' => $e->getMessage(),
                    'data' => $row
                ];
                \Log::error("Row {$index} import failed: " . $e->getMessage());
            }
        }

        \Log::info("=== IMPORT FROM CSV END === Success: {$importedCount}, Failed: " . count($failedRows));

        return [
            'success_count' => $importedCount,
            'failed_count' => count($failedRows),
            'failed_rows' => $failedRows,
            'total_rows' => count($data)
        ];
    }

    /**
     * Parse jam dari format CSV
     */
    private static function parseJam($jamString)
    {
        // Format: "07.00 - 08.00" atau "07.00 - 08.00 "
        $jamString = trim($jamString);

        // Jika tidak ada pemisah, anggap 1 jam
        if (strpos($jamString, '-') === false) {
            $jamMulai = str_replace('.', ':', $jamString);
            $jamSelesai = date('H:i', strtotime($jamMulai . ' +1 hour'));
        } else {
            $parts = explode('-', $jamString);
            $jamMulai = str_replace('.', ':', trim($parts[0]));
            $jamSelesai = str_replace('.', ':', trim($parts[1]));
        }

        // Pastikan format HH:MM
        $jamMulai = self::formatTime($jamMulai);
        $jamSelesai = self::formatTime($jamSelesai);

        return [
            'mulai' => $jamMulai,
            'selesai' => $jamSelesai
        ];
    }

    /**
     * Format waktu ke HH:MM
     */
    private static function formatTime($time)
    {
        // Jika format sudah benar
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time;
        }

        // Jika format 07.00
        if (preg_match('/^\d{2}\.\d{2}$/', $time)) {
            return str_replace('.', ':', $time);
        }

        // Parse dengan strtotime
        return date('H:i', strtotime($time));
    }

    /**
     * Parse team teaching dari kolom CSV
     */
    private static function parseTeamTeaching($row)
    {
        $teamTeaching = [];

        // Versi 1: Coba dengan typo "Taching" (dari CSV Anda)
        for ($i = 1; $i <= 4; $i++) {
            $key = "Team Taching {$i}";  // Perhatikan: "Taching" bukan "Teaching"
            if (isset($row[$key]) && !empty(trim($row[$key])) && trim($row[$key]) !== '0') {
                $teamTeaching[] = trim($row[$key]);
            }
        }

        // Versi 2: Jika tidak ada dengan typo, coba dengan spelling yang benar
        if (empty($teamTeaching)) {
            for ($i = 1; $i <= 4; $i++) {
                $key = "Team Teaching {$i}";
                if (isset($row[$key]) && !empty(trim($row[$key])) && trim($row[$key]) !== '0') {
                    $teamTeaching[] = trim($row[$key]);
                }
            }
        }

        // Kembalikan sebagai JSON string
        return !empty($teamTeaching) ? json_encode($teamTeaching) : null;
    }
}
