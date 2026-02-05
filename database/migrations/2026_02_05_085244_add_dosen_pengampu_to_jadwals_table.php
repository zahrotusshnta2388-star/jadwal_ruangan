<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('jadwals', function (Blueprint $table) {
            if (!Schema::hasColumn('jadwals', 'dosen_pengampu')) {
                $table->string('dosen_pengampu')->nullable()->after('team_teaching');
            }
        });
    }

    public function down()
    {
        Schema::table('jadwals', function (Blueprint $table) {
            $table->dropColumn('dosen_pengampu');
        });
    }
};
