<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BarangFoto;

class BarangFotoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            BarangFoto::create([
                'barang_id' => $i,
                'path_foto' => "barang$i.jpg"
            ]);
        }
    }
}
