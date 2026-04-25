<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `tags` — daftar tag fungsional barang (waterproof, shelter, dll).
 *
 * Tag bersifat FUNGSIONAL, bukan kategori. Contoh:
 *   - waterproof  → cocok untuk hujan
 *   - shelter     → perlindungan dari cuaca (tenda, tarp)
 *   - insulating  → menahan hawa dingin (sleeping bag, jaket)
 *   - footwear    → alas kaki untuk medan
 *   - cooking     → alat memasak
 *   - carrier     → penyimpanan / tas gunung
 *   - lighting    → penerangan
 *   - navigation  → alat navigasi
 *
 * Admin bisa tambah tag baru kapan saja dari panel admin.
 * Sistem rekomendasi membaca dari tabel ini — tidak ada yang hardcoded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();

            // Slug unik, digunakan sebagai referensi internal (tidak berubah meski label berubah)
            $table->string('slug')->unique();

            // Label yang ditampilkan ke user/admin
            $table->string('label');

            // Deskripsi singkat untuk admin (opsional)
            $table->string('deskripsi')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
