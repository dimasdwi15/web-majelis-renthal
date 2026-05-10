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
        // 1. Baca konten gambar — dengan fallback khusus Windows/Laragon
        $imageContent = $this->readImageContent($image);

        $base64  = base64_encode($imageContent);
        $mime    = $image->getMimeType() ?: 'image/jpeg';
        $dataUrl = "data:{$mime};base64,{$base64}";

        // 2. Kirim ke Groq Vision
        $aiResult = $this->analyzeWithGroq($dataUrl);

        // 3. Matching barang
        $isFallback      = empty($aiResult['tags']);
        $recommendations = $isFallback
            ? $this->getFallbackRecommendations()
            : $this->matchBarangByTags($aiResult['tags']);

        $message = $isFallback
            ? 'Menampilkan barang populer'
            : 'Rekomendasi berdasarkan analisis gambar';

        // 4. Simpan history jika user login
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
    // Baca konten file — aman di Windows & Linux
    // ────────────────────────────────────────────────────────────────────────

    private function readImageContent(UploadedFile $image): string
    {
        // Coba getRealPath() dulu (normal case — Linux / macOS)
        $realPath = $image->getRealPath();

        if ($realPath !== false && $realPath !== '' && file_exists($realPath)) {
            return file_get_contents($realPath);
        }

        // Fallback untuk Windows/Laragon: simpan sementara ke storage/app/temp
        // lalu baca dari sana, lalu hapus.
        Log::info('ImageRecommendationService: getRealPath() kosong, pakai fallback disk temp.');

        $tempName = 'temp_recommendation_' . uniqid() . '.' . $image->getClientOriginalExtension();
        $tempPath = 'recommendation_temp/' . $tempName;

        // Simpan ke storage/app (disk default)
        Storage::disk('local')->put($tempPath, $image->get());

        $fullPath = Storage::disk('local')->path($tempPath);
        $content  = file_get_contents($fullPath);

        // Hapus file temp setelah dibaca
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
    // Barang Matching
    // ────────────────────────────────────────────────────────────────────────

    private function matchBarangByTags(array $tags): Collection
    {
        if (empty($tags)) {
            return $this->getFallbackRecommendations();
        }

        $barangs = Barang::with(['fotos', 'kategori', 'tags'])
            ->where('status', 'aktif')
            ->whereHas('tags', fn($q) => $q->whereIn('slug', $tags))
            ->get();

        return $barangs->map(function (Barang $barang) use ($tags) {
            $barangTagSlugs = $barang->tags->pluck('slug')->toArray();
            $matchedTags    = array_values(array_intersect($tags, $barangTagSlugs));

            return [
                'barang'       => $barang,
                'match_score'  => count($matchedTags),
                'matched_tags' => $matchedTags,
            ];
        })->sortByDesc('match_score')->values();
    }

    private function getFallbackRecommendations(): Collection
    {
        $barangs = Barang::with(['fotos', 'kategori', 'tags'])
            ->where('status', 'aktif')
            ->inRandomOrder()
            ->limit(10)
            ->get();

        return $barangs->map(fn(Barang $barang) => [
            'barang'       => $barang,
            'match_score'  => 0,
            'matched_tags' => [],
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
            $recommendedBarangIds = $recommendations
                ->pluck('barang.id')
                ->filter()
                ->values()
                ->toArray();

            RecommendationHistory::create([
                'user_id'            => $userId,
                'image_path'         => null,
                'ai_detected_items'  => $aiResult['detected_items'],
                'ai_tags'            => $aiResult['tags'],
                'ai_confidence'      => $aiResult['confidence'],
                'recommended_barang' => $recommendedBarangIds,
                'is_fallback'        => $isFallback,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gagal simpan recommendation history: ' . $e->getMessage());
        }
    }
}
