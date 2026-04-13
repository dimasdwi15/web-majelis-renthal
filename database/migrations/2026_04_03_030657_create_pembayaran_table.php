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
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksi')->cascadeOnDelete();

            $table->enum('jenis', ['utama', 'charge', 'denda']);
            $table->decimal('jumlah', 12, 2);
            $table->string('metode');
            $table->enum('status', ['menunggu', 'lunas', 'gagal'])->default('menunggu');

            $table->string('referensi_midtrans')->nullable();
            $table->dateTime('dibayar_pada')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
