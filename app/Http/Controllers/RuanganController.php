<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Jadwal; // Jangan lupa import model

class RuanganController extends Controller
{
    /**
     * Display the room schedule page.
     */
    public function index(Request $request)
    {
        // Get date filter or use today
        $selectedDate = $request->input('tanggal', date('Y-m-d'));
        $gedung = $request->input('gedung', '');

        // Query data dari database berdasarkan tanggal
        $query = Jadwal::where('tanggal', $selectedDate);

        // Filter berdasarkan gedung jika dipilih
        if (!empty($gedung)) {
            if ($gedung === 'Lab') {
                // Untuk filter laboratorium
                $query->where(function ($q) {
                    $q->where('ruangan', 'LIKE', 'Lab%')
                        ->orWhere('ruangan', 'LIKE', '%Lab%');
                });
            } elseif ($gedung === 'F') {
                // Untuk gedung F
                $query->where('ruangan', 'LIKE', 'F%');
            } else {
                // Untuk gedung angka (3, 4, dll)
                $query->where('ruangan', 'LIKE', $gedung . '.%');
            }
        }

        // Ambil data jadwal
        $jadwals = $query->orderBy('ruangan')
            ->orderBy('jam_mulai')
            ->get();

        // Jika tidak ada data untuk tanggal ini, coba hari dari data
        if ($jadwals->isEmpty()) {
            // Cari tanggal terdekat yang ada data
            $nearestDate = Jadwal::where('tanggal', '>=', $selectedDate)
                ->orderBy('tanggal')
                ->value('tanggal');

            if ($nearestDate) {
                // Redirect ke tanggal terdekat yang ada data
                return redirect()->route('ruangan.index', [
                    'tanggal' => $nearestDate,
                    'gedung' => $gedung
                ]);
            }
        }

        // Dapatkan daftar ruangan unik dari database
        $ruangans = Jadwal::where('tanggal', $selectedDate)
            ->select('ruangan')
            ->distinct()
            ->orderBy('ruangan')
            ->pluck('ruangan')
            ->toArray();

        // Jika tidak ada ruangan, gunakan daftar default
        if (empty($ruangans)) {
            $ruangans = $this->getDefaultRuanganList($gedung);
        }

        // Organize data into grid format
        $scheduleGrid = $this->organizeScheduleGrid($jadwals, $ruangans);

        // Get statistics
        $totalKelas = $jadwals->count();
        $totalRuangan = count($ruangans);
        $prodiList = $jadwals->unique('prodi')->pluck('prodi')->toArray();

        return view('ruangan.index', [
            'selectedDate' => $selectedDate,
            'gedung' => $gedung,
            'ruangans' => $ruangans,
            'jadwals' => $jadwals,
            'scheduleGrid' => $scheduleGrid,
            'timeSlots' => $this->getTimeSlots(),
            'totalKelas' => $totalKelas,
            'totalRuangan' => $totalRuangan,
            'prodiList' => $prodiList,
            'statistics' => $this->getStatistics($jadwals)
        ]);
    }

    /**
     * Get default room list based on building filter
     */
    private function getDefaultRuanganList($gedung)
    {
        $defaultRuangan = [
            '3.1',
            '3.2',
            '3.3',
            '3.4',
            '3.5',
            '3.6',
            '3.7',
            '3.8',
            '3.9',
            '3.10',
            '3.11',
            '4.1',
            '4.2',
            '4.3',
            '4.4',
            '4.5',
            '4.6',
            '4.7',
            '4.8',
            '4.9',
            'F1',
            'F2',
            'F3',
            'F4',
            'F5',
            'F6',
            'Lab MMC',
            'Lab RSI',
            'Lab AJK',
            'Lab SKK',
            'Lab RPL',
            'Lab KSI',
            'Workshop Lab RSI',
            'Workshop Lab AJK',
            'Workshop Lab SKK',
            'G1',
            'G2',
            'G3',
            'Ruang Kelas A BWS',
            'Ruang Kelas B BWS'
        ];

        // Filter berdasarkan gedung jika dipilih
        if (!empty($gedung)) {
            if ($gedung === 'Lab') {
                return array_filter($defaultRuangan, function ($ruangan) {
                    return stripos($ruangan, 'Lab') !== false ||
                        stripos($ruangan, 'Workshop') !== false;
                });
            } elseif ($gedung === 'F') {
                return array_filter($defaultRuangan, function ($ruangan) {
                    return strpos($ruangan, 'F') === 0;
                });
            } elseif (is_numeric($gedung)) {
                return array_filter($defaultRuangan, function ($ruangan) use ($gedung) {
                    return strpos($ruangan, $gedung . '.') === 0;
                });
            }
        }

        return $defaultRuangan;
    }

    /**
     * Organize schedule into grid format.
     */
    private function organizeScheduleGrid($jadwals, $ruangans)
    {
        $grid = [];
        $timeSlots = $this->getTimeSlots();

        // Initialize grid with empty values
        foreach ($ruangans as $ruangan) {
            $grid[$ruangan] = [];
            foreach ($timeSlots as $timeSlot) {
                $grid[$ruangan][$timeSlot] = null;
            }
        }

        // Fill grid with schedule data
        foreach ($jadwals as $jadwal) {
            $ruangan = $jadwal->ruangan;

            // Skip if room not in our list
            if (!in_array($ruangan, $ruangans)) {
                continue;
            }

            $startHour = (int)substr($jadwal->jam_mulai, 0, 2);
            $endHour = (int)substr($jadwal->jam_selesai, 0, 2);

            // Mark each time slot that's occupied
            for ($hour = $startHour; $hour < $endHour; $hour++) {
                $timeSlot = sprintf('%02d:00', $hour);
                if (isset($grid[$ruangan][$timeSlot])) {
                    $grid[$ruangan][$timeSlot] = $jadwal;
                }
            }
        }

        return $grid;
    }

    /**
     * Get time slots from 07:00 to 17:00.
     */
    private function getTimeSlots()
    {
        $slots = [];
        for ($hour = 7; $hour <= 17; $hour++) {
            $slots[] = sprintf('%02d:00', $hour);
        }
        return $slots;
    }

    /**
     * Get statistics from schedule data
     */
    private function getStatistics($jadwals)
    {
        if ($jadwals->isEmpty()) {
            return [
                'total_kelas' => 0,
                'prodi_count' => 0,
                'ruangan_count' => 0,
                'semester_list' => [],
                'busiest_hour' => 'N/A',
                'most_used_room' => 'N/A'
            ];
        }

        // Hitung jam tersibuk
        $hourCount = [];
        foreach ($jadwals as $jadwal) {
            $startHour = (int)substr($jadwal->jam_mulai, 0, 2);
            $endHour = (int)substr($jadwal->jam_selesai, 0, 2);

            for ($hour = $startHour; $hour < $endHour; $hour++) {
                $hourKey = sprintf('%02d:00', $hour);
                $hourCount[$hourKey] = ($hourCount[$hourKey] ?? 0) + 1;
            }
        }

        $busiestHour = $hourCount ? array_search(max($hourCount), $hourCount) : 'N/A';

        // Hitung ruangan paling banyak digunakan
        $roomUsage = $jadwals->groupBy('ruangan')->map->count();
        $mostUsedRoom = $roomUsage->isNotEmpty() ? $roomUsage->sortDesc()->keys()->first() : 'N/A';

        return [
            'total_kelas' => $jadwals->count(),
            'prodi_count' => $jadwals->unique('prodi')->count(),
            'ruangan_count' => $jadwals->unique('ruangan')->count(),
            'semester_list' => $jadwals->unique('semester')->pluck('semester')->sort()->values(),
            'busiest_hour' => $busiestHour,
            'most_used_room' => $mostUsedRoom
        ];
    }
}
