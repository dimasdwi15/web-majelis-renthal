<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImageRecommendationRequest;
use App\Http\Resources\RecommendationResource;
use App\Services\Recommendation\ImageRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ImageRecommendationController extends Controller
{
    public function __construct(
        private readonly ImageRecommendationService $recommendationService,
    ) {}

    /**
     * POST /api/recommendation/image
     *
     * Body: multipart/form-data
     *   image: file (jpeg/png/webp/heic, max 5MB)
     */
    public function analyze(ImageRecommendationRequest $request): JsonResponse
    {
        try {
            $userId = $request->user()?->id; // null jika guest

            $result = $this->recommendationService->recommend(
                image:  $request->file('image'),
                userId: $userId,
            );

            $recommendations = $result['recommendations']->map(function ($item) {
                return new RecommendationResource(
                    $item['barang'],
                    $item['match_score'],
                    $item['matched_tags'],
                );
            })->values();

            return response()->json([
                'success'     => true,
                'message'     => $result['message'],
                'is_fallback' => $result['is_fallback'],
                'ai'          => [
                    'detected_items' => $result['ai_result']['detected_items'],
                    'tags'           => $result['ai_result']['tags'],
                    'confidence'     => $result['ai_result']['confidence'],
                    'description'    => $result['ai_result']['description'] ?? '',
                ],
                'total'           => count($recommendations),
                'recommendations' => $recommendations,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('ImageRecommendationController error', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success'     => false,
                'message'     => 'Terjadi kesalahan saat memproses gambar.',
                'is_fallback' => true,
                'ai'          => [
                    'detected_items' => [],
                    'tags'           => [],
                    'confidence'     => 0,
                    'description'    => '',
                ],
                'total'           => 0,
                'recommendations' => [],
            ], 500);
        }
    }

    /**
     * GET /api/recommendation/history
     * Riwayat rekomendasi user yang login.
     */
    public function history(Request $request): JsonResponse
    {
        $histories = $request->user()
            ->recommendationHistories()
            ->latest()
            ->limit(20)
            ->get([
                'id',
                'ai_detected_items',
                'ai_tags',
                'ai_confidence',
                'is_fallback',
                'created_at',
            ]);

        return response()->json([
            'success' => true,
            'data'    => $histories,
        ]);
    }
}
