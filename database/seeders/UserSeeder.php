<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Administrator',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Direktur',
                'email' => 'direktur@example.com',
                'password' => Hash::make('password'),
                'role' => 'direktur',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'General Manager',
                'email' => 'gm@example.com',
                'password' => Hash::make('password'),
                'role' => 'general_manager',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Staff Sales',
                'email' => 'staff@example.com',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}