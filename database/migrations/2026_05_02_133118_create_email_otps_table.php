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
        Schema::create('email_otps', function (Blueprint $table) {
            $table->id();

            // Email tujuan OTP — tidak harus FK ke users agar fleksibel
            $table->string('email')->index();

            // Kode OTP 6 digit (disimpan plain, bisa di-hash jika ingin lebih aman)
            $table->string('otp', 6);

            // Waktu kadaluarsa OTP — default 10 menit dari dibuat
            $table->timestamp('expires_at');

            // Tandai apakah OTP sudah dipakai (cegah reuse)
            $table->boolean('used')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_otps');
    }
};
