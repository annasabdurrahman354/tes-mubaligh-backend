<?php

namespace Database\Seeders;

use App\Models\Siswa;
use Illuminate\Database\Seeder;

class SiswaTableSeeder extends Seeder
{
    public function run()
    {
        Siswa::factory()->count(900)->create();
    }
}

