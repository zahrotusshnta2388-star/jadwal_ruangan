<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_programs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_prodi', 10)->unique(); // TIF, MIF, TKK
            $table->string('nama_prodi', 100); // Teknik Informatika, dll
            $table->string('fakultas', 100)->nullable();
            $table->string('jenjang', 10)->nullable(); // D3, S1

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_programs');
    }
};
