<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Transaksi;

class TransaksiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Transaksi::create([
                'user_id' => rand(1, 5),
                'nomor_transaksi' => "TRX00$i",
                'status' => 'dibayar',
                'metode_pembayaran' => 'tunai',
                'status_pembayaran' => 'lunas',
                'total_sewa' => rand(100000, 300000),
                'tanggal_ambil' => now(),
                'tanggal_kembali' => now()->addDays(2),
            ]);
        }
    }
}
