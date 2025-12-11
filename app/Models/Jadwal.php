<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'team_teaching' => 'array', // otomatis decode dari JSON
        'tanggal' => 'date',
        'jam_mulai' => 'datetime:H:i',
        'jam_selesai' => 'datetime:H:i',
    ];

    // Accessor untuk menampilkan jam dalam format 07.00 - 08.00
    public function getJamRangeAttribute()
    {
        return date('H.i', strtotime($this->jam_mulai)) . ' - ' . date('H.i', strtotime($this->jam_selesai));
    }

    // Accessor untuk menampilkan kelas (TIF 3 A)
    public function getKelasAttribute()
    {
        return $this->prodi . ' ' . $this->semester . ' ' . $this->golongan;
    }

    // Scope untuk filter berdasarkan tanggal
    public function scopeFilterByTanggal($query, $tanggal)
    {
        return $query->where('tanggal', $tanggal);
    }

    // Scope untuk filter berdasarkan ruangan
    public function scopeFilterByRuangan($query, $ruangan)
    {
        return $query->where('ruangan', $ruangan);
    }

    // Scope untuk filter berdasarkan hari
    public function scopeFilterByHari($query, $hari)
    {
        return $query->where('hari', $hari);
    }
}
