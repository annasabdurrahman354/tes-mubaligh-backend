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
        Schema::create('tes_users', function (Blueprint $table) {
            $table->id()->primary();
            $table->string('nama');
            $table->string('nama_panggilan')->nullable();
            $table->string('username');
            $table->string('jenis_kelamin');
            $table->string('email');
            $table->string('nomor_telepon')->nullable();
            $table->string('nik', 16)->nullable();
            $table->string('rfid')->nullable();
            $table->integer('ponpes_id')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tes_users');
    }
};
