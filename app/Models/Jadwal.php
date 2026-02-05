<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Jadwal extends Model
{
    use HasFactory;

    protected $table = 'jadwals';

    protected $fillable = [
        'tahun_akademik',
        'semester_akademik',
        'tanggal_mulai',
        'tanggal_selesai',
        'is_template',
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
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'jam_mulai' => 'string',
        'jam_selesai' => 'string',
        'semester' => 'integer',
        'sks' => 'integer',
        'is_template' => 'boolean',
    ];

    /**
     * Import data dari array CSV dengan sistem semester
     */
    public static function importFromCSV($data, $tahunAkademik, $semesterAkademik, $tanggalMulai, $tanggalSelesai, $action)
    {
        set_time_limit(300);
        $importedCount = 0;
        $failedRows = [];

        \Log::info('=== IMPORT START - ' . count($data) . ' rows ===');

        DB::beginTransaction();

        try {
            // Hapus data lama jika replace
            if ($action === 'replace') {
                self::where('tahun_akademik', $tahunAkademik)
                    ->where('semester_akademik', $semesterAkademik)
                    ->where('is_template', true)
                    ->delete();
            }

            $batchData = [];
            $batchSize = 100;

            foreach ($data as $index => $row) {
                try {
                    // Validasi data wajib
                    if (empty($row['Prodi']) || empty($row['Ruang']) || empty($row['Jam']) || empty($row['Hari'])) {
                        $failedRows[] = ['row' => $index + 2, 'reason' => 'Data tidak lengkap'];
                        continue;
                    }

                    // Parse jam
                    $jam = self::parseJamFast($row['Jam']);

                    // Validasi format jam
                    if (!preg_match('/^\d{2}:\d{2}$/', $jam['mulai']) || !preg_match('/^\d{2}:\d{2}$/', $jam['selesai'])) {
                        $failedRows[] = ['row' => $index + 2, 'reason' => 'Format jam harus HH:MM: ' . $row['Jam']];
                        continue;
                    }

                    // **DEBUG: LOG SEMUA KEY YANG ADA DI ROW**
                    if ($index === 0) {
                        \Log::info('AVAILABLE KEYS IN FIRST ROW:', array_keys($row));
                    }

                    // **AMBIL DATA DOSEN KOORDINATOR - PERBAIKAN DI SINI**
                    // Coba beberapa kemungkinan nama kolom
                    $dosenKoordinator = '';
                    $possibleKoordinatorKeys = [
                        'Koordinator',
                        'Dosen Koordinator',
                        'Koordinator MK',
                        'Koordinator,'
                    ];

                    foreach ($possibleKoordinatorKeys as $key) {
                        if (isset($row[$key]) && !empty(trim($row[$key]))) {
                            $dosenKoordinator = trim($row[$key]);
                            // Hapus quotes jika ada
                            $dosenKoordinator = trim($dosenKoordinator, '"\'');
                            break;
                        }
                    }

                    // **AMBIL TEKNISI - PERBAIKAN DI SINI**
                    $teknisi = '';
                    $possibleTeknisiKeys = ['Teknisi', 'Teknisi,'];
                    foreach ($possibleTeknisiKeys as $key) {
                        if (isset($row[$key]) && !empty(trim($row[$key]))) {
                            $teknisi = trim($row[$key]);
                            // Hapus quotes jika ada
                            $teknisi = trim($teknisi, '"\'');
                            break;
                        }
                    }

                    // **GABUNG TEAM TEACHING 1-4 - PERBAIKAN DI SINI**
                    $teamTeaching = [];
                    for ($i = 1; $i <= 4; $i++) {
                        // Coba beberapa variasi key
                        $keyVariants = [
                            "Team Taching {$i}",
                            "Team Taching {$i},",
                            "Team Teaching {$i}",
                            "Team Teaching {$i},"
                        ];

                        foreach ($keyVariants as $key) {
                            if (isset($row[$key]) && !empty(trim($row[$key]))) {
                                $teamMember = trim($row[$key]);
                                // Hapus koma di akhir jika ada
                                $teamMember = rtrim($teamMember, ',');
                                // Hapus quotes jika ada
                                $teamMember = trim($teamMember, '"\'');
                                if (!empty($teamMember)) {
                                    $teamTeaching[] = $teamMember;
                                }
                                break;
                            }
                        }
                    }

                    // **GABUNG TEAM TEACHING MENJADI STRING JSON**
                    $teamTeachingJson = !empty($teamTeaching) ? json_encode($teamTeaching) : null;

                    // **DEBUG: LOG DATA YANG DIAMBIL**
                    if ($index === 0) {
                        \Log::info('EXTRACTED DATA FROM FIRST ROW:', [
                            'Dosen Koordinator found' => !empty($dosenKoordinator),
                            'Dosen Koordinator value' => $dosenKoordinator,
                            'Teknisi found' => !empty($teknisi),
                            'Teknisi value' => $teknisi,
                            'Team Teaching count' => count($teamTeaching),
                            'Team Teaching values' => $teamTeaching,
                            'Team Teaching JSON' => $teamTeachingJson
                        ]);
                    }

                    // Siapkan data untuk batch insert
                    $batchData[] = [
                        'tahun_akademik' => $tahunAkademik,
                        'semester_akademik' => $semesterAkademik,
                        'tanggal_mulai' => $tanggalMulai,
                        'tanggal_selesai' => $tanggalSelesai,
                        'is_template' => true,
                        'tanggal' => null,
                        'hari' => trim($row['Hari']),
                        'jam_mulai' => $jam['mulai'],
                        'jam_selesai' => $jam['selesai'],
                        'ruangan' => trim($row['Ruang']),
                        'prodi' => $row['Prodi'],
                        'semester' => intval($row['Smt'] ?? 1),
                        'golongan' => $row['gol'] ?? 'A',
                        'kode_mk' => $row['Kode'] ?? '',
                        'mata_kuliah' => $row['MK'] ?? '',
                        'sks' => intval($row['SKS'] ?? 1),

                        // **DATA BARU DENGAN VALUE YANG SUDAH DIPEROLEH**
                        'dosen_koordinator' => $dosenKoordinator ?: null,
                        'team_teaching' => $teamTeachingJson,
                        'teknisi' => $teknisi ?: null,

                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $importedCount++;

                    // Batch insert setiap 100 row
                    if (count($batchData) >= $batchSize) {
                        self::insert($batchData);
                        $batchData = [];
                        \Log::info("Batch inserted: {$batchSize} rows");
                    }
                } catch (\Exception $e) {
                    $failedRows[] = ['row' => $index + 2, 'reason' => $e->getMessage()];
                    \Log::error("Error in row {$index}: " . $e->getMessage());
                }
            }

            // Insert sisa data
            if (!empty($batchData)) {
                self::insert($batchData);
                \Log::info("Inserted remaining: " . count($batchData) . " rows");
            }

            DB::commit();

            \Log::info("=== IMPORT COMPLETE === Success: {$importedCount}, Failed: " . count($failedRows));

            // LOG SAMPLE DATA YANG DIINSERT
            if ($importedCount > 0) {
                $sample = self::where('tahun_akademik', $tahunAkademik)
                    ->where('semester_akademik', $semesterAkademik)
                    ->where('is_template', true)
                    ->limit(2)
                    ->get(['dosen_koordinator', 'team_teaching', 'teknisi']);

                \Log::info('SAMPLE INSERTED DATA (checking teaching columns):', $sample->toArray());
            }

            return [
                'success_count' => $importedCount,
                'failed_count' => count($failedRows),
                'failed_rows' => array_slice($failedRows, 0, 20),
                'total_rows' => count($data)
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Import failed: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Optimized jam parsing
     */
    private static function parseJamFast($jamString)
    {
        if (empty($jamString)) {
            return ['mulai' => '07:00', 'selesai' => '08:00'];
        }

        $jamString = trim($jamString);

        // Normalize dots to colons first
        $jamString = str_replace('.', ':', $jamString);

        // Handle various formats
        if (strpos($jamString, '-') !== false) {
            $parts = explode('-', $jamString);
            $mulai = trim($parts[0]);
            $selesai = trim($parts[1] ?? $parts[0]);

            // Ensure format HH:MM
            $mulai = self::ensureTimeFormat($mulai);
            $selesai = self::ensureTimeFormat($selesai);

            return [
                'mulai' => $mulai,
                'selesai' => $selesai
            ];
        }

        // Single time format
        $mulai = self::ensureTimeFormat($jamString);
        $selesai = date('H:i', strtotime($mulai . ' +1 hour'));

        return [
            'mulai' => $mulai,
            'selesai' => $selesai
        ];
    }

    /**
     * Ensure time is in HH:MM format (max 5 chars)
     */
    private static function ensureTimeFormat($time)
    {
        $time = trim($time);

        // Remove seconds if present
        if (strlen($time) > 5) {
            $time = substr($time, 0, 5);
        }

        // Ensure proper format
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            // Add leading zero for single-digit hours
            if (strlen($time) === 4) {
                $time = '0' . $time;
            }
            return $time;
        }

        // Default fallback
        return '07:00';
    }


    /**
     * Generate jadwal riil dari template untuk periode semester
     */
    public static function generateJadwalRiil($tahunAkademik, $semesterAkademik)
    {
        // Hapus jadwal riil lama
        self::where('tahun_akademik', $tahunAkademik)
            ->where('semester_akademik', $semesterAkademik)
            ->where('is_template', false)
            ->delete();

        // Ambil template
        $templates = self::where('tahun_akademik', $tahunAkademik)
            ->where('semester_akademik', $semesterAkademik)
            ->where('is_template', true)
            ->get();

        if ($templates->isEmpty()) {
            return 0;
        }

        // Ambil periode semester
        $template = $templates->first();
        $startDate = Carbon::parse($template->tanggal_mulai);
        $endDate = Carbon::parse($template->tanggal_selesai);

        $generatedCount = 0;

        // Loop setiap template
        foreach ($templates as $template) {
            // Generate untuk setiap minggu dalam periode
            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                // Cek jika hari sama dengan template
                $dayMap = [
                    'Senin' => Carbon::MONDAY,
                    'Selasa' => Carbon::TUESDAY,
                    'Rabu' => Carbon::WEDNESDAY,
                    'Kamis' => Carbon::THURSDAY,
                    'Jumat' => Carbon::FRIDAY,
                    'Sabtu' => Carbon::SATURDAY,
                    'Minggu' => Carbon::SUNDAY,
                ];

                $templateDay = $dayMap[$template->hari] ?? null;

                if ($templateDay && $currentDate->dayOfWeek === $templateDay) {
                    // Buat jadwal riil dengan SEMUA data termasuk teaching
                    self::create([
                        'tahun_akademik' => $template->tahun_akademik,
                        'semester_akademik' => $template->semester_akademik,
                        'tanggal_mulai' => $template->tanggal_mulai,
                        'tanggal_selesai' => $template->tanggal_selesai,
                        'is_template' => false, // Jadwal riil

                        'tanggal' => $currentDate->toDateString(),
                        'hari' => $template->hari,
                        'jam_mulai' => $template->jam_mulai,
                        'jam_selesai' => $template->jam_selesai,
                        'ruangan' => $template->ruangan,
                        'prodi' => $template->prodi,
                        'semester' => $template->semester,
                        'golongan' => $template->golongan,
                        'kode_mk' => $template->kode_mk,
                        'mata_kuliah' => $template->mata_kuliah,
                        'sks' => $template->sks,

                        // **DATA TEACHING DARI TEMPLATE**
                        'dosen_koordinator' => $template->dosen_koordinator,
                        'team_teaching' => $template->team_teaching,
                        'teknisi' => $template->teknisi,

                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $generatedCount++;
                }

                $currentDate->addDay();
            }
        }

        return $generatedCount;
    }

    /**
     * Get academic period dates
     */
    public static function getAcademicPeriodDates($tahunAkademik, $semester)
    {
        $years = explode('/', $tahunAkademik);
        $startYear = intval($years[0]);

        if ($semester === 'Ganjil') {
            // Semester Ganjil: Agustus - Desember
            return [
                'mulai' => Carbon::create($startYear, 8, 26), // 26 Agustus
                'selesai' => Carbon::create($startYear, 12, 6) // 6 Desember
            ];
        } else {
            // Semester Genap: Februari - Mei
            return [
                'mulai' => Carbon::create($startYear + 1, 2, 3), // 3 Februari
                'selesai' => Carbon::create($startYear + 1, 5, 30) // 30 Mei
            ];
        }
    }

    /**
     * Parse jam dari format CSV
     */
    private static function parseJam($jamString)
    {
        if (empty($jamString)) {
            return ['mulai' => '07:00', 'selesai' => '08:00'];
        }

        $jamString = trim($jamString);

        // Format: "07.00 - 08.00"
        if (strpos($jamString, '-') !== false) {
            $parts = explode('-', $jamString);
            $jamMulai = trim(str_replace('.', ':', $parts[0]));
            $jamSelesai = trim(str_replace('.', ':', $parts[1] ?? $parts[0]));
        } else {
            // Format: "07.00" saja
            $jamMulai = str_replace('.', ':', $jamString);
            $jamSelesai = date('H:i', strtotime($jamMulai . ' +1 hour'));
        }

        return [
            'mulai' => self::formatTime($jamMulai),
            'selesai' => self::formatTime($jamSelesai)
        ];
    }

    /**
     * Format waktu ke HH:MM
     */
    private static function formatTime($time)
    {
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time;
        }

        if (preg_match('/^\d{2}\.\d{2}$/', $time)) {
            return str_replace('.', ':', $time);
        }

        return date('H:i', strtotime($time));
    }

    /**
     * Parse team teaching
     */
    private static function parseTeamTeaching($row)
    {
        $teamTeaching = [];

        for ($i = 1; $i <= 4; $i++) {
            $keys = [
                "Team Taching {$i}",
                "Team Teaching {$i}",
                "Team Taching {$i},",
                "Team Teaching {$i},"
            ];

            foreach ($keys as $key) {
                if (isset($row[$key]) && !empty(trim($row[$key])) && trim($row[$key]) !== '0') {
                    $teamTeaching[] = trim($row[$key]);
                    break;
                }
            }
        }

        return !empty($teamTeaching) ? json_encode($teamTeaching) : null;
    }

    /**
     * Delete by semester
     */
    public static function deleteBySemester($tahunAkademik, $semesterAkademik)
    {
        return self::where('tahun_akademik', $tahunAkademik)
            ->where('semester_akademik', $semesterAkademik)
            ->delete();
    }

    /**
     * Scope untuk filter berdasarkan tanggal
     */
    public function scopeFilterByDate($query, $date)
    {
        return $query->where('tanggal', $date)
            ->where('is_template', false);
    }

    /**
     * Scope untuk filter berdasarkan semester akademik
     */
    public function scopeFilterByAcademicPeriod($query, $tahunAkademik, $semesterAkademik)
    {
        return $query->where('tahun_akademik', $tahunAkademik)
            ->where('semester_akademik', $semesterAkademik)
            ->where('is_template', false);
    }

    /**
     * Accessor untuk display
     */
    public function getKelasDisplayAttribute()
    {
        return $this->prodi . ' ' . $this->semester . $this->golongan;
    }

    public function getJamDisplayAttribute()
    {
        return substr($this->jam_mulai, 0, 5) . ' - ' . substr($this->jam_selesai, 0, 5);
    }

    /**
     * Accessor untuk team teaching (array dari JSON)
     */
    public function getTeamTeachingArrayAttribute()
    {
        if ($this->team_teaching && !empty($this->team_teaching)) {
            $decoded = json_decode($this->team_teaching, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    /**
     * Accessor untuk menampilkan team teaching sebagai string
     */
    public function getTeamTeachingDisplayAttribute()
    {
        $array = $this->team_teaching_array;
        return !empty($array) ? implode(', ', $array) : '-';
    }

    /**
     * Accessor untuk display lengkap
     */
    public function getInfoLengkapAttribute()
    {
        $info = $this->mata_kuliah;

        if ($this->dosen_koordinator) {
            $info .= "\nKoordinator: " . $this->dosen_koordinator;
        }

        if ($this->team_teaching_array) {
            $info .= "\nTeam: " . $this->team_teaching_display;
        }

        if ($this->teknisi) {
            $info .= "\nTeknisi: " . $this->teknisi;
        }

        return $info;
    }
}
