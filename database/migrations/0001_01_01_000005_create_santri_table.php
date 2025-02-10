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
        Schema::disableForeignKeyConstraints();

        Schema::create('tes_santri', function (Blueprint $table) {
            $table->id();
            $table->integer('ponpes_id');
            $table->integer('periode_id');
            $table->string('nispn');
            $table->string('tahap')->nullable();
            $table->string('kelompok')->nullable();
            $table->integer('nomor_cocard')->nullable();
            $table->string('status_tes')->nullable();
            $table->string('status_kelanjutan')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tes_santri');
    }
};
