<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FcmService — Kirim FCM Push Notification via HTTP v1 API
 *
 * SETUP (wajib sebelum pakai):
 * 1. Buka Firebase Console → Project Settings → Service Accounts
 * 2. Klik "Generate new private key" → download file JSON
 * 3. Rename file ke: firebase-service-account.json
 * 4. Letakkan di: storage/app/firebase-service-account.json
 * 5. Pastikan file ini TIDAK masuk ke git → tambahkan ke .gitignore:
 *    storage/app/firebase-service-account.json
 */
class FcmService
{
    private array  $serviceAccount;
    private string $projectId;

    /** Cache key untuk menyimpan access token sementara */
    private const CACHE_KEY = 'fcm_access_token';

    public function __construct()
    {
        $path = storage_path('app/firebase/majelis-rental-firebase.json');

        if (! file_exists($path)) {
            throw new \RuntimeException(
                'Firebase service account file tidak ditemukan di: ' . $path
            );
        }

        $this->serviceAccount = json_decode(file_get_contents($path), true);
        $this->projectId      = $this->serviceAccount['project_id'];
    }

    // ─────────────────────────────────────────────────────────────────────
    // sendToToken — kirim notifikasi ke satu device (via FCM token)
    // ─────────────────────────────────────────────────────────────────────
    public function sendToToken(
        string $fcmToken,
        string $title,
        string $body,
        array  $data        = [],
        string $channelId   = 'majelis_notif',
        ?callable $onInvalidToken = null
    ): bool {
        try {
            $accessToken = $this->getAccessToken();

            // FCM v1: semua nilai di "data" harus string
            $dataStringified = array_map('strval', $data);

            $payload = [
                'message' => [
                    'token'        => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                    // data dikirim bersamaan agar background handler bisa baca
                    'data' => $dataStringified,

                    // ── Android Config ────────────────────────────────────
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'channel_id'   => $channelId,
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            'icon'         => 'ic_notification',
                            'color'        => '#3E2723',
                        ],
                    ],

                    // ── iOS Config ────────────────────────────────────────
                    'apns' => [
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                        'payload' => [
                            'aps' => [
                                'alert' => [
                                    'title' => $title,
                                    'body'  => $body,
                                ],
                                'badge' => 1,
                                'sound' => 'default',
                            ],
                        ],
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type'  => 'application/json',
            ])->post(
                "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send",
                $payload
            );

            if ($response->successful()) {
                Log::info('[FCM] Push terkirim', [
                    'token_prefix' => substr($fcmToken, 0, 20) . '...',
                    'title'        => $title,
                ]);
                return true;
            }

            // Token tidak valid (device uninstall / refresh token) — log saja, jangan throw
            $responseBody = $response->json();
            $errorCode    = $responseBody['error']['details'][0]['errorCode'] ?? '';

            if (in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'])) {
                Log::warning('[FCM] Token tidak valid, perlu dihapus dari DB', [
                    'error' => $errorCode,
                ]);
                if ($onInvalidToken) {
                    $onInvalidToken($fcmToken);
                }
            } else {
                Log::error('[FCM] Push gagal', ['response' => $responseBody]);
            }

            return false;
        } catch (\Throwable $e) {
            Log::error('[FCM] Exception: ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // getAccessToken — generate JWT lalu tukar ke OAuth2 access token
    // Di-cache selama 55 menit (token valid 1 jam)
    // ─────────────────────────────────────────────────────────────────────
    private function getAccessToken(): string
    {
        // Cek cache dulu agar tidak request setiap kali
        if (Cache::has(self::CACHE_KEY)) {
            return Cache::get(self::CACHE_KEY);
        }

        $jwt      = $this->buildJwt();
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                '[FCM] Gagal mendapatkan access token: ' . $response->body()
            );
        }

        $token    = $response->json('access_token');
        $expiresIn = $response->json('expires_in', 3600);

        // Simpan di cache selama 55 menit (beri buffer 5 menit sebelum expired)
        Cache::put(self::CACHE_KEY, $token, now()->addSeconds($expiresIn - 300));

        return $token;
    }

    // ─────────────────────────────────────────────────────────────────────
    // buildJwt — buat JWT pakai private key dari service account JSON
    // Tidak membutuhkan package tambahan, cukup PHP openssl
    // ─────────────────────────────────────────────────────────────────────
    private function buildJwt(): string
    {
        $now = time();

        $header  = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ]));

        $payload = $this->base64UrlEncode(json_encode([
            'iss'   => $this->serviceAccount['client_email'],
            'sub'   => $this->serviceAccount['client_email'],
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        ]));

        $signingInput = "$header.$payload";
        $privateKey   = openssl_pkey_get_private($this->serviceAccount['private_key']);

        if (! $privateKey) {
            throw new \RuntimeException('[FCM] Private key tidak valid di service account JSON');
        }

        $signature = '';
        if (! openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('[FCM] Gagal menandatangani JWT');
        }

        return "$header.$payload." . $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
