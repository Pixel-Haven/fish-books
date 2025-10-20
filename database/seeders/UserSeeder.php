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
        // Create or update owner account
        User::updateOrCreate(
            ['email' => 'owner@hushiyaaru.com'],
            [
                'name' => 'Owner',
                'email' => 'owner@hushiyaaru.com',
                'password' => Hash::make('owner123'),
                'role' => 'OWNER',
                'phone' => '+960 777-0000',
            ]
        );

        // Create or update manager account
        User::updateOrCreate(
            ['email' => 'manager@hushiyaaru.com'],
            [
                'name' => 'Manager',
                'email' => 'manager@hushiyaaru.com',
                'password' => Hash::make('manager123'),
                'role' => 'MANAGER',
                'phone' => '+960 777-0001',
            ]
        );

        $this->command->info('Users seeded successfully!');
    }
}
