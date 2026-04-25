<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

/**
 * TagSeeder — seed tag fungsional barang.
 *
 * PENTING: slug bersifat PERMANEN. Jangan ubah slug setelah sistem berjalan
 * karena slug digunakan sebagai referensi stabil di weather_tag_rules.
 * Yang boleh diubah hanya `label` dan `deskripsi`.
 *
 * Untuk tambah tag baru, cukup tambah entry di array $tags ini
 * lalu jalankan: php artisan db:seed --class=TagSeeder
 */
class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            // ── Perlindungan cuaca ───────────────────────────────────────
            [
                'slug'      => 'waterproof',
                'label'     => 'Waterproof',
                'deskripsi' => 'Tahan air — cocok saat hujan atau medan basah (jas hujan, tenda waterproof, sepatu anti air)',
            ],
            [
                'slug'      => 'shelter',
                'label'     => 'Pelindung / Shelter',
                'deskripsi' => 'Menyediakan perlindungan dari elemen luar (tenda, tarp, flysheet)',
            ],
            [
                'slug'      => 'windproof',
                'label'     => 'Windproof',
                'deskripsi' => 'Tahan angin — penting di dataran tinggi atau musim berangin (jaket windbreaker, shelter)',
            ],

            // ── Kebutuhan suhu dingin ────────────────────────────────────
            [
                'slug'      => 'insulating',
                'label'     => 'Penghangat / Insulating',
                'deskripsi' => 'Menahan panas tubuh di suhu rendah (sleeping bag, jaket down, matras)',
            ],

            // ── Mobilitas & Trekking ─────────────────────────────────────
            [
                'slug'      => 'footwear',
                'label'     => 'Alas Kaki',
                'deskripsi' => 'Alas kaki untuk medan outdoor (sepatu gunung, sandal gunung)',
            ],
            [
                'slug'      => 'carrier',
                'label'     => 'Carrier / Tas',
                'deskripsi' => 'Tempat menyimpan dan membawa perlengkapan (carrier, daypack, drybag)',
            ],

            // ── Dapur lapangan ───────────────────────────────────────────
            [
                'slug'      => 'cooking',
                'label'     => 'Memasak / Cooking',
                'deskripsi' => 'Alat memasak dan makan di lapangan (kompor, nesting, windshield)',
            ],

            // ── Penerangan ───────────────────────────────────────────────
            [
                'slug'      => 'lighting',
                'label'     => 'Penerangan',
                'deskripsi' => 'Sumber cahaya di gelap (headlamp, senter, lantern)',
            ],

            // ── Navigasi & Keselamatan ───────────────────────────────────
            [
                'slug'      => 'navigation',
                'label'     => 'Navigasi',
                'deskripsi' => 'Alat bantu navigasi (kompas, GPS, peta topo)',
            ],
            [
                'slug'      => 'safety',
                'label'     => 'Keselamatan',
                'deskripsi' => 'Perlengkapan keselamatan (P3K, peluit, survival blanket)',
            ],

            // ── Tidur & Istirahat ────────────────────────────────────────
            [
                'slug'      => 'sleeping',
                'label'     => 'Tidur / Sleeping',
                'deskripsi' => 'Perlengkapan tidur (sleeping bag, matras, pillow)',
            ],
        ];

        foreach ($tags as $tag) {
            // updateOrCreate supaya aman jika dijalankan ulang
            Tag::updateOrCreate(
                ['slug' => $tag['slug']],
                [
                    'label'     => $tag['label'],
                    'deskripsi' => $tag['deskripsi'],
                ]
            );
        }

        $this->command->info('✅ Tags berhasil di-seed (' . count($tags) . ' tags)');
    }
}
