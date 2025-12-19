<?php

namespace Database\Seeders;

use App\Models\Jadwal;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AcademicPeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    // database/seeders/AcademicPeriodSeeder.php
    public function run()
    {
        // Hapus data lama
        Jadwal::truncate();

        // Template untuk Semester Ganjil 2024/2025
        $template = [
            'tahun_akademik' => '2024/2025',
            'semester_akademik' => 'Ganjil',
            'tanggal_mulai' => '2024-08-26',
            'tanggal_selesai' => '2024-12-06',
            'is_template' => true,
            'hari' => 'Senin',
            'jam_mulai' => '07:00',
            'jam_selesai' => '09:00',
            'ruangan' => '4.1',
            'prodi' => 'TKK',
            'semester' => 1,
            'golongan' => 'A',
            'mata_kuliah' => 'LITERASI DIGITAL',
            'sks' => 2,
        ];

        Jadwal::create($template);

        // Generate jadwal riil
        Jadwal::generateJadwalRiil('2024/2025', 'Ganjil');
    }
}
