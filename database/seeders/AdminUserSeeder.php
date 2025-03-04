<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Buat user admin baru
        User::create([
            'username' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password123'), // Ganti dengan password yang kuat
            'phone' => '1234567890',
            'role' => 'admin',
            'image' => null, // Jika tidak ada gambar
        ]);
    }
}
