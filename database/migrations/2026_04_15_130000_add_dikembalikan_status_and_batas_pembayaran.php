<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah value 'dikembalikan' ke enum status
        DB::statement("ALTER TABLE `transaksi` MODIFY `status` ENUM(
            'menunggu_pembayaran',
            'dibayar',
            'berjalan',
            'terlambat',
            'dikembalikan',
            'selesai',
            'dibatalkan'
        ) DEFAULT 'menunggu_pembayaran'");

        // 2. Tambah kolom batas_pembayaran untuk auto-cancel COD
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dateTime('batas_pembayaran')
                  ->nullable()
                  ->after('tanggal_dikembalikan')
                  ->comment('Batas waktu pembayaran COD (24 jam dari checkout)');
        });
    }

    public function down(): void
    {
        // Kembalikan enum tanpa 'dikembalikan'
        DB::statement("ALTER TABLE `transaksi` MODIFY `status` ENUM(
            'menunggu_pembayaran',
            'dibayar',
            'berjalan',
            'terlambat',
            'selesai',
            'dibatalkan'
        ) DEFAULT 'menunggu_pembayaran'");

        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropColumn('batas_pembayaran');
        });
    }
};
