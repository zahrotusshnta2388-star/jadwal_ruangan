<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Jadwal;

class JadwalSeeder extends Seeder
{
    public function run(): void
    {
        // Data contoh untuk testing
        Jadwal::create([
            'tanggal' => '2024-01-15',
            'hari' => 'Senin',
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:00',
            'ruangan' => '3.1',
            'keterangan' => 'Jember',
            'prodi' => 'TIF',
            'semester' => 3,
            'golongan' => 'C',
            'kode_mk' => 'TIF130702',
            'mata_kuliah' => 'Matematika Diskrit',
            'sks' => 2,
            'dosen_koordinator' => 'Moh. Munih Dian W., S.Kom, MT',
            'team_teaching' => json_encode(['Dr. Denny Trias Utomo, S.Si., M.T.']),
            'teknisi' => null,
        ]);
    }
}
