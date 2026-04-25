<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\Tag;
use Illuminate\Database\Seeder;

/**
 * BarangTagSeeder — assign tag ke barang yang sudah ada di database.
 *
 * Seeder ini menggunakan `nama` barang untuk mapping karena ID bisa berbeda
 * di setiap environment. Di production, lebih baik admin assign tag
 * manual lewat panel admin.
 *
 * Seeder ini aman dijalankan berulang (syncWithoutDetaching tidak hapus tag yang ada).
 *
 * CATATAN PENTING:
 * Untuk barang baru yang ditambah admin, assign tag via panel admin —
 * tidak perlu ubah seeder ini.
 */
class BarangTagSeeder extends Seeder
{
    public function run(): void
    {
        // Index semua tag by slug untuk kemudahan lookup
        $tags = Tag::all()->keyBy('slug');

        if ($tags->isEmpty()) {
            $this->command->error('❌ Tidak ada tag. Jalankan TagSeeder terlebih dahulu.');
            return;
        }

        /**
         * Mapping nama barang (lowercase, trim) → array slug tag.
         *
         * Key menggunakan nama barang yang ada di DB saat ini.
         * Jika nama barang diubah admin, mapping ini perlu diupdate juga.
         *
         * REKOMENDASI: Setelah sistem live, gunakan panel admin
         * untuk assign tag ke barang baru — jangan andalkan seeder ini.
         */
        $mappingNama = [
            'tenda arei' => [
                'shelter', 'waterproof', 'windproof',
            ],
            'sleeping bag eiger' => [
                'insulating', 'sleeping',
            ],
            'barang 3' => [
                'shelter',
            ],
            'barang 4' => [
                'shelter',
            ],
            'barang 5' => [
                'sleeping', 'insulating',
            ],
            'sepatu eiger' => [
                'footwear', 'waterproof',
            ],
            'kompor bogabo' => [
                'cooking',
            ],
            'carrier eiger' => [
                'carrier',
            ],
            'tarp tent' => [
                'shelter', 'waterproof', 'windproof',
            ],
        ];

        $synced  = 0;
        $missing = 0;

        foreach ($mappingNama as $namaBarang => $tagSlugs) {
            // Cari barang dengan nama yang cocok (case-insensitive)
            $barang = Barang::whereRaw('LOWER(nama) = ?', [strtolower($namaBarang)])->first();

            if (!$barang) {
                $this->command->warn("⚠️  Barang '{$namaBarang}' tidak ditemukan, dilewati.");
                $missing++;
                continue;
            }

            // Kumpulkan ID tag yang valid
            $tagIds = collect($tagSlugs)
                ->map(fn($slug) => $tags->get($slug)?->id)
                ->filter()
                ->values()
                ->toArray();

            if (empty($tagIds)) {
                $this->command->warn("⚠️  Tidak ada tag valid untuk '{$namaBarang}', dilewati.");
                continue;
            }

            // syncWithoutDetaching: tambah tag baru tapi jangan hapus tag yang sudah ada
            $barang->tags()->syncWithoutDetaching($tagIds);

            $this->command->line("  → {$barang->nama} — " . implode(', ', $tagSlugs));
            $synced++;
        }

        $this->command->info("✅ Barang tags berhasil di-sync ({$synced} barang, {$missing} tidak ditemukan)");
    }
}
