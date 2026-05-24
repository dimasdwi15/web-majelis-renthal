<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\JWK;

/**
 * FirebaseService
 *
 * Memverifikasi Firebase ID Token yang dikirim dari Flutter secara server-side.
 * Ini adalah langkah keamanan WAJIB — jangan pernah percaya data dari client!
 *
 * Dependensi (tambahkan ke composer.json):
 *   composer require firebase/php-jwt
 *
 * Flow verifikasi:
 *   1. Download public keys Google (di-cache 1 jam)
 *   2. Decode JWT header untuk tahu kid (key ID)
 *   3. Verifikasi signature, exp, iss, aud
 *   4. Return payload berisi uid, email, name, picture
 *
 * Docs: https://firebase.google.com/docs/auth/admin/verify-id-tokens#verify_id_tokens_using_a_third-party_jwt_library
 */
class FirebaseService
{
    private const CERTS_URL = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';
    private const ISSUER_PREFIX = 'https://securetoken.google.com/';

    /**
     * Verifikasi Firebase ID Token.
     *
     * @param  string $idToken   Token dari Firebase Auth (Flutter)
     * @return array             Payload berisi: uid, email, name, picture, email_verified
     * @throws \Exception        Jika token tidak valid
     */
    public function verifyIdToken(string $idToken): array
    {
        $projectId = config('services.firebase.project_id');

        if (empty($projectId)) {
            throw new \Exception('Firebase Project ID belum dikonfigurasi. Set FIREBASE_PROJECT_ID di .env');
        }

        // ── 1. Ambil public keys Google (cached 1 jam) ─────────────────────
        $certs = $this->getGooglePublicKeys();

        // ── 2. Decode header untuk ambil kid ──────────────────────────────
        $headerParts = explode('.', $idToken);
        if (count($headerParts) !== 3) {
            throw new \Exception('Format token tidak valid.');
        }

        $header = json_decode(base64_decode(
            str_replace(['-', '_'], ['+', '/'], $headerParts[0])
        ), true);

        $kid = $header['kid'] ?? null;
        if (! $kid || ! isset($certs[$kid])) {
            throw new \Exception('Key ID tidak ditemukan di Google public keys.');
        }

        // ── 3. Verifikasi token ────────────────────────────────────────────
        try {
            JWT::$leeway = 120;

            $decoded = JWT::decode(
                $idToken,
                new Key($certs[$kid], 'RS256')
            );
        } catch (\Firebase\JWT\ExpiredException $e) {
            throw new \Exception('Token sudah kedaluwarsa. Silakan login ulang.');
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            throw new \Exception('Signature token tidak valid.');
        } catch (\Exception $e) {
            throw new \Exception('Token tidak valid: ' . $e->getMessage());
        }

        $payload = (array) $decoded;
        \Log::info('Firebase Token Debug', [
            'server_now' => now()->toDateTimeString(),
            'iat'        => date('Y-m-d H:i:s', $payload['iat'] ?? 0),
            'exp'        => date('Y-m-d H:i:s', $payload['exp'] ?? 0),
        ]);

        // ── 4. Validasi klaim wajib ────────────────────────────────────────
        // iss: harus dari Google + project ID yang benar
        $expectedIssuer = self::ISSUER_PREFIX . $projectId;
        if (($payload['iss'] ?? '') !== $expectedIssuer) {
            throw new \Exception('Issuer token tidak valid.');
        }

        // aud: harus project ID kita
        if (($payload['aud'] ?? '') !== $projectId) {
            throw new \Exception('Audience token tidak valid.');
        }

        // sub: Firebase UID (wajib ada)
        if (empty($payload['sub'])) {
            throw new \Exception('Subject (UID) tidak ditemukan di token.');
        }

        // ── 5. Return data yang sudah terverifikasi ────────────────────────
        return [
            'uid'            => $payload['sub'],
            'email'          => $payload['email']          ?? null,
            'name'           => $payload['name']           ?? null,
            'picture'        => $payload['picture']        ?? null,
            'email_verified' => $payload['email_verified'] ?? false,
        ];
    }

    /**
     * Ambil Google public keys untuk verifikasi Firebase token.
     * Di-cache 1 jam untuk efisiensi.
     */
    private function getGooglePublicKeys(): array
    {
        return Cache::remember('firebase_public_keys', 3600, function () {
            $response = Http::timeout(10)->get(self::CERTS_URL);

            if (! $response->successful()) {
                throw new \Exception('Gagal mengambil Google public keys.');
            }

            return $response->json();
        });
    }
}
