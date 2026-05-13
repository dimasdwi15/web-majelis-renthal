<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ChatHistory;
use App\Services\GroqChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function __construct(private GroqChatService $groqService) {}

    // ── POST /api/chat ────────────────────────────────────────────────────
    public function send(Request $request): JsonResponse
    {
        // 1. Validasi input
        $validated = $request->validate([
            'message'    => 'required|string|min:1|max:1000',
            'session_id' => 'nullable|string|max:64',
            'history'    => 'nullable|array|max:20',
            'history.*.role'    => 'required_with:history|in:user,assistant',
            'history.*.content' => 'required_with:history|string|max:2000',
        ]);

        $message   = strip_tags(trim($validated['message']));
        $userId    = $request->user()?->id;
        $sessionId = $validated['session_id'] ?? ($userId ? "user_{$userId}" : Str::random(32));

        // 2. Rate limiting: 20 req/menit per user/session
        $rateLimitKey = 'chat_' . ($userId ?? $request->ip());
        if (RateLimiter::tooManyAttempts($rateLimitKey, 20)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return response()->json([
                'success' => false,
                'message' => "Terlalu banyak permintaan. Coba lagi dalam {$seconds} detik.",
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        // 3. Ambil riwayat dari request (atau dari DB jika tidak ada)
        $history = $validated['history'] ?? $this->getRecentHistory($sessionId, $userId);

        // 4. Panggil AI
        $result = $this->groqService->chat($message, $history);

        // 5. Simpan ke database
        $this->saveHistory($userId, $sessionId, $message, $result);

        // 6. Response
        return response()->json([
            'success' => true,
            'data'    => [
                'reply'        => $result['reply'],
                'need_admin'   => $result['need_admin'],
                'whatsapp_url' => $result['whatsapp_url'],
                'from_faq'     => $result['from_faq'],
                'session_id'   => $sessionId,
            ],
        ]);
    }

    // ── GET /api/chat/history ─────────────────────────────────────────────
    public function history(Request $request): JsonResponse
    {
        $userId    = $request->user()?->id;
        $sessionId = $request->query('session_id');

        if (!$userId && !$sessionId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $query = ChatHistory::query()
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when(!$userId && $sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->orderBy('created_at', 'asc')
            ->limit(100);

        $messages = $query->get(['role', 'message', 'need_admin', 'whatsapp_url', 'created_at']);

        return response()->json([
            'success' => true,
            'data'    => $messages,
        ]);
    }

    // ── GET /api/chat/clear ───────────────────────────────────────────────
    public function clear(Request $request): JsonResponse
    {
        $userId    = $request->user()?->id;
        $sessionId = $request->query('session_id');

        if (!$userId && !$sessionId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        ChatHistory::query()
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when(!$userId && $sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->delete();

        return response()->json(['success' => true, 'message' => 'Riwayat chat berhasil dihapus.']);
    }

    // ────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ────────────────────────────────────────────────────────────────────────

    private function getRecentHistory(?int $userId, string $sessionId): array
    {
        return ChatHistory::query()
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when(!$userId, fn($q) => $q->where('session_id', $sessionId))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['role', 'message'])
            ->reverse()
            ->map(fn($h) => ['role' => $h->role, 'content' => $h->message])
            ->toArray();
    }

    private function saveHistory(?int $userId, string $sessionId, string $message, array $result): void
    {
        $base = [
            'user_id'    => $userId,
            'session_id' => $sessionId,
            'tokens_used' => 0,
            'model'      => config('services.ai.groq_model'),
        ];

        ChatHistory::create(array_merge($base, [
            'role'         => 'user',
            'message'      => $message,
            'need_admin'   => false,
            'whatsapp_url' => null,
        ]));

        ChatHistory::create(array_merge($base, [
            'role'         => 'assistant',
            'message'      => $result['reply'],
            'need_admin'   => $result['need_admin'],
            'whatsapp_url' => $result['whatsapp_url'],
            'tokens_used'  => $result['tokens_used'],
        ]));
    }
}
