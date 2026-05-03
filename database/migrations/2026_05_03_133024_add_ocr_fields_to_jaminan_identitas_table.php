<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jaminan_identitas', function (Blueprint $table) {
            // Status verifikasi OCR
            $table->enum('status_ocr', [
                'belum_diverifikasi',
                'terverifikasi_otomatis',
                'gagal_otomatis',
                'terverifikasi_manual',
                'ditolak_manual',
            ])->default('belum_diverifikasi')->after('status');

            // Skor kepercayaan OCR (0-100)
            $table->unsignedTinyInteger('ocr_confidence')->default(0)->after('status_ocr');

            // Apakah perlu verifikasi manual oleh admin
            $table->boolean('perlu_verifikasi_manual')->default(false)->after('ocr_confidence');

            // Catatan admin saat verifikasi manual
            $table->text('catatan_admin')->nullable()->after('perlu_verifikasi_manual');

            // Waktu verifikasi
            $table->timestamp('diverifikasi_pada')->nullable()->after('catatan_admin');
        });
    }

    public function down(): void
    {
        Schema::table('jaminan_identitas', function (Blueprint $table) {
            $table->dropColumn([
                'status_ocr',
                'ocr_confidence',
                'perlu_verifikasi_manual',
                'catatan_admin',
                'diverifikasi_pada',
            ]);
        });
    }
};
