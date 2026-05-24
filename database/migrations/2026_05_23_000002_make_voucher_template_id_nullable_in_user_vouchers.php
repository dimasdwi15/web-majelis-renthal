<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat voucher_template_id nullable di user_vouchers.
     * Diperlukan karena voucher dari mystery box tidak memiliki template,
     * hanya memiliki mystery_box_item_id.
     */
    public function up(): void
    {
        Schema::table('user_vouchers', function (Blueprint $table) {
            // Drop foreign key lama dulu
            $table->dropForeign(['voucher_template_id']);

            // Ubah kolom menjadi nullable
            $table->foreignId('voucher_template_id')
                  ->nullable()
                  ->change();

            // Tambahkan kembali foreign key constraint
            $table->foreign('voucher_template_id')
                  ->references('id')
                  ->on('voucher_templates')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_vouchers', function (Blueprint $table) {
            $table->dropForeign(['voucher_template_id']);
            $table->foreignId('voucher_template_id')
                  ->nullable(false)
                  ->change();
            $table->foreign('voucher_template_id')
                  ->references('id')
                  ->on('voucher_templates')
                  ->cascadeOnDelete();
        });
    }
};
