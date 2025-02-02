<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'nama' => $this->faker->name('male'),
            'nama_panggilan' => $this->faker->firstName('male'),
            'username' => $this->faker->unique()->userName(),
            'nomor_telepon' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => Hash::make('password'), // Default password
            'jenis_kelamin' => 'L', // All users will be male
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
