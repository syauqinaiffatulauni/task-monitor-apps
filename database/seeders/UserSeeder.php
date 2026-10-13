<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->delete();

        User::create([
            'name' => fake()->name(),
            'email' => 'supervisor@example.test',
            'password' => 'password',
            'role' => 'supervisor',
        ]);

        User::create([
            'name' => fake()->name(),
            'email' => 'officer@example.test',
            'password' => 'password',
            'role' => 'officer',
        ]);

        User::create([
            'name' => fake()->name(),
            'email' => 'officer2@example.test',
            'password' => 'password',
            'role' => 'officer',
        ]);

        User::create([
            'name' => fake()->name(),
            'email' => 'officer3@example.test',
            'password' => 'password',
            'role' => 'officer',
        ]);

        User::create([
            'name' => fake()->name(),
            'email' => 'officer4@example.test',
            'password' => 'password',
            'role' => 'officer',
        ]);
    }
}
