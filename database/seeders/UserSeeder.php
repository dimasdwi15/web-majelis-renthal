<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;    


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'name' => "User $i",
                'email' => "user$i@gmail.com",
                'password' => Hash::make('123456'),
                'role' => $i === 1 ? 'super_admin' : 'user',
            ]);
        }
    }
}
