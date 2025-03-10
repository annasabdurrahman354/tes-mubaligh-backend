<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('tes_users')->insert([
            'id' => 1,
            'nama' => 'Super Admin',
            'username' => 'superadmin',
            'jenis_kelamin' => 'L',
            'email' => 'superadmin@kediri',
            'email_verified_at' => now(),
            'password' => Hash::make('superadmin'),
            'ponpes_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Artisan::call('shield:super-admin', ['--user' => 1]);

        DB::table('tes_users')->insert([
            'nama' => 'Admin Kertosono',
            'username' => 'admin_kertosono',
            'jenis_kelamin' => 'L',
            'email' => 'admin@kertosono',
            'email_verified_at' => now(),
            'password' => Hash::make('admin'),
            'ponpes_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tes_users')->insert([
            'nama' => 'Admin Kediri',
            'username' => 'admin_kediri',
            'jenis_kelamin' => 'L',
            'email' => 'admin@kediri',
            'email_verified_at' => now(),
            'password' => Hash::make('admin'),
            'ponpes_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

       User::factory()->count(20)->create();
    }
}

