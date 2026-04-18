<?php

namespace App\Console\Commands;

use App\Services\TransaksiService;
use Illuminate\Console\Command;

class CancelExpiredCodCommand extends Command
{
    protected $signature = 'transaksi:cancel-expired-cod';

    protected $description = 'Batalkan transaksi COD & Midtrans yang melewati batas 24 jam, dan tandai transaksi terlambat';

    public function handle(TransaksiService $service): int
    {
        // 1. Tandai transaksi yang terlambat dikembalikan
        $terlambat = $service->markTerlambat();

        if ($terlambat > 0) {
            $this->info("Menandai {$terlambat} transaksi sebagai Terlambat.");
        } else {
            $this->info('Tidak ada transaksi baru yang terlambat.');
        }

        // 2. Batalkan COD yang expired (user tidak datang bayar tunai)
        $dibatalkanCod = $service->cancelExpiredCod();

        if ($dibatalkanCod > 0) {
            $this->info("Berhasil membatalkan {$dibatalkanCod} transaksi COD yang expired.");
        } else {
            $this->info('Tidak ada transaksi COD yang expired.');
        }

        // 3. Batalkan Midtrans yang tidak dibayar dalam 24 jam
        //    (stok otomatis di-restore ke DB)
        $dibatalkanMidtrans = $service->cancelExpiredMidtrans();

        if ($dibatalkanMidtrans > 0) {
            $this->info("Berhasil membatalkan {$dibatalkanMidtrans} transaksi Midtrans yang tidak dibayar dalam 24 jam.");
        } else {
            $this->info('Tidak ada transaksi Midtrans yang expired.');
        }

        return self::SUCCESS;
    }
}
