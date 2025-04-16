<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiswaPonpes extends Model
{
    protected $table = 'tb_person_ponpes_status';

    protected $fillable = [
        'id_ponpes',
        'nispn',
        'st_person',
        'st_aktif',
        'tgl_masuk',
        'tgl_keluar',
        'ket_keluar',
    ];

    public $timestamps = false; // Set to true if the table has created_at and updated_at

    // Optionally: cast date fields
    protected $casts = [
        'tgl_masuk' => 'date',
        'tgl_keluar' => 'date',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'nispn', 'nispn');
    }

    public function ponpes()
    {
        return $this->belongsTo(Ponpes::class, 'id_ponpes', 'id_ponpes');
    }
}
