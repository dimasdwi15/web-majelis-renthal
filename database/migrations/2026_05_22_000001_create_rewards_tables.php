<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. USER REWARDS — XP, Level, Streak per user ─────────────────
        Schema::create('user_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('total_xp')->default(0);   // XP kumulatif sepanjang masa
            $table->unsignedBigInteger('current_xp')->default(0); // XP di level sekarang (tidak reset saat naik level)
            $table->unsignedInteger('level')->default(1);
            $table->unsignedInteger('available_boxes')->default(0);

            // Daily check-in streak
            $table->unsignedInteger('current_streak')->default(0);   // 0–6 (hari ke-1 s/d 7)
            $table->date('last_checkin_date')->nullable();            // tanggal terakhir claim
            $table->boolean('is_daily_claimed')->default(false);      // sudah claim hari ini?

            $table->timestamps();

            $table->unique('user_id');
        });

        // ── 2. VOUCHER TEMPLATES — master katalog voucher & mystery box ──
        Schema::create('voucher_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['discount', 'free_item'])->default('discount');
            $table->enum('rarity', ['Common', 'Rare', 'Epic'])->default('Common');

            // Untuk discount voucher
            $table->unsignedInteger('discount_amount')->default(0);  // nominal potongan Rp
            $table->unsignedInteger('min_checkout')->default(0);      // min. total sewa

            // Untuk free_item voucher
            $table->foreignId('free_barang_id')->nullable()->constrained('barang')->nullOnDelete();

            // XP cost untuk redeem dari katalog (null = tidak bisa dibeli, hanya dari mystery box)
            $table->unsignedInteger('xp_cost')->nullable();

            // Validity
            $table->unsignedInteger('valid_days')->default(7);       // berlaku N hari setelah diterima
            $table->boolean('is_active')->default(true);
            $table->boolean('is_mystery_pool')->default(false);       // masuk pool mystery box?

            $table->timestamps();
        });

        // ── 3. USER VOUCHERS — voucher yang dimiliki user ────────────────
        Schema::create('user_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voucher_template_id')->constrained()->cascadeOnDelete();
            $table->string('unique_code')->unique(); // kode unik per voucher user
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_in_transaksi_id')
                  ->nullable()
                  ->constrained('transaksi')
                  ->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'used_at']);
        });

        // ── 4. XP LOGS — riwayat semua perolehan & pengeluaran XP ───────
        Schema::create('xp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');                        // positif = earn, negatif = spend
            $table->string('source');                         // 'checkout', 'daily_checkin', 'redeem', dll
            $table->string('description');                    // deskripsi yang ditampilkan di UI
            $table->foreignId('transaksi_id')->nullable()->constrained('transaksi')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        // ── 5. Tambah kolom xp_rewarded ke transaksi ────────────────────
        Schema::table('transaksi', function (Blueprint $table) {
            $table->boolean('xp_rewarded')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropColumn('xp_rewarded');
        });

        Schema::dropIfExists('xp_logs');
        Schema::dropIfExists('user_vouchers');
        Schema::dropIfExists('voucher_templates');
        Schema::dropIfExists('user_rewards');
    }
};
