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

        Schema::create('tes_akademik_kediri', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('tes_santri_id')->references('id_tes_santri')->on('tb_tes_santri')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('guru_id')->references('id')->on('tes_users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->integer('nilai_makna');
            $table->integer('nilai_keterangan');
            $table->integer('nilai_penjelasan');
            $table->integer('nilai_pemahaman');
            $table->string('catatan')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tes_akademik_kediri');
    }
};
