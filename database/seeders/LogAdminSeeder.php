<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LogAdmin;

class LogAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            LogAdmin::create([
                'user_id' => 1,
                'aksi' => "aksi ke-$i",
                'target_tipe' => 'transaksi',
                'target_id' => $i
            ]);
        }
    }
}
