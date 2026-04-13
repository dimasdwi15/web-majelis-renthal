<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Barang;

class BarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Barang::create([
                'kategori_barang_id' => rand(1, 5),
                'nama' => "Barang $i",
                'harga_per_hari' => rand(50000, 150000),
                'stok' => rand(1, 10),
                'status' => 'aktif'
            ]);
        }
    }
}
