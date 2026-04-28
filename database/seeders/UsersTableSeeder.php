<?php

// database/seeders/UsersTableSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        $pwd = Hash::make('password123');

        // Cashier (updateOrCreate avoids duplicate-email errors when re-seeding)
        User::updateOrCreate(
            ['email' => 'cashier@az.com'],
            ['name' => 'Diana', 'password' => $pwd, 'role' => 'cashier']
        );
        User::updateOrCreate(
            ['email' => 'cashier1@az.com'],
            ['name' => 'Grace', 'password' => $pwd, 'role' => 'cashier']
        );
        User::updateOrCreate(
            ['email' => 'cashier2@az.com'],
            ['name' => 'Belmonte', 'password' => $pwd, 'role' => 'cashier']
        );

        // Admin
        User::updateOrCreate(
            ['email' => 'admin@az.com'],
            ['name' => 'Admin User', 'password' => $pwd, 'role' => 'admin']
        );
        User::updateOrCreate(
            ['email' => 'andrei@az.com'],
            ['name' => 'Andrei', 'password' => $pwd, 'role' => 'admin']
        );
    }
}
