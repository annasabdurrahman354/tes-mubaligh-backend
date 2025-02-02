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
        Schema::create('tb_kota_kab', function (Blueprint $table) {
            $table->id('id_kota_kab'); // Primary key with a custom name
            $table->string('nama');   // Column for city name
            $table->unsignedBigInteger('provinsi_id'); // Foreign key for provinsi

            // Foreign key constraint
            $table->foreign('provinsi_id')
                ->references('id_provinsi')
                ->on('tb_provinsi')
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
        Schema::dropIfExists('tb_kota_kab');
    }
};
