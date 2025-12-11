<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwals', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->nullable(); // untuk filter berdasarkan tanggal
            $table->string('hari', 10); // Senin, Selasa, dll
            $table->time('jam_mulai'); // 07:00:00
            $table->time('jam_selesai'); // 08:00:00
            $table->string('ruangan', 50); // 3.1, 4.3, Lab MMC, dll

            // Data dari CSV
            $table->string('keterangan')->nullable(); // Jember, Bondowoso
            $table->string('prodi', 10); // TIF, MIF, TKK
            $table->integer('semester'); // 1, 3, 5
            $table->string('golongan', 10); // A, B, C, INT, BWS
            $table->string('kode_mk', 20); // TIF110803
            $table->string('mata_kuliah', 100);
            $table->integer('sks');
            $table->text('dosen_koordinator')->nullable();
            $table->text('team_teaching')->nullable(); // disimpan sebagai JSON
            $table->text('teknisi')->nullable();

            $table->timestamps();

            // Index untuk performa query
            $table->index(['tanggal', 'ruangan']);
            $table->index(['prodi', 'semester']);
            $table->index('hari');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwals');
    }
};
