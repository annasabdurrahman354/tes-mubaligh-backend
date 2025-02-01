<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Filament\Facades\Filament;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Super Admin',
            'email' => 'superadmin@kediri',
            'email_verified_at' => now(),
            'password' => Hash::make('superadmin'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = array_column(Role::cases(), 'value');

        foreach ($roles as $key => $role) {
            $roleCreated = (new (\BezhanSalleh\FilamentShield\Resources\RoleResource::getModel()))->create(
                [
                    'name' => $role,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        Artisan::call('shield:super-admin', ['--user' => 1]);

        $panels = Filament::getPanels();
        foreach ($panels as $panelId => $panel) {
            Filament::setCurrentPanel($panel);
            Artisan::call('shield:generate', [
                '--all' => true,
                '--panel' => $panelId,
            ]);
        }
    }
}
