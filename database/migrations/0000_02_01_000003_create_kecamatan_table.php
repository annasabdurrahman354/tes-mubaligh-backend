<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tb_kecamatan', function (Blueprint $table) {
            $table->id('id_kecamatan'); // Primary key with a custom name
            $table->string('nama');   // Column for sub-district name
            $table->unsignedBigInteger('kota_kab_id'); // Foreign key for city/district

            // Foreign key constraint
            $table->foreign('kota_kab_id')
                ->references('id_kota_kab')
                ->on('tb_kota_kab')
                ->onDelete('cascade'); // Cascades delete operations
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tb_kecamatan');
    }
};
