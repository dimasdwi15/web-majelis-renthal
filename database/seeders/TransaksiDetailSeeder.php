<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TransaksiDetail;

class TransaksiDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            TransaksiDetail::create([
                'transaksi_id' => $i,
                'barang_id' => rand(1, 5),
                'jumlah' => 1,
                'harga_per_hari' => 100000,
                'durasi_hari' => 2,
                'subtotal' => 200000
            ]);
        }
    }
}
