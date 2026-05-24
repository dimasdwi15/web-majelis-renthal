<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Perbaikan arsitektur autentikasi
 *
 * Perubahan:
 *  1. Tambah kolom `auth_provider` (local | google | hybrid)
 *  2. Password sekarang benar-benar nullable (tanpa random value untuk Google)
 *  3. Isi `auth_provider` untuk data lama berdasarkan keberadaan google_id
 *
 * Jalankan: php artisan migrate
 * Rollback : php artisan migrate:rollback
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // ── Tambah kolom provider ─────────────────────────────────────────
            // local   → daftar via email/password
            // google  → daftar via Google, belum pernah set password sendiri
            // hybrid  → awalnya Google, sudah set password → bisa login dua cara
            $table->enum('auth_provider', ['local', 'google', 'hybrid'])
                  ->default('local')
                  ->after('google_id')
                  ->comment('local=email/pw, google=Google only, hybrid=keduanya');

            // Pastikan password bisa null (sudah nullable di DB tapi tambahkan
            // comment eksplisit untuk dokumentasi)
            // Tidak perlu alter karena di SQL dump sudah DEFAULT NULL
        });

        // ── Isi data lama ─────────────────────────────────────────────────────
        // User yang punya google_id = akun Google
        // User yang TIDAK punya google_id = akun local
        DB::statement("
            UPDATE users
            SET auth_provider = CASE
                WHEN google_id IS NOT NULL THEN 'google'
                ELSE 'local'
            END
        ");

        // ── Hapus password random dari akun Google murni ───────────────────
        // Untuk keamanan: akun Google yang passwordnya dibuat sistem (random)
        // harus di-null-kan agar tidak bisa login via email/password dengan
        // password yang tidak diketahui siapapun.
        //
        // CATATAN: Uncomment baris di bawah jika Anda yakin semua akun Google
        // belum pernah set password secara sadar. Jika ragu, biarkan comment.
        //
        // DB::statement("UPDATE users SET password = NULL WHERE google_id IS NOT NULL");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('auth_provider');
        });
    }
};
