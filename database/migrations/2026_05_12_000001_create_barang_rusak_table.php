<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang_rusak', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksi')->cascadeOnDelete();
            $table->foreignId('transaksi_detail_id')->constrained('transaksi_detail')->cascadeOnDelete();
            $table->foreignId('barang_id')->constrained('barang')->cascadeOnDelete();
            $table->foreignId('denda_id')->nullable()->constrained('denda')->nullOnDelete();
            $table->unsignedInteger('jumlah')->default(1);
            $table->string('status', 32)->default('menunggu_perbaikan'); // menunggu_perbaikan | sudah_diperbaiki
            $table->text('catatan_kerusakan')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diperbaiki_pada')->nullable();
            $table->foreignId('diperbaiki_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'barang_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang_rusak');
    }
};
