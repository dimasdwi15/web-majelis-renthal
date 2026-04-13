<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nomor_transaksi')->unique();
            $table->text('qr_code')->nullable();

            $table->enum('status', [
                'menunggu_pembayaran',
                'dibayar',
                'berjalan',
                'terlambat',
                'selesai',
                'dibatalkan'
            ])->default('menunggu_pembayaran');

            $table->enum('metode_pembayaran', ['midtrans', 'tunai']);
            $table->enum('status_pembayaran', ['menunggu', 'lunas', 'gagal', 'parsial'])->default('menunggu');

            $table->decimal('total_sewa', 12, 2);
            $table->decimal('total_denda', 12, 2)->default(0);
            $table->decimal('total_charge', 12, 2)->default(0);

            $table->date('tanggal_ambil');
            $table->date('tanggal_kembali');
            $table->dateTime('tanggal_dikembalikan')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
