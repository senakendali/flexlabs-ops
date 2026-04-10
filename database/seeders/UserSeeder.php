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
            'name' => 'Admin Ops',
            'email' => 'admin@flexlabs.co.id',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        User::create([
            'name' => 'Instruktur 1',
            'email' => 'instruktur1@flexlabs.co.id',
            'role' => 'instructor',
            'password' => Hash::make('password123'),
        ]);
    }
}