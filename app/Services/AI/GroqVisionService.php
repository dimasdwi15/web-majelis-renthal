<?php

namespace App\Services\AI;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GroqVisionService
{
    private string $apiKey;
    private string $visionModel;
    private string $baseUrl = 'https://api.groq.com/openai/v1';

    // Map deteksi AI → slug tag di database
    private array $keywordTagMap = [
        // Shelter / Tenda
        'tent'            => ['shelter', 'waterproof'],
        'tarpaulin'       => ['shelter', 'waterproof'],
        'tarp'            => ['shelter', 'waterproof'],
        'flysheet'        => ['shelter', 'waterproof'],
        'canopy'          => ['shelter'],
        'bivy'            => ['shelter', 'sleeping'],

        // Sleeping
        'sleeping bag'    => ['sleeping', 'insulating'],
        'hammock'         => ['sleeping'],
        'mattress'        => ['sleeping'],
        'foam pad'        => ['sleeping'],
        'sleeping pad'    => ['sleeping'],

        // Carrier / Tas
        'backpack'        => ['carrier'],
        'rucksack'        => ['carrier'],
        'carrier'         => ['carrier'],
        'daypack'         => ['carrier'],
        'hydration pack'  => ['carrier', 'safety'],
        'dry bag'         => ['carrier', 'waterproof'],
        'bag'             => ['carrier'],

        // Footwear
        'hiking boots'    => ['footwear', 'waterproof'],
        'boots'           => ['footwear'],
        'shoes'           => ['footwear'],
        'sandals'         => ['footwear'],
        'gaiters'         => ['footwear', 'waterproof'],

        // Pakaian
        'jacket'          => ['insulating', 'windproof'],
        'down jacket'     => ['insulating', 'windproof'],
        'rain jacket'     => ['waterproof', 'windproof'],
        'raincoat'        => ['waterproof'],
        'poncho'          => ['waterproof'],
        'windbreaker'     => ['windproof'],
        'fleece'          => ['insulating'],
        'gloves'          => ['insulating'],
        'hat'             => ['insulating'],
        'pants'           => ['footwear'],

        // Masak / Cooking
        'stove'           => ['cooking'],
        'camp stove'      => ['cooking'],
        'cooking set'     => ['cooking'],
        'cookware'        => ['cooking'],
        'pot'             => ['cooking'],
        'pan'             => ['cooking'],
        'utensils'        => ['cooking'],
        'mess kit'        => ['cooking'],
        'food'            => ['cooking'],

        // Penerangan
        'headlamp'        => ['lighting'],
        'flashlight'      => ['lighting'],
        'torch'           => ['lighting'],
        'lantern'         => ['lighting'],
        'light'           => ['lighting'],

        // Navigasi
        'compass'         => ['navigation'],
        'gps'             => ['navigation'],
        'map'             => ['navigation'],
        'altimeter'       => ['navigation'],

        // Safety
        'first aid kit'   => ['safety'],
        'first aid'       => ['safety'],
        'whistle'         => ['safety'],
        'rope'            => ['safety'],
        'harness'         => ['safety'],
        'carabiner'       => ['safety'],
        'trekking pole'   => ['safety', 'navigation'],
        'trekking poles'  => ['safety', 'navigation'],
        'walking stick'   => ['safety', 'navigation'],
        'ice axe'         => ['safety'],

        // Generik camping/hiking
        'camping'         => ['shelter', 'sleeping', 'cooking'],
        'hiking'          => ['footwear', 'carrier', 'navigation'],
        'climbing'        => ['safety', 'footwear'],
        'outdoor gear'    => ['carrier', 'safety'],
        'gear'            => ['carrier'],
        'equipment'       => ['carrier', 'safety'],
    ];

    public function __construct()
    {
        $this->apiKey      = config('services.ai.groq_api_key', '');
        $this->visionModel = config('services.ai.groq_vision_model', 'meta-llama/llama-4-scout-17b-16e-instruct');
    }

    /**
     * Analisis gambar dan kembalikan detected items + matched tags.
     */
    public function analyzeImage(UploadedFile $file): array
    {
        if (empty($this->apiKey)) {
            Log::warning('GroqVisionService: API key tidak ditemukan, gunakan fallback.');
            return $this->fallbackResponse('API key tidak dikonfigurasi.');
        }

        try {
            // ── Baca konten file — aman di Windows/Laragon ───────────────────
            $imageContent = $this->readFileContent($file);
            $base64       = base64_encode($imageContent);
            $mimeType     = $file->getMimeType() ?? 'image/jpeg';

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type'  => 'application/json',
            ])
            ->timeout(45)
            ->post("{$this->baseUrl}/chat/completions", [
                'model'       => $this->visionModel,
                'max_tokens'  => 512,
                'temperature' => 0.1,
                'messages'    => [
                    [
                        'role'    => 'user',
                        'content' => [
                            [
                                'type'      => 'image_url',
                                'image_url' => [
                                    'url' => "data:{$mimeType};base64,{$base64}",
                                ],
                            ],
                            [
                                'type' => 'text',
                                'text' => $this->buildPrompt(),
                            ],
                        ],
                    ],
                ],
            ]);

            if ($response->failed()) {
                Log::error('Groq Vision API gagal', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return $this->fallbackResponse('Groq Vision API error: ' . $response->status());
            }

            return $this->parseGroqResponse($response->json());

        } catch (\Exception $e) {
            Log::error('GroqVisionService exception', ['error' => $e->getMessage()]);
            return $this->fallbackResponse($e->getMessage());
        }
    }

    /**
     * Ubah detected items dari AI menjadi array slug tag.
     */
    public function mapItemsToTagSlugs(array $detectedItems): array
    {
        $slugs = [];

        foreach ($detectedItems as $item) {
            $itemLower = strtolower(trim($item));

            if (isset($this->keywordTagMap[$itemLower])) {
                $slugs = array_merge($slugs, $this->keywordTagMap[$itemLower]);
                continue;
            }

            foreach ($this->keywordTagMap as $keyword => $mappedSlugs) {
                if (str_contains($itemLower, $keyword) || str_contains($keyword, $itemLower)) {
                    $slugs = array_merge($slugs, $mappedSlugs);
                }
            }
        }

        return array_values(array_unique($slugs));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Baca file — fallback untuk Windows/Laragon (getRealPath() bisa kosong)
    // ─────────────────────────────────────────────────────────────────────────

    private function readFileContent(UploadedFile $file): string
    {
        $realPath = $file->getRealPath();

        // Normal case (Linux/macOS)
        if ($realPath !== false && $realPath !== '' && file_exists($realPath)) {
            return file_get_contents($realPath);
        }

        // Fallback Windows/Laragon: tulis ke disk lokal dulu
        Log::info('GroqVisionService: getRealPath() kosong, pakai fallback disk temp.');

        $tempName = 'groq_temp_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $tempPath = 'groq_temp/' . $tempName;

        Storage::disk('local')->put($tempPath, $file->get());
        $fullPath = Storage::disk('local')->path($tempPath);
        $content  = file_get_contents($fullPath);
        Storage::disk('local')->delete($tempPath);

        if ($content === false) {
            throw new \RuntimeException('Gagal membaca konten file gambar.');
        }

        return $content;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function buildPrompt(): string
    {
        $availableTags = 'waterproof, shelter, windproof, insulating, footwear, carrier, cooking, lighting, navigation, safety, sleeping';

        return <<<PROMPT
You are an outdoor equipment detection assistant. Analyze this image and identify all outdoor gear, camping, or hiking equipment visible.

Respond ONLY with a valid JSON object (no markdown, no explanation):
{
  "detected_items": ["item1", "item2", "item3"],
  "tags": ["tag1", "tag2"],
  "confidence": 0.90,
  "description": "brief description of what you see"
}

Rules:
1. detected_items: English names of outdoor equipment/gear items visible
2. tags: choose ONLY from this list: {$availableTags}
3. confidence: float 0.0-1.0 (how confident you are that outdoor gear is visible)
4. If no outdoor gear detected: return empty arrays and confidence 0.0
5. Return ONLY the JSON object, nothing else
PROMPT;
    }

    private function parseGroqResponse(array $responseData): array
    {
        $content = $responseData['choices'][0]['message']['content'] ?? '';
        $content = trim(preg_replace('/```json\s*|\s*```/', '', $content));

        $parsed = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($parsed)) {
            Log::warning('Groq response JSON parse error', ['content' => $content]);
            return $this->extractFromFreeText($content);
        }

        $detectedItems = array_map('strtolower', $parsed['detected_items'] ?? []);
        $aiTags        = array_map('strtolower', $parsed['tags'] ?? []);
        $confidence    = (float) ($parsed['confidence'] ?? 0.5);

        $mappedSlugs = $this->mapItemsToTagSlugs($detectedItems);
        $allSlugs    = array_values(array_unique(array_merge($aiTags, $mappedSlugs)));

        return [
            'detected_items' => $detectedItems,
            'tags'           => $allSlugs,
            'confidence'     => $confidence,
            'description'    => $parsed['description'] ?? '',
            'is_fallback'    => false,
        ];
    }

    private function extractFromFreeText(string $text): array
    {
        $knownSlugs = [
            'waterproof', 'shelter', 'windproof', 'insulating',
            'footwear', 'carrier', 'cooking', 'lighting',
            'navigation', 'safety', 'sleeping',
        ];

        $textLower  = strtolower($text);
        $foundSlugs = array_filter($knownSlugs, fn($s) => str_contains($textLower, $s));

        return [
            'detected_items' => [],
            'tags'           => array_values($foundSlugs),
            'confidence'     => 0.3,
            'description'    => '',
            'is_fallback'    => false,
        ];
    }

    private function fallbackResponse(string $reason = ''): array
    {
        return [
            'detected_items'  => [],
            'tags'            => [],
            'confidence'      => 0.0,
            'description'     => '',
            'is_fallback'     => true,
            'fallback_reason' => $reason,
        ];
    }
}
