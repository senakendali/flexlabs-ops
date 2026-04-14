<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Sena Kendali',
            'email' => 'sena@flexlabs.co.id',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        User::create([
            'name' => 'Awalokita',
            'email' => 'awalokita@flexlabs.co.id',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        User::create([
            'name' => 'Hanif',
            'email' => 'kasetyan@flexlabs.co.id',
            'role' => 'marketing',
            'password' => Hash::make('password123'),
        ]);

        User::create([
            'name' => 'Irfan',
            'email' => 'irfan@flexlabs.co.id',
            'role' => 'marketing',
            'password' => Hash::make('password123'),
        ]);

         User::create([
            'name' => 'Syahreza',
            'email' => 'syahreza@flexlabs.co.id',
            'role' => 'marketing',
            'password' => Hash::make('password123'),
        ]);

        User::create([
            'name' => 'Fajar',
            'email' => 'fajar@flexlabs.co.id',
            'role' => 'academic',
            'password' => Hash::make('password123'),
        ]);

         User::create([
            'name' => 'Faldi',
            'email' => 'hr@flexlabs.co.id',
            'role' => 'marketing',
            'password' => Hash::make('password123'),
        ]);

         User::create([
            'name' => 'Gita',
            'email' => 'finance@flexlabs.co.id',
            'role' => 'marketing',
            'password' => Hash::make('password123'),
        ]);

         User::create([
            'name' => 'Seff',
            'email' => 'seff@flexlabs.co.id',
            'role' => 'marketing',
            'password' => Hash::make('password123'),
        ]);

       
    }
}