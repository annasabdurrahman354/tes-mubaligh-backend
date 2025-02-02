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

        Schema::create('tes_akhlak_kertosono', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('tes_santri_id')->references('id')->on('tes_santri')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('guru_id')->references('id')->on('tes_users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('catatan');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tes_akhlak_kertosono');
    }
};
