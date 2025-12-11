<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Display the home page.
     */
    public function index()
    {
        // For Laravel 12, use simple data
        $data = [
            'pageTitle' => 'Beranda - Sistem Jadwal Ruangan',
            'welcomeMessage' => 'Selamat datang di Sistem Jadwal Ruangan',
            'features' => [
                'Monitoring jadwal penggunaan ruangan',
                'Filter berdasarkan tanggal',
                'Upload data dari CSV',
                'Tampilan tabel yang interaktif'
            ],
            'stats' => [
                'total_rooms' => 0,
                'total_schedules' => 0,
                'active_programs' => 0
            ]
        ];

        return view('home.index', $data);
    }
}
