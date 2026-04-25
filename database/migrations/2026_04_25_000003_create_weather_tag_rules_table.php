<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `weather_tag_rules` — mapping kondisi cuaca → tag yang direkomendasikan.
 *
 * Contoh data:
 *   rain        → waterproof (prioritas 1)
 *   rain        → shelter    (prioritas 2)
 *   rain        → footwear   (prioritas 3)
 *   thunderstorm→ shelter    (prioritas 1)
 *   thunderstorm→ waterproof (prioritas 2)
 *   clear       → cooking    (prioritas 1)
 *   clear       → footwear   (prioritas 2)
 *   snow        → insulating (prioritas 1)
 *   snow        → shelter    (prioritas 2)
 *
 * `weather_condition` diisi dengan nilai lowercase dari field `main`
 * OpenWeatherMap API: rain, drizzle, thunderstorm, clear, clouds, mist, fog, snow, dll.
 *
 * `prioritas` menentukan urutan tampilan rekomendasi (ASC = utama dulu).
 *
 * Admin dapat menambah/mengubah rule ini dari panel admin
 * tanpa menyentuh kode apapun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_tag_rules', function (Blueprint $table) {
            $table->id();

            // Nilai cuaca dari OpenWeatherMap (lowercase): rain, clear, clouds, dll.
            $table->string('weather_condition');

            $table->foreignId('tag_id')
                ->constrained('tags')
                ->onDelete('cascade');

            // Urutan prioritas tampil — semakin kecil semakin diprioritaskan
            $table->unsignedSmallInteger('prioritas')->default(10);

            // Satu kondisi cuaca tidak boleh map ke tag yang sama dua kali
            $table->unique(['weather_condition', 'tag_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_tag_rules');
    }
};
