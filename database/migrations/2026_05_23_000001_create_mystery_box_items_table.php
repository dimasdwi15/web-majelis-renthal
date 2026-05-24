<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── TABEL mystery_box_items ──────────────────────────────────────────
        // Pool hadiah yang bisa keluar dari mystery box.
        // Dikelola admin secara independen dari voucher_templates.
        // Field snapshot (_snapshot) menyimpan data barang PERMANEN saat item dibuat,
        // sehingga walau admin hapus/edit barang, mystery box tetap tampil dengan benar.
        Schema::create('mystery_box_items', function (Blueprint $table) {
            $table->id();

            // Judul & deskripsi yang ditampilkan ke user
            $table->string('title');
            $table->text('description')->nullable();

            // Jenis hadiah
            $table->enum('type', ['discount', 'free_rental'])->default('discount');

            // Kelangkaan (untuk UI rarity badge)
            $table->enum('rarity', ['Common', 'Rare', 'Epic'])->default('Common');

            // ── Untuk type = discount ────────────────────────────────────────
            $table->unsignedInteger('discount_amount')->default(0); // potongan Rp
            $table->unsignedInteger('min_checkout')->default(0);     // min. total sewa

            // ── Untuk type = free_rental ─────────────────────────────────────
            // Referensi barang (soft reference — nullable jika barang dihapus)
            $table->foreignId('barang_id')
                  ->nullable()
                  ->constrained('barang')
                  ->nullOnDelete(); // jika barang dihapus, set null tapi snapshot tetap ada

            // SNAPSHOT: nama & URL foto barang disimpan permanen saat item dibuat
            $table->string('barang_nama_snapshot')->nullable();
            $table->string('barang_foto_url_snapshot')->nullable();

            // ── Bobot probabilitas ────────────────────────────────────────────
            // Makin besar weight, makin sering keluar dari mystery box.
            // Common = 100, Rare = 40, Epic = 10 (rekomendasi)
            $table->unsignedInteger('weight')->default(100);

            // ── Masa berlaku voucher setelah diterima ────────────────────────
            $table->unsignedInteger('valid_days')->default(7);

            // ── Status ───────────────────────────────────────────────────────
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        // ── Tambah kolom snapshot ke user_vouchers ───────────────────────────
        // Agar user_vouchers menyimpan data mystery box item yang diterima,
        // termasuk info hadiah barang (snapshot) dan referensi ke mystery_box_items.
        Schema::table('user_vouchers', function (Blueprint $table) {
            $table->foreignId('mystery_box_item_id')
                  ->nullable()
                  ->after('free_barang_id')
                  ->constrained('mystery_box_items')
                  ->nullOnDelete();

            // Snapshot judul & deskripsi khusus untuk mystery box reward
            $table->string('mystery_title_snapshot')->nullable()->after('mystery_box_item_id');
            $table->string('mystery_foto_url_snapshot')->nullable()->after('mystery_title_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('user_vouchers', function (Blueprint $table) {
            $table->dropForeign(['mystery_box_item_id']);
            $table->dropColumn(['mystery_box_item_id', 'mystery_title_snapshot', 'mystery_foto_url_snapshot']);
        });

        Schema::dropIfExists('mystery_box_items');
    }
};
