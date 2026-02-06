<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@gmail.com',
            'username' => 'admin',
            'password' => Hash::make('123'), // Ganti dengan password yang aman
            'role' => 'admin',
        ]);

        echo "User admin berhasil dibuat!\n";
        echo "Username: admin\n";
        echo "Password: 123\n";
    }
}
