<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Denda;

class DendaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Denda::create([
                'transaksi_id' => $i,
                'jenis' => 'kerusakan',
                'jumlah' => rand(10000, 50000),
                'dibuat_oleh' => 1
            ]);
        }
    }
}
