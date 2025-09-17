<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'abdulrahman',
                'email' => 'abdulrahman@gmail.com',
                'phone' => '+905010588210',
                'password' => Hash::make('password123'),
                'user_type' => 'admin',
                'status' => 'active',
            ],
            [
                'name' => 'fareza',
                'email' => 'fareza@gmail.com',
                'phone' => '+905380933348',
                'password' => Hash::make('password123'),
                'user_type' => 'admin',
                'status' => 'active',
            ],
            [
                'name' => 'basma',
                'email' => 'basma@gmail.com',
                'phone' => '+306997331033',
                'password' => Hash::make('password123'),
                'user_type' => 'admin',
                'status' => 'active',
            ],
            [
                'name' => 'ali',
                'email' => 'ali@gmail.com',
                'phone' => '+9647849222398',
                'password' => Hash::make('password123'),
                'user_type' => 'admin',
                'status' => 'active',
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                $data
            );
        }
    }
}
