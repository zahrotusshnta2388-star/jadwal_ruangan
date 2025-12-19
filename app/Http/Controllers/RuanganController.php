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
        // Get filters
        $selectedDate = $request->input('tanggal', date('Y-m-d'));
        $gedung = $request->input('gedung', '');
        $tahunAkademik = $request->input('tahun_akademik', '');
        $semesterAkademik = $request->input('semester', '');

        // Query jadwal RIIL (bukan template)
        $query = Jadwal::where('is_template', false);

        // Filter by date
        $query->where('tanggal', $selectedDate);

        // Filter by building
        if (!empty($gedung)) {
            if ($gedung === 'Lab') {
                $query->where(function ($q) {
                    $q->where('ruangan', 'LIKE', 'Lab%')
                        ->orWhere('ruangan', 'LIKE', '%Lab%')
                        ->orWhere('ruangan', 'LIKE', '%Workshop%');
                });
            } elseif ($gedung === 'F') {
                $query->where('ruangan', 'LIKE', 'F%');
            } else {
                $query->where('ruangan', 'LIKE', $gedung . '.%');
            }
        }

        // Filter by academic year
        if (!empty($tahunAkademik)) {
            $query->where('tahun_akademik', $tahunAkademik);
        }

        // Filter by semester
        if (!empty($semesterAkademik)) {
            $query->where('semester_akademik', $semesterAkademik);
        }

        // Get schedules
        $jadwals = $query->orderBy('ruangan')
            ->orderBy('jam_mulai')
            ->get();

        // Get unique rooms from the result
        $ruangans = $jadwals->pluck('ruangan')->unique()->sort()->values()->toArray();

        // If no rooms, show default
        if (empty($ruangans)) {
            $ruangans = $this->getDefaultRuanganList($gedung);
        }

        // Get time slots
        $timeSlots = [];
        for ($hour = 7; $hour <= 17; $hour++) {
            $timeSlots[] = sprintf('%02d:00', $hour);
        }

        // Organize into grid
        $scheduleGrid = [];
        foreach ($ruangans as $ruangan) {
            $scheduleGrid[$ruangan] = [];
            foreach ($timeSlots as $timeSlot) {
                $scheduleGrid[$ruangan][$timeSlot] = null;
            }
        }

        // Fill grid
        foreach ($jadwals as $jadwal) {
            $ruangan = $jadwal->ruangan;
            if (!isset($scheduleGrid[$ruangan])) {
                continue;
            }

            $startHour = (int) substr($jadwal->jam_mulai, 0, 2);
            $endHour = (int) substr($jadwal->jam_selesai, 0, 2);

            // Mark occupied time slots
            for ($hour = $startHour; $hour < $endHour; $hour++) {
                $timeSlot = sprintf('%02d:00', $hour);
                if (isset($scheduleGrid[$ruangan][$timeSlot])) {
                    $scheduleGrid[$ruangan][$timeSlot] = $jadwal;
                }
            }
        }

        // Statistics
        $statistics = [
            'total_kelas' => $jadwals->count(),
            'total_ruangan' => count($ruangans),
            'prodi_list' => $jadwals->pluck('prodi')->unique()->values(),
            'most_used_room' => $jadwals->groupBy('ruangan')
                ->map->count()
                ->sortDesc()
                ->keys()
                ->first() ?? 'N/A'
        ];

        return view('ruangan.index', compact(
            'selectedDate',
            'gedung',
            'tahunAkademik',
            'semesterAkademik',
            'jadwals',
            'ruangans',
            'timeSlots',
            'scheduleGrid',
            'statistics'
        ));
    }

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
