<?php

namespace App\Services\Recommendation;

use App\Models\Barang;
use App\Models\RecommendationHistory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Client\ConnectionException;

class ImageRecommendationService
{
    private string $groqApiKey;
    private string $visionModel;
    private string $groqEndpoint = 'https://api.groq.com/openai/v1/chat/completions';

    // ── Peta kata kunci bahasa Indonesia → kata kunci bahasa Inggris ──────────
    // Agar detected_items dari AI (bahasa Inggris) bisa mencocokkan nama
    // barang di database yang ditulis dalam bahasa Indonesia.
    private array $translationMap = [
        'tent'           => ['tenda'],
        'sleeping bag'   => ['sleeping bag', 'sleeping'],
        'backpack'       => ['carrier', 'ransel', 'tas'],
        'headlamp'       => ['headlamp', 'head lamp', 'senter kepala'],
        'flashlight'     => ['senter', 'headlamp'],
        'jacket'         => ['jaket'],
        'rain jacket'    => ['jas hujan', 'jaket hujan'],
        'raincoat'       => ['jas hujan'],
        'boots'          => ['sepatu', 'boots'],
        'hiking boots'   => ['sepatu hiking', 'sepatu gunung'],
        'shoes'          => ['sepatu'],
        'trekking pole'  => ['trekking pole', 'trekking'],
        'stove'          => ['kompor'],
        'cooking set'    => ['cooking set', 'nesting', 'panci'],
        'mattress'       => ['matras'],
        'sleeping pad'   => ['matras'],
        'flysheet'       => ['flysheet', 'fly sheet', 'tarp'],
        'tarp'           => ['tarp', 'flysheet', 'fly sheet'],
        'chair'          => ['kursi'],
        'table'          => ['meja'],
        'pants'          => ['celana'],
        'gloves'         => ['sarung tangan', 'gloves'],
        'sandals'        => ['sandal'],
        'lantern'        => ['lantern', 'lampu'],
        'compass'        => ['kompas'],
        'first aid'      => ['p3k', 'first aid'],
        'rope'           => ['tali', 'rope'],
        'bag'            => ['tas', 'carrier', 'ransel'],
        'fleece'         => ['fleece', 'jaket'],
        'waterproof'     => ['waterproof', 'anti air'],
    ];

    public function __construct()
    {
        $this->groqApiKey  = config('services.ai.groq_api_key', '');
        $this->visionModel = config('services.ai.groq_vision_model', 'meta-llama/llama-4-scout-17b-16e-instruct');
    }

    // ────────────────────────────────────────────────────────────────────────
    // Public API
    // ────────────────────────────────────────────────────────────────────────

    public function recommend(UploadedFile $image, ?int $userId = null): array
    {
        $imageContent = $this->readImageContent($image);
        $base64       = base64_encode($imageContent);
        $mime         = $image->getMimeType() ?: 'image/jpeg';
        $dataUrl      = "data:{$mime};base64,{$base64}";

        $aiResult = $this->analyzeWithGroq($dataUrl);

        $isFallback      = empty($aiResult['tags']) && empty($aiResult['detected_items']);
        $recommendations = $isFallback
            ? $this->getFallbackRecommendations()
            : $this->matchBarang($aiResult['tags'], $aiResult['detected_items']);

        $message = $isFallback
            ? 'Menampilkan barang populer'
            : 'Rekomendasi berdasarkan analisis gambar';

        if ($userId) {
            $this->saveHistory($userId, $aiResult, $isFallback, $recommendations);
        }

        return [
            'message'         => $message,
            'is_fallback'     => $isFallback,
            'ai_result'       => $aiResult,
            'recommendations' => $recommendations,
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    // Matching utama — gabungan tag + nama barang
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Cocokkan barang berdasarkan:
     *   1. Tag slug  → match_score +1 per tag cocok
     *   2. Nama barang mengandung keyword dari detected_items → name_score +2
     *
     * Barang yang cocok di kedua kriteria mendapat skor lebih tinggi.
     */
    private function matchBarang(array $tags, array $detectedItems): Collection
    {
        // Kumpulkan semua keyword pencarian nama (Indonesia + Inggris)
        $nameKeywords = $this->buildNameKeywords($detectedItems);

        // ── Ambil kandidat dari dua sumber ──────────────────────────────────

        // A) Barang yang punya tag cocok
        $tagMatched = collect();
        if (! empty($tags)) {
            $tagMatched = Barang::with(['fotos', 'kategori', 'tags'])
                ->where('status', 'aktif')
                ->whereHas('tags', fn($q) => $q->whereIn('slug', $tags))
                ->get();
        }

        // B) Barang yang namanya mengandung keyword detected_items
        $nameMatched = collect();
        if (! empty($nameKeywords)) {
            $query = Barang::with(['fotos', 'kategori', 'tags'])
                ->where('status', 'aktif');

            $query->where(function ($q) use ($nameKeywords) {
                foreach ($nameKeywords as $keyword) {
                    $q->orWhere('nama', 'like', "%{$keyword}%");
                }
            });

            $nameMatched = $query->get();
        }

        // ── Gabungkan & hindari duplikat ────────────────────────────────────
        $allBarang = $tagMatched->merge($nameMatched)->unique('id');

        if ($allBarang->isEmpty()) {
            return $this->getFallbackRecommendations();
        }

        // ── Hitung skor tiap barang ─────────────────────────────────────────
        return $allBarang->map(function (Barang $barang) use ($tags, $nameKeywords) {

            // Skor dari tag
            $barangTagSlugs = $barang->tags->pluck('slug')->toArray();
            $matchedTags    = array_values(array_intersect($tags, $barangTagSlugs));
            $tagScore       = count($matchedTags);

            // Skor dari kecocokan nama (+2 per keyword yang cocok di nama)
            $namaLower = strtolower($barang->nama);
            $nameScore = 0;
            $matchedKeywords = [];
            foreach ($nameKeywords as $keyword) {
                if (str_contains($namaLower, strtolower($keyword))) {
                    $nameScore += 2;
                    $matchedKeywords[] = $keyword;
                }
            }

            return [
                'barang'           => $barang,
                'match_score'      => $tagScore + $nameScore,
                'matched_tags'     => $matchedTags,
                'matched_keywords' => $matchedKeywords, // opsional untuk debug
            ];
        })
        ->filter(fn($item) => $item['match_score'] > 0)
        ->sortByDesc('match_score')
        ->values();
    }

    /**
     * Bangun daftar keyword nama dari detected_items AI.
     * Contoh: ["headlamp"] → ["headlamp", "head lamp", "senter kepala"]
     */
    private function buildNameKeywords(array $detectedItems): array
    {
        $keywords = [];

        foreach ($detectedItems as $item) {
            $itemLower = strtolower(trim($item));

            // Tambahkan item asli (bahasa Inggris) sebagai keyword
            $keywords[] = $itemLower;

            // Tambahkan terjemahan Indonesia jika ada di map
            if (isset($this->translationMap[$itemLower])) {
                $keywords = array_merge($keywords, $this->translationMap[$itemLower]);
                continue;
            }

            // Partial match: cek apakah item mengandung/cocok dengan key di map
            foreach ($this->translationMap as $key => $translations) {
                if (str_contains($itemLower, $key) || str_contains($key, $itemLower)) {
                    $keywords = array_merge($keywords, $translations);
                }
            }
        }

        return array_values(array_unique(array_filter($keywords)));
    }

    // ────────────────────────────────────────────────────────────────────────
    // Baca konten file — aman di Windows & Linux
    // ────────────────────────────────────────────────────────────────────────

    private function readImageContent(UploadedFile $image): string
    {
        $realPath = $image->getRealPath();

        if ($realPath !== false && $realPath !== '' && file_exists($realPath)) {
            return file_get_contents($realPath);
        }

        Log::info('ImageRecommendationService: getRealPath() kosong, pakai fallback disk temp.');

        $tempName = 'temp_recommendation_' . uniqid() . '.' . $image->getClientOriginalExtension();
        $tempPath = 'recommendation_temp/' . $tempName;

        Storage::disk('local')->put($tempPath, $image->get());
        $fullPath = Storage::disk('local')->path($tempPath);
        $content  = file_get_contents($fullPath);
        Storage::disk('local')->delete($tempPath);

        if ($content === false) {
            throw new \RuntimeException('Gagal membaca konten file gambar.');
        }

        return $content;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Groq Vision API
    // ────────────────────────────────────────────────────────────────────────

    private function analyzeWithGroq(string $dataUrl): array
    {
        $defaultResult = [
            'detected_items' => [],
            'tags'           => [],
            'confidence'     => 0.0,
            'description'    => '',
        ];

        if (empty($this->groqApiKey)) {
            Log::warning('ImageRecommendationService: GROQ_API_KEY belum diset di .env');
            return $defaultResult;
        }

        $prompt = <<<'PROMPT'
Kamu adalah asisten yang menganalisis gambar perlengkapan outdoor/camping/hiking.

Analisis gambar ini dan kembalikan HANYA JSON murni (tanpa penjelasan, tanpa markdown, tanpa ```):
{
  "detected_items": ["nama item 1", "nama item 2"],
  "tags": ["tag1", "tag2"],
  "confidence": 0.85,
  "description": "Deskripsi singkat apa yang terlihat"
}

Tag yang tersedia — gunakan HANYA slug dari daftar ini:
- waterproof  : tenda waterproof, jas hujan, sepatu anti air
- shelter     : tenda, tarp, flysheet
- windproof   : jaket windbreaker, shelter
- insulating  : sleeping bag, jaket down, matras
- footwear    : sepatu gunung, sandal gunung
- carrier     : carrier, daypack, tas ransel
- cooking     : kompor, nesting, cooking set, peralatan masak
- lighting    : headlamp, senter, lantern
- navigation  : kompas, GPS, peta
- safety      : P3K, peluit, survival blanket
- sleeping    : sleeping bag, matras, bantal

Jika tidak ada perlengkapan outdoor yang terdeteksi, kembalikan tags sebagai [].
PROMPT;

        try {
            $response = Http::withToken($this->groqApiKey)
                ->timeout(30)
                ->post($this->groqEndpoint, [
                    'model'      => $this->visionModel,
                    'max_tokens' => 500,
                    'messages'   => [
                        [
                            'role'    => 'user',
                            'content' => [
                                [
                                    'type'      => 'image_url',
                                    'image_url' => ['url' => $dataUrl],
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                Log::error('Groq API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return $defaultResult;
            }

            $content = $response->json('choices.0.message.content', '');

            return $this->parseAiResponse($content);

        } catch (ConnectionException $e) {
            Log::error('Groq connection error: ' . $e->getMessage());
            return $defaultResult;
        } catch (\Throwable $e) {
            Log::error('Groq unexpected error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return $defaultResult;
        }
    }

    private function parseAiResponse(string $content): array
    {
        $cleaned = preg_replace('/```(?:json)?\s*([\s\S]*?)```/', '$1', $content);
        $cleaned = trim($cleaned ?? $content);

        $data = json_decode($cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            Log::warning('Groq response bukan JSON valid', ['raw' => $content]);
            return [
                'detected_items' => [],
                'tags'           => [],
                'confidence'     => 0.0,
                'description'    => $content,
            ];
        }

        $allowedTags = [
            'waterproof', 'shelter', 'windproof', 'insulating', 'footwear',
            'carrier', 'cooking', 'lighting', 'navigation', 'safety', 'sleeping',
        ];

        $tags = array_values(array_filter(
            (array) ($data['tags'] ?? []),
            fn($t) => in_array($t, $allowedTags, true)
        ));

        return [
            'detected_items' => array_values((array) ($data['detected_items'] ?? [])),
            'tags'           => $tags,
            'confidence'     => (float) ($data['confidence'] ?? 0.0),
            'description'    => (string) ($data['description'] ?? ''),
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    // Fallback
    // ────────────────────────────────────────────────────────────────────────

    private function getFallbackRecommendations(): Collection
    {
        $barangs = Barang::with(['fotos', 'kategori', 'tags'])
            ->where('status', 'aktif')
            ->inRandomOrder()
            ->limit(10)
            ->get();

        return $barangs->map(fn(Barang $barang) => [
            'barang'           => $barang,
            'match_score'      => 0,
            'matched_tags'     => [],
            'matched_keywords' => [],
        ])->values();
    }

    // ────────────────────────────────────────────────────────────────────────
    // History
    // ────────────────────────────────────────────────────────────────────────

    private function saveHistory(
        int        $userId,
        array      $aiResult,
        bool       $isFallback,
        Collection $recommendations,
    ): void {
        try {
            RecommendationHistory::create([
                'user_id'            => $userId,
                'image_path'         => null,
                'ai_detected_items'  => $aiResult['detected_items'],
                'ai_tags'            => $aiResult['tags'],
                'ai_confidence'      => $aiResult['confidence'],
                'recommended_barang' => $recommendations->pluck('barang.id')->filter()->values()->toArray(),
                'is_fallback'        => $isFallback,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gagal simpan recommendation history: ' . $e->getMessage());
        }
    }
}
