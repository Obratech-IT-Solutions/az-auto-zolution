<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        // Drop old demo rows from earlier seed revisions so Employees list matches roster below.
        User::whereIn('email', ['cashier@az.com', 'andrei@az.com'])->delete();

        $cashierPassword = Hash::make('password123');
        $adminPassword = Hash::make('az2026');

        User::updateOrCreate(
            ['email' => 'cashier1@az.com'],
            ['name' => 'Cashier 1', 'password' => $cashierPassword, 'role' => User::ROLE_CASHIER]
        );
        User::updateOrCreate(
            ['email' => 'cashier2@az.com'],
            ['name' => 'Cashier 2', 'password' => $cashierPassword, 'role' => User::ROLE_CASHIER]
        );

        User::updateOrCreate(
            ['email' => 'admin@az.com'],
            ['name' => 'Admin', 'password' => $adminPassword, 'role' => User::ROLE_ADMIN]
        );
        User::updateOrCreate(
            ['email' => 'adminbackup@az.com'],
            ['name' => 'Admin Backup', 'password' => $adminPassword, 'role' => User::ROLE_ADMIN]
        );
    }
}
