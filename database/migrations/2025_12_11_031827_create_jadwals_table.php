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
            $table->date('tanggal')->nullable();
            $table->string('hari', 10);
            $table->string('jam_mulai', 5); // Format: 07:00
            $table->string('jam_selesai', 5); // Format: 08:00
            $table->string('ruangan', 50);

            // Data dari CSV
            $table->string('keterangan')->nullable();
            $table->string('prodi', 10);
            $table->integer('semester');
            $table->string('golongan', 10);
            $table->string('kode_mk', 50)->nullable();
            $table->string('mata_kuliah', 200);
            $table->integer('sks')->default(1);
            $table->text('dosen_koordinator')->nullable();
            $table->json('team_teaching')->nullable();
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
