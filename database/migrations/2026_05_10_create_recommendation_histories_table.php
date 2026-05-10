<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->string('image_path')->nullable();

            $table->json('ai_detected_items')->nullable();

            $table->json('ai_tags')->nullable();

            $table->decimal('ai_confidence', 3, 2)
                  ->default(0.00);

            $table->json('recommended_barang')->nullable();

            $table->boolean('is_fallback')
                  ->default(false);

            $table->timestamps();

            // Index untuk query riwayat per user
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_histories');
    }
};
