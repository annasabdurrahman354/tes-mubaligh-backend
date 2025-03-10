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
        Schema::create('tb_personal_data', function (Blueprint $table) {
            $table->string('nik')->primary(); // Primary key
            $table->string('nispn')->nullable();
            $table->string('nis')->nullable();
            $table->string('nisn')->nullable();
            $table->string('kk')->nullable();
            $table->string('rfid')->nullable();
            $table->string('nama_lengkap');
            $table->string('nama_panggilan')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('jenis_kelamin')->nullable();
            $table->text('alamat')->nullable();
            $table->string('rt')->nullable();
            $table->string('rw')->nullable();
            $table->unsignedBigInteger('provinsi_id')->nullable();
            $table->unsignedBigInteger('kota_kab_id')->nullable();
            $table->unsignedBigInteger('kecamatan_id')->nullable();
            $table->unsignedBigInteger('desa_kel_id')->nullable();
            $table->string('kode_pos')->nullable();
            $table->string('hp')->nullable();
            $table->string('email')->nullable();
            $table->string('pendidikan')->nullable();
            $table->string('jurusan')->nullable();
            $table->unsignedBigInteger('id_daerah_sambung')->nullable();
            $table->string('desa_sambung')->nullable();
            $table->string('kelompok_sambung')->nullable();
            $table->integer('anak_ke')->nullable();
            $table->integer('dari_saudara')->nullable();
            $table->string('status_nikah')->nullable();
            $table->string('riwayat_sakit')->nullable();
            $table->string('alergi')->nullable();
            $table->string('status_mondok')->nullable();
            $table->unsignedBigInteger('id_daerah_kiriman')->nullable();
            $table->string('dapukan')->nullable();
            $table->string('bahasa_makna')->nullable();
            $table->string('bahasa_harian')->nullable();
            $table->string('khatam_hb')->nullable();
            $table->string('keahlian')->nullable();
            $table->string('hobi')->nullable();
            $table->integer('tinggi_badan')->nullable();
            $table->integer('berat_badan')->nullable();
            $table->string('gol_darah')->nullable();
            $table->string('sim')->nullable();
            $table->string('img_person')->nullable();
            $table->string('img_identitas')->nullable();
            $table->boolean('del_status')->default(false);
            $table->string('nama_ayah')->nullable();
            $table->string('status_hidup_ayah')->nullable();
            $table->string('hp_ayah')->nullable();
            $table->string('tempat_lahir_ayah')->nullable();
            $table->date('tanggal_lahir_ayah')->nullable();
            $table->text('alamat_domisili_ayah')->nullable();
            $table->text('alamat_sambung_ayah')->nullable();
            $table->string('pekerjaan_ayah')->nullable();
            $table->string('nama_ibu')->nullable();
            $table->string('status_hidup_ibu')->nullable();
            $table->string('hp_ibu')->nullable();
            $table->string('tempat_lahir_ibu')->nullable();
            $table->date('tanggal_lahir_ibu')->nullable();
            $table->text('alamat_domisili_ibu')->nullable();
            $table->text('alamat_sambung_ibu')->nullable();
            $table->string('pekerjaan_ibu')->nullable();
            $table->timestamps(); // created_at and updated_at

            // Foreign key constraints
            $table->foreign('provinsi_id')->references('id_provinsi')->on('tb_provinsi')->onDelete('set null');
            $table->foreign('kota_kab_id')->references('id_kota_kab')->on('tb_kota_kab')->onDelete('set null');
            $table->foreign('kecamatan_id')->references('id_kecamatan')->on('tb_kecamatan')->onDelete('set null');
            $table->foreign('desa_kel_id')->references('id_desa_kel')->on('tb_desa_kel')->onDelete('set null');
            //$table->foreign('id_daerah_sambung')->references('id_daerah')->on('tb_daerah')->onDelete('set null');
            //$table->foreign('id_daerah_kiriman')->references('id_daerah')->on('tb_daerah')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tb_personal_data3');
    }
};
