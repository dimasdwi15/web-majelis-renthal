<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\WeatherTagRule;
use Illuminate\Database\Seeder;

/**
 * WeatherTagRuleSeeder — mapping kondisi cuaca OWM → tag fungsional.
 *
 * Kondisi cuaca dari OpenWeatherMap (field `main`, lowercase):
 *   thunderstorm, drizzle, rain, snow, mist, smoke, haze, fog,
 *   sand, dust, ash, squall, tornado, clear, clouds
 *
 * Prioritas: angka lebih kecil = lebih diprioritaskan dalam urutan tampil.
 *
 * CARA UBAH: Admin cukup ubah data tabel ini melalui panel admin
 * atau jalankan ulang seeder ini. Tidak perlu ubah kode apapun.
 */
class WeatherTagRuleSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil semua tag, index by slug untuk kemudahan lookup
        $tags = Tag::all()->keyBy('slug');

        // Validasi: pastikan TagSeeder sudah dijalankan dulu
        if ($tags->isEmpty()) {
            $this->command->error('❌ Tidak ada tag ditemukan. Jalankan TagSeeder terlebih dahulu.');
            return;
        }

        /**
         * Format: [weather_condition, tag_slug, prioritas]
         *
         * Logika desain:
         * - HUJAN/BADAI → waterproof & shelter jadi prioritas utama
         * - CERAH       → cooking & footwear (aktivitas aktif)
         * - BERAWAN     → shelter & carrier (siap segala kemungkinan)
         * - DINGIN      → insulating & sleeping (suhu rendah)
         * - KABUT/ASAP  → safety & shelter
         * - DEFAULT     → shelter & carrier (rekomendasi umum)
         */
        $rules = [
            // 🌩️ Thunderstorm
            ['thunderstorm', 'shelter',    1],
            ['thunderstorm', 'waterproof', 2],
            ['thunderstorm', 'insulating', 3],
            ['thunderstorm', 'windproof',  4],
            ['thunderstorm', 'safety',     5],
            ['thunderstorm', 'lighting',   6],

            // 🌧️ Rain
            ['rain', 'waterproof', 1],
            ['rain', 'shelter',    2],
            ['rain', 'footwear',   3],
            ['rain', 'insulating', 4],
            ['rain', 'carrier',    5],
            ['rain', 'lighting',   6],

            // 🌦️ Drizzle
            ['drizzle', 'waterproof', 1],
            ['drizzle', 'shelter',    2],
            ['drizzle', 'footwear',   3],
            ['drizzle', 'carrier',    4],

            // ❄️ Snow
            ['snow', 'insulating', 1],
            ['snow', 'shelter',    2],
            ['snow', 'waterproof', 3],
            ['snow', 'sleeping',   4],
            ['snow', 'windproof',  5],
            ['snow', 'footwear',   6],

            // ☀️ Clear
            ['clear', 'cooking',    1],
            ['clear', 'footwear',   2],
            ['clear', 'carrier',    3],
            ['clear', 'navigation', 4],
            ['clear', 'lighting',   5],

            // ☁️ Clouds
            ['clouds', 'shelter',    1],
            ['clouds', 'carrier',    2],
            ['clouds', 'waterproof', 3],
            ['clouds', 'cooking',    4],
            ['clouds', 'footwear',   5],

            // 🌫️ Mist / Fog / Haze / Smoke / Ash
            ['mist',  'shelter',  1],
            ['mist',  'safety',   2],
            ['mist',  'lighting', 3],

            ['fog',   'shelter',  1],
            ['fog',   'safety',   2],
            ['fog',   'lighting', 3],

            ['haze',  'shelter',  1],
            ['haze',  'safety',   2],

            ['smoke', 'shelter',  1],
            ['smoke', 'safety',   2],

            ['ash',   'shelter',  1],
            ['ash',   'safety',   2],

            // 🌪️ Squall / Tornado
            ['squall',  'shelter',   1],
            ['squall',  'windproof', 2],
            ['squall',  'safety',    3],

            ['tornado', 'shelter',   1],
            ['tornado', 'safety',    2],

            // 🔁 Default — fallback jika kondisi tidak dikenali
            ['default', 'shelter',  1],
            ['default', 'carrier',  2],
            ['default', 'cooking',  3],
            ['default', 'footwear', 4],
            ['default', 'sleeping', 5],
        ];

        $created = 0;
        $skipped = 0;

        foreach ($rules as [$condition, $tagSlug, $prioritas]) {
            $tag = $tags->get($tagSlug);

            if (!$tag) {
                $this->command->warn("⚠️  Tag '{$tagSlug}' tidak ditemukan, dilewati.");
                $skipped++;
                continue;
            }

            WeatherTagRule::updateOrCreate(
                [
                    'weather_condition' => $condition,
                    'tag_id'            => $tag->id,
                ],
                ['prioritas' => $prioritas]
            );

            $created++;
        }

        $this->command->info("✅ Weather rules berhasil di-seed ({$created} rules, {$skipped} dilewati)");
    }
}
