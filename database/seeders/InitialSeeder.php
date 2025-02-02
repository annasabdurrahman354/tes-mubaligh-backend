<?php

namespace Database\Seeders;

use App\Enums\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InitialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::unprepared(
            file_get_contents('database/sql/tb_provinsi.sql')
        );
        $this->command->info('Provinsi table seeded!');

        DB::unprepared(
            file_get_contents('database/sql/tb_kota.sql')
        );
        $this->command->info('Kota table seeded!');

        DB::unprepared(
            file_get_contents('database/sql/tb_kecamatan.sql')
        );
        $this->command->info('Kecamatan table seeded!');

        DB::unprepared(
            file_get_contents('database/sql/tb_desa_kel.sql')
        );
        $this->command->info('Desa table seeded!');

        DB::unprepared(
            file_get_contents('database/sql/tb_daerah.sql')
        );
        $this->command->info('Daerah table seeded!');

        DB::unprepared(
            file_get_contents('database/sql/tb_ponpes.sql')
        );
        $this->command->info('Ponpes table seeded!');
    }
}
