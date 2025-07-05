<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::create([
            'name' => 'Admin',
            'user_name' => '@Admin_7',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('123456'),
            'user_status' => 'active',
            'last_login_at' => Carbon::now(),
            'email_verified_at' => now(),
            'role' => 'ADMIN',
            'verified_status' => 'verified',
            'profile_status' => 'admin'
        ]);
    }
}
