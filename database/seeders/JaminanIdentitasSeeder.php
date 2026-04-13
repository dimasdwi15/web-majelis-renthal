<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\JaminanIdentitas;

class JaminanIdentitasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            JaminanIdentitas::create([
                'transaksi_id' => $i,
                'user_id' => $i,
                'jenis_identitas' => 'KTP',
                'path_file' => "ktp$i.jpg"
            ]);
        }
    }
}
