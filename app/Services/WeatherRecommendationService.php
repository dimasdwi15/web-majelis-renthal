<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\WeatherTagRule;
use Illuminate\Support\Collection;

/**
 * WeatherRecommendationService
 *
 * Bertanggung jawab untuk mengambil barang yang direkomendasikan
 * berdasarkan kondisi cuaca, menggunakan data dari tabel weather_tag_rules
 * dan barang_tag.
 *
 * TIDAK ADA hardcoded nama kategori, slug, atau ID di sini.
 * Semua driven dari database.
 */
class WeatherRecommendationService
{
    /**
     * Ambil barang yang direkomendasikan untuk kondisi cuaca tertentu.
     *
     * Urutan logika:
     *  1. Cari tag_id yang relevan dengan kondisi cuaca dari weather_tag_rules
     *  2. Cari barang yang memiliki setidaknya satu tag tersebut
     *  3. Filter hanya barang aktif dan stok > 0
     *  4. Urutkan: barang dengan lebih banyak tag yang cocok tampil lebih dulu
     *  5. Fallback ke kondisi 'default' jika tidak ada rule yang cocok
     *
     * @param  string  $weatherCondition  Lowercase OWM weather condition (rain, clear, dll.)
     * @param  int     $limit             Maksimal barang yang dikembalikan
     * @return Collection
     */
    public function getRekomendasi(string $weatherCondition, int $limit = 6): Collection
    {
        // Ambil tag_id untuk kondisi ini, diurutkan by prioritas
        $tagIds = WeatherTagRule::tagIdsForCondition($weatherCondition);

        // Jika tidak ada rule spesifik, coba fallback ke 'default'
        if ($tagIds->isEmpty()) {
            $tagIds = WeatherTagRule::tagIdsForCondition('default');
        }

        // Jika masih kosong (belum ada seed sama sekali), kembalikan koleksi kosong
        if ($tagIds->isEmpty()) {
            return collect();
        }

        // Query barang yang memiliki salah satu tag tersebut
        $barang = Barang::with(['fotoUtama', 'tags'])
            ->tersedia() // scope: status=aktif AND stok>0
            ->whereHas('tags', fn($q) => $q->whereIn('tags.id', $tagIds))
            ->withCount([
                // Hitung berapa tag yang cocok — untuk sorting relevansi
                'tags as cocok_count' => fn($q) => $q->whereIn('tags.id', $tagIds),
            ])
            ->orderByDesc('cocok_count')   // barang dengan lebih banyak tag cocok → lebih atas
            ->orderBy('harga_per_hari')    // harga lebih murah → lebih atas (tie-breaker)
            ->limit($limit)
            ->get();

        return $barang->map(fn(Barang $b) => [
            'id'       => $b->id,
            'nama'     => $b->nama,
            'kategori' => $b->tags->pluck('label')->join(', '), // tampilkan tag, bukan kategori
            'harga'    => (float) $b->harga_per_hari,
            'stok'     => $b->stok,
            'foto'     => $b->fotoUtama?->path_foto,
        ]);
    }

    /**
     * Tentukan level peringatan dan pesan berdasarkan kondisi cuaca dan suhu.
     *
     * Level: danger | warning | good | neutral
     */
    public function getLevelCuaca(string $condition, int $suhu): array
    {
        if ($condition === 'thunderstorm') {
            return [
                'level' => 'danger',
                'pesan' => 'Potensi badai petir! Pastikan tenda sangat kuat dan hindari puncak terbuka.',
                'warna' => 'red',
            ];
        }

        if (in_array($condition, ['rain', 'drizzle'])) {
            return [
                'level' => 'warning',
                'pesan' => 'Hujan diprakirakan. Siapkan tenda waterproof dan jas hujan.',
                'warna' => 'amber',
            ];
        }

        if ($suhu <= 10) {
            return [
                'level' => 'warning',
                'pesan' => 'Suhu sangat dingin! Sleeping bag tebal sangat disarankan.',
                'warna' => 'blue',
            ];
        }

        if (in_array($condition, ['clear', 'clouds'])) {
            return [
                'level' => 'good',
                'pesan' => 'Cuaca cukup baik untuk camping. Tetap persiapkan perlengkapan lengkap.',
                'warna' => 'green',
            ];
        }

        return [
            'level' => 'neutral',
            'pesan' => 'Kondisi cuaca bervariasi. Disarankan membawa perlengkapan lengkap.',
            'warna' => 'gray',
        ];
    }
}
