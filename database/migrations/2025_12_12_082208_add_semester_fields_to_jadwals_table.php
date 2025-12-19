<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwals', function (Blueprint $table) {
            $table->string('tahun_akademik', 9)->nullable()->after('id'); // 2024/2025
            $table->enum('semester_akademik', ['Ganjil', 'Genap'])->nullable()->after('tahun_akademik');
            $table->date('tanggal_mulai')->nullable()->after('semester_akademik');
            $table->date('tanggal_selesai')->nullable()->after('tanggal_mulai');
            $table->boolean('is_template')->default(false)->after('tanggal_selesai'); // true = template, false = jadwal riil
        });
    }

    public function down(): void
    {
        Schema::table('jadwals', function (Blueprint $table) {
            $table->dropColumn([
                'tahun_akademik',
                'semester_akademik',
                'tanggal_mulai',
                'tanggal_selesai',
                'is_template'
            ]);
        });
    }
};
