<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Jadwal;

class RuanganController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->input('tanggal', date('Y-m-d'));
        $hari = $this->getHariIndonesia($selectedDate);

        // 1. GET DATA
        $jadwals = Jadwal::where('hari', $hari)
            ->orderBy('ruangan')
            ->orderBy('jam_mulai')
            ->get()
            ->unique(function ($item) {
                return $item->ruangan . '|' . $item->jam_mulai . '|' . $item->jam_selesai;
            });

        // 2. GET ROOMS
        $ruangansFromData = $jadwals->pluck('ruangan')->unique()->sort()->values()->toArray();
        $defaultRuangan = ['3.1', '3.2', '3.3', '3.4', '3.5', '3.6', '3.7', '3.8', '3.9', '3.10', '3.11'];
        $ruangans = array_unique(array_merge($ruangansFromData, $defaultRuangan));
        sort($ruangans);

        // 3. TIME SLOTS
        $timeSlots = [];
        for ($hour = 7; $hour <= 17; $hour++) {
            $timeSlots[] = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
        }

        // 4. INISIALISASI GRID
        $scheduleGrid = [];

        foreach ($ruangans as $ruangan) {
            foreach ($timeSlots as $timeSlot) {
                $scheduleGrid[$ruangan][$timeSlot] = null;
            }
        }

        // 5. FILL GRID - GUNAKAN array_key_exists(), BUKAN isset()
        foreach ($jadwals as $jadwal) {
            $ruangan = $jadwal->ruangan;

            if (!array_key_exists($ruangan, $scheduleGrid)) {
                continue;
            }

            $jamMulai = str_replace('.', ':', $jadwal->jam_mulai);
            $jamSelesai = str_replace('.', ':', $jadwal->jam_selesai);

            $startHour = (int) substr($jamMulai, 0, 2);
            $endHour = (int) substr($jamSelesai, 0, 2);

            for ($hour = $startHour; $hour < $endHour; $hour++) {
                $timeSlot = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';

                // GUNAKAN array_key_exists() karena isset() gagal untuk NULL values
                if (array_key_exists($timeSlot, $scheduleGrid[$ruangan])) {
                    $scheduleGrid[$ruangan][$timeSlot] = $jadwal;
                }
            }
        }

        // 7. RETURN VIEW (uncomment untuk production)
        return view('ruangan.index', [
            'selectedDate' => $selectedDate,
            'gedung' => '',
            'jadwals' => $jadwals,
            'ruangans' => $ruangans,
            'timeSlots' => $timeSlots,
            'scheduleGrid' => $scheduleGrid
        ]);
    }

    private function getHariIndonesia($date)
    {
        $hariInggris = date('l', strtotime($date));

        $hariMap = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu'
        ];

        return $hariMap[$hariInggris] ?? 'Senin';
    }
}
