<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('kode_ruangan', 20)->unique(); // 3.1, 4.3, dll
            $table->string('nama_ruangan', 100)->nullable(); // Ruang Kelas 3.1, Lab MMC
            $table->integer('kapasitas')->nullable();
            $table->string('jenis', 50)->nullable(); // Kelas, Lab, Workshop
            $table->string('gedung', 50)->nullable(); // Gedung 3, Gedung 4
            $table->string('lantai', 10)->nullable(); // 3, 4

            $table->timestamps();

            // Index
            $table->index('kode_ruangan');
            $table->index('jenis');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
