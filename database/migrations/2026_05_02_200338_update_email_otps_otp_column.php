<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_otps', function (Blueprint $table) {
            // Perbesar kolom agar bisa menampung bcrypt hash
            $table->string('otp', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('email_otps', function (Blueprint $table) {
            // Rollback — perhatikan: data hash yang ada akan terpotong
            $table->string('otp', 6)->change();
        });
    }
};
