<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Pembayaran;

class PembayaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Pembayaran::create([
                'transaksi_id' => $i,
                'jenis' => 'utama',
                'jumlah' => 200000,
                'metode' => 'tunai',
                'status' => 'lunas'
            ]);
        }
    }
}
