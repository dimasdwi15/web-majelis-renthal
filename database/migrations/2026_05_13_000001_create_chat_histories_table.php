<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('session_id', 64)->index(); // untuk guest user
            $table->enum('role', ['user', 'assistant']);
            $table->text('message');
            $table->boolean('need_admin')->default(false);
            $table->string('whatsapp_url', 512)->nullable();
            $table->integer('tokens_used')->default(0);
            $table->string('model', 100)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'session_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_histories');
    }
};
