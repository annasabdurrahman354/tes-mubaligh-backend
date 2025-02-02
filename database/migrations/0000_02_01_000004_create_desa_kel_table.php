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
        Schema::create('tb_desa_kel', function (Blueprint $table) {
            $table->id('id_desa_kel'); // Primary key with a custom name
            $table->string('nama');   // Column for village/ward name
            $table->unsignedBigInteger('kecamatan_id'); // Foreign key for sub-district

            // Foreign key constraint
            $table->foreign('kecamatan_id')
                ->references('id_kecamatan')
                ->on('tb_kecamatan')
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
        Schema::dropIfExists('tb_desa_kel');
    }
};
