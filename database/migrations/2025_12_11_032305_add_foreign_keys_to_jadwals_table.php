<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwals', function (Blueprint $table) {
            // Jika mau relasi ke rooms
            // $table->foreignId('room_id')->nullable()->constrained('rooms');

            // Jika mau relasi ke study_programs
            // $table->foreignId('study_program_id')->nullable()->constrained('study_programs');
        });
    }

    public function down(): void
    {
        Schema::table('jadwals', function (Blueprint $table) {
            // $table->dropForeign(['room_id']);
            // $table->dropForeign(['study_program_id']);
        });
    }
};
