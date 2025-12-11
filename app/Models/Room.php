<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_ruangan',
        'nama_ruangan',
        'kapasitas',
        'jenis',
        'gedung',
        'lantai',
    ];

    public function jadwals()
    {
        return $this->hasMany(Jadwal::class, 'ruangan', 'kode_ruangan');
    }
}
