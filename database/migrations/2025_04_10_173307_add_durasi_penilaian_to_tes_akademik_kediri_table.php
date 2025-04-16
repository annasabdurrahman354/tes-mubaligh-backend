<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tes_akademik_kediri', function (Blueprint $table) {
            $table->integer('durasi_penilaian')->nullable()->after('catatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tes_akademik_kediri', function (Blueprint $table) {
            $table->dropColumn('durasi_penilaian');
        });
    }
};
