<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RuanganController extends Controller
{
    /**
     * Display the room schedule page.
     */
    public function index(Request $request)
    {
        // Get date filter or use today
        $selectedDate = $request->input('tanggal', date('Y-m-d'));

        // Sample data for testing (will be replaced with database later)
        $jadwals = $this->getSampleData();

        // Sample rooms
        $ruangans = ['3.1', '3.2', '3.3', '3.4', '3.5', '3.6', '4.1', '4.2', '4.3'];

        // Organize data by room and time
        $scheduleGrid = $this->organizeScheduleGrid($jadwals, $ruangans);

        return view('ruangan.index', [
            'selectedDate' => $selectedDate,
            'ruangans' => $ruangans,
            'jadwals' => $jadwals,
            'scheduleGrid' => $scheduleGrid,
            'timeSlots' => $this->getTimeSlots()
        ]);
    }

    /**
     * Get sample schedule data for testing.
     */
    private function getSampleData()
    {
        return [
            (object)[
                'id' => 1,
                'ruangan' => '3.1',
                'prodi' => 'TIF',
                'semester' => 3,
                'golongan' => 'C',
                'mata_kuliah' => 'Matematika Diskrit',
                'jam_mulai' => '07:00',
                'jam_selesai' => '09:00',
                'kode_mk' => 'TIF130702',
                'sks' => 2,
                'dosen_koordinator' => 'Moh. Munih Dian W., S.Kom, MT',
                'hari' => 'Senin'
            ],
            (object)[
                'id' => 2,
                'ruangan' => '3.1',
                'prodi' => 'TIF',
                'semester' => 3,
                'golongan' => 'B',
                'mata_kuliah' => 'Algoritma Pemrograman',
                'jam_mulai' => '10:00',
                'jam_selesai' => '12:00',
                'kode_mk' => 'TIF130701',
                'sks' => 3,
                'dosen_koordinator' => 'Dr. Denny Trias Utomo, S.Si., M.T.',
                'hari' => 'Senin'
            ],
            (object)[
                'id' => 3,
                'ruangan' => '3.2',
                'prodi' => 'MIF',
                'semester' => 3,
                'golongan' => 'A',
                'mata_kuliah' => 'Manajemen Operasional',
                'jam_mulai' => '07:00',
                'jam_selesai' => '09:00',
                'kode_mk' => 'MIF130703',
                'sks' => 2,
                'dosen_koordinator' => 'Wahyu Kurnia Dewanto, S.Kom, MT',
                'hari' => 'Senin'
            ],
            (object)[
                'id' => 4,
                'ruangan' => '3.3',
                'prodi' => 'TIF',
                'semester' => 3,
                'golongan' => 'E',
                'mata_kuliah' => 'Matematika Diskrit',
                'jam_mulai' => '07:00',
                'jam_selesai' => '09:00',
                'kode_mk' => 'TIF130702',
                'sks' => 2,
                'dosen_koordinator' => 'Moh. Munih Dian W., S.Kom, MT',
                'hari' => 'Senin'
            ],
            (object)[
                'id' => 5,
                'ruangan' => '4.1',
                'prodi' => 'TKK',
                'semester' => 1,
                'golongan' => 'A',
                'mata_kuliah' => 'Literasi Digital',
                'jam_mulai' => '07:00',
                'jam_selesai' => '09:00',
                'kode_mk' => 'TKK110804',
                'sks' => 2,
                'dosen_koordinator' => 'Hariyono Rakhmad, S.Pd, M.Kom',
                'hari' => 'Senin'
            ],
        ];
    }

    /**
     * Organize schedule into grid format.
     */
    private function organizeScheduleGrid($jadwals, $ruangans)
    {
        $grid = [];
        $timeSlots = $this->getTimeSlots();

        foreach ($ruangans as $ruangan) {
            $grid[$ruangan] = [];

            foreach ($timeSlots as $timeSlot) {
                $grid[$ruangan][$timeSlot] = null;

                // Find schedule for this room and time
                foreach ($jadwals as $jadwal) {
                    if ($jadwal->ruangan == $ruangan) {
                        $startHour = (int)substr($jadwal->jam_mulai, 0, 2);
                        $endHour = (int)substr($jadwal->jam_selesai, 0, 2);
                        $currentHour = (int)substr($timeSlot, 0, 2);

                        if ($currentHour >= $startHour && $currentHour < $endHour) {
                            $grid[$ruangan][$timeSlot] = $jadwal;
                            break;
                        }
                    }
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
}
