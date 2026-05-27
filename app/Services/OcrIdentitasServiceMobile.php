<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OcrIdentitasServiceMobile
{
    /**
     * =========================================================
     * KEYWORDS OCR FALLBACK
     * =========================================================
     */
    private const KEYWORDS = [
        'KTP' => [
            ['nik', 'nomor induk kependudukan'],
            ['provinsi', 'kabupaten', 'kecamatan', 'kelurahan', 'desa'],
            ['kartu tanda penduduk', 'republik indonesia'],
        ],
        'SIM' => [
            ['surat izin mengemudi'],
            ['kepolisian', 'polri', 'korlantas'],
            ['golongan', 'mengemudi'],
        ],
        'PELAJAR' => [
            ['kartu pelajar', 'kartu tanda pelajar', 'kartu identitas pelajar'],
            ['nisn', 'nomor induk siswa'],
            [
                'sekolah',
                'smk',
                'sma',
                'smp',
                'madrasah',
                'pesantren',
                'ma arif',
                'student card',
                'pelajar',
                'mahasiswa',
                'siswa',
            ],
        ],
    ];

    private const MIN_MATCH_GROUPS = [
        'KTP'     => 2,
        'SIM'     => 2,
        'PELAJAR' => 1,
    ];

    /**
     * =========================================================
     * GROQ AI CONFIG
     * =========================================================
     */
    private string $apiKey;
    private string $visionModel;
    private string $endpoint = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct(
        private readonly OcrSpaceService $ocrSpaceService
    ) {
        $this->apiKey = config('services.ai.groq_api_key', '');

        $this->visionModel = config(
            'services.ai.groq_vision_model',
            'meta-llama/llama-4-scout-17b-16e-instruct'
        );
    }

    /**
     * =========================================================
     * VALIDASI UTAMA
     * =========================================================
     *
     * Return shape (semua key selalu ada):
     * [
     *   'valid'            => bool,
     *   'confidence'       => int   (0–100),
     *   'pesan'            => string,
     *   'perlu_manual'     => bool,
     *   'jenis_terdeteksi' => string|null,
     *   'sesuai_jenis'     => bool,
     * ]
     */
    public function validasi(UploadedFile $file, string $jenisIdentitas): array
    {
        $jenisIdentitas = strtoupper(trim($jenisIdentitas));

        if (!array_key_exists($jenisIdentitas, self::KEYWORDS)) {
            return $this->hasil(
                valid: false,
                confidence: 0,
                message: 'Jenis identitas tidak dikenali.',
                requiresManual: false,
                jenisTerdeteksi: null,
                sesuaiJenis: false
            );
        }

        // ── 1. PRIORITAS: AI VISION (Groq LLaMA-4 Scout) ─────────────────
        $hasilAi = $this->validasiDenganAI($file, $jenisIdentitas);

        if ($hasilAi !== null) {

            // Jika AI sangat yakin
            if (
                $hasilAi['valid'] === true &&
                $hasilAi['confidence'] >= 75
            ) {
                return $hasilAi;
            }

            if ($hasilAi['valid'] === true && $hasilAi['confidence'] >= 50) {
                return $this->hasil(
                    valid: false,
                    confidence: $hasilAi['confidence'],
                    message: 'Identitas terdeteksi. Menunggu verifikasi manual admin.',
                    requiresManual: true,
                    jenisTerdeteksi: $hasilAi['jenis_terdeteksi'] ?? null,
                    sesuaiJenis: $hasilAi['sesuai_jenis'] ?? false,
                );
            }

            // Jika AI ragu / salah deteksi
            // lanjut OCR fallback untuk memastikan
            Log::warning('[OCR AI] Confidence rendah, lanjut OCR fallback.', [
                'confidence' => $hasilAi['confidence'],
            ]);
        }

        // ── 2. Skip OCR.space — timeout 40s+ melebihi batas koneksi ──────
        // Gunakan hasil AI jika ada, atau langsung manual review
        Log::info('[OCR] OCR.space dilewati. Pakai hasil AI atau manual review.', [
            'jenis'    => $jenisIdentitas,
            'has_ai'   => $hasilAi !== null,
        ]);

        if ($hasilAi !== null) {
            return $this->hasil(
                valid: false,
                confidence: $hasilAi['confidence'] ?? 0,
                message: 'Identitas terdeteksi. Akan diverifikasi manual oleh admin.',
                requiresManual: true,
                jenisTerdeteksi: $hasilAi['jenis_terdeteksi'] ?? null,
                sesuaiJenis: $hasilAi['sesuai_jenis'] ?? false,
            );
        }

        return $this->fallbackManual(
            $jenisIdentitas,
            'Identitas Anda akan diverifikasi manual oleh admin.'
        );
    }

    /**
     * =========================================================
     * AI VALIDATION — Groq LLaMA-4 Scout Vision
     * =========================================================
     */
    private function validasiDenganAI(
        UploadedFile $file,
        string $jenisIdentitas
    ): ?array {
        try {
            if (empty($this->apiKey)) {
                Log::warning('[OCR AI] API key Groq kosong, beralih ke OCR fallback.');
                return null;
            }

            $realPath = $file->getRealPath();

            if (!$realPath || !file_exists($realPath)) {
                Log::warning('[OCR AI] Real path file tidak ditemukan.');

                return null;
            }

            $imageContent = file_get_contents($realPath);

            if ($imageContent === false) {
                Log::warning('[OCR AI] Gagal membaca isi file.');

                return null;
            }

            $imageData = base64_encode($imageContent);
            $mimeType  = $file->getMimeType() ?? 'image/jpeg';

            $jenisLabel = match ($jenisIdentitas) {
                'SIM'     => 'SIM (Surat Izin Mengemudi)',
                'PELAJAR' => 'Kartu Pelajar / Kartu Identitas Pelajar',
                default   => 'KTP (Kartu Tanda Penduduk)',
            };

            // ── System Prompt ──────────────────────────────────────────────
            $systemPrompt = <<<PROMPT
Kamu adalah sistem verifikasi identitas resmi untuk aplikasi rental alat outdoor di Indonesia.

TUGASMU:
Analisis gambar yang dikirim user dan tentukan apakah gambar tersebut merupakan dokumen identitas resmi yang valid.

KRITERIA VALIDASI:
1. Gambar harus berupa foto fisik atau scan dari dokumen identitas resmi Indonesia (KTP, SIM, atau Kartu Pelajar).
2. Dokumen harus terlihat jelas, tidak buram, tidak terpotong bagian pentingnya.
3. Toleransi terhadap pencahayaan, sudut foto, dan kualitas kamera.
4. Fokus utama pada keberadaan elemen identitas resmi Indonesia.
5. Jangan menolak hanya karena sedikit blur atau pantulan cahaya jika informasi utama masih terlihat..

ATURAN JENIS DOKUMEN:
- KTP: Ada NIK 16 digit, nama, tempat/tanggal lahir, logo Garuda, tulisan "KARTU TANDA PENDUDUK" atau "REPUBLIK INDONESIA"
- SIM: Ada tulisan "SURAT IZIN MENGEMUDI", logo Korlantas/POLRI, golongan SIM (A/B/C/D)
- Kartu Pelajar: Ada nama sekolah/institusi, NISN, atau keterangan pelajar/siswa

WAJIB BALAS HANYA JSON (tanpa backtick, tanpa markdown, tanpa teks lain):
{
  "is_valid": true,
  "confidence": 90,
  "jenis_terdeteksi": "KTP",
  "sesuai_jenis": true,
  "alasan": "Dokumen KTP valid dengan NIK dan logo Garuda terlihat jelas.",
  "perlu_manual": false
}

NILAI confidence: 0-100 (seberapa yakin kamu dokumen ini valid dan sesuai)
NILAI perlu_manual: true jika gambar kurang jelas tapi mungkin valid, false jika sudah pasti valid atau pasti tidak valid
PROMPT;

            $userPrompt =
                "User memilih jenis identitas: {$jenisLabel}.\n"
                . "Analisis gambar berikut dan tentukan apakah itu dokumen {$jenisLabel} yang valid.";

            // ── HTTP Request ke Groq ───────────────────────────────────────
            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->post($this->endpoint, [
                    'model'           => $this->visionModel,
                    'temperature'     => 0.0,
                    'max_tokens'      => 300,
                    'response_format' => ['type' => 'json_object'],
                    'messages'        => [
                        [
                            'role'    => 'system',
                            'content' => $systemPrompt,
                        ],
                        [
                            'role'    => 'user',
                            'content' => [
                                [
                                    'type'      => 'image_url',
                                    'image_url' => [
                                        'url'    => "data:{$mimeType};base64,{$imageData}",
                                        'detail' => 'high',
                                    ],
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $userPrompt,
                                ],
                            ],
                        ],
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('[OCR AI] Request Groq gagal', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            $content = $response->json('choices.0.message.content', '{}');
            $parsed  = json_decode($content, true);

            if (!is_array($parsed)) {
                Log::warning('[OCR AI] JSON response tidak valid', [
                    'content' => $content,
                ]);
                return null;
            }

            Log::info('[OCR AI] Hasil validasi Groq', [
                'jenis_diminta'    => $jenisIdentitas,
                'jenis_terdeteksi' => $parsed['jenis_terdeteksi'] ?? null,
                'is_valid'         => $parsed['is_valid'] ?? false,
                'confidence'       => $parsed['confidence'] ?? 0,
                'alasan'           => $parsed['alasan'] ?? '',
            ]);

            $isValid        = (bool)  ($parsed['is_valid']         ?? false);
            $confidence     = (int)   ($parsed['confidence']       ?? 0);
            $jenisTerdeteksi = (string)($parsed['jenis_terdeteksi'] ?? '');
            $sesuaiJenis    = (bool)  ($parsed['sesuai_jenis']     ?? false);
            $alasan         = (string)($parsed['alasan']           ?? 'Identitas berhasil diverifikasi AI.');
            $perluManual    = (bool)  ($parsed['perlu_manual']     ?? false);

            return $this->hasil(
                valid: $isValid,
                confidence: $confidence,
                message: $alasan,
                requiresManual: $perluManual,
                jenisTerdeteksi: $jenisTerdeteksi !== '' ? $jenisTerdeteksi : null,
                sesuaiJenis: $sesuaiJenis
            );
        } catch (\Throwable $e) {
            Log::error('[OCR AI] Exception saat memanggil Groq', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);
            return null;
        }
    }

    /**
     * =========================================================
     * OCR FALLBACK ANALYSIS
     * =========================================================
     */
    private function analisisTeks(
        string $rawText,
        string $jenisIdentitas
    ): array {
        $normalizedText = $this->normalisasiTeks($rawText);

        if (empty($normalizedText)) {
            return $this->hasil(
                valid: false,
                confidence: 0,
                message: 'Teks tidak dapat diproses.',
                requiresManual: false,
                jenisTerdeteksi: null,
                sesuaiJenis: false
            );
        }

        [$matchedGroups, $confidence] = $this->hitungKecocokan(
            $normalizedText,
            $jenisIdentitas
        );

        $crossCheckGagal = $this->crossCheck(
            $normalizedText,
            $jenisIdentitas
        );

        $minMatch = self::MIN_MATCH_GROUPS[$jenisIdentitas] ?? 2;

        // cek NIK 16 digit
        $hasNik = false;

        if ($jenisIdentitas === 'KTP') {

            preg_match('/\b\d{16}\b/', $normalizedText, $nikMatch);

            $hasNik = !empty($nikMatch);

            // bonus confidence
            if ($hasNik) {
                $confidence += 20;
            }
        }

        // VALIDASI FINAL
        $valid =
            (
                $matchedGroups >= $minMatch
                || ($jenisIdentitas === 'KTP' && $hasNik)
            )
            && !$crossCheckGagal;

        return $this->hasil(
            valid: $valid,
            confidence: $confidence,
            message: $valid
                ? "Identitas {$jenisIdentitas} berhasil diverifikasi."
                : "Foto tidak cocok dengan {$jenisIdentitas}.",
            requiresManual: false,
            jenisTerdeteksi: $valid ? $jenisIdentitas : null,
            sesuaiJenis: $valid
        );
    }

    private function gabungkanHasilAI(
        ?array $hasilAi,
        array $hasilOcr
    ): array {

        // Jika OCR valid → prioritaskan OCR
        if ($hasilOcr['valid']) {

            // Jika AI juga valid → confidence dinaikkan
            if (
                $hasilAi !== null &&
                $hasilAi['valid'] === true
            ) {

                $hasilOcr['confidence'] = max(
                    $hasilOcr['confidence'],
                    $hasilAi['confidence']
                );

                $hasilOcr['pesan'] =
                    'Identitas berhasil diverifikasi.';
            }

            return $hasilOcr;
        }

        // Jika OCR gagal tapi AI sangat yakin
        if (
            $hasilAi !== null &&
            $hasilAi['valid'] === true &&
            $hasilAi['confidence'] >= 80
        ) {
            return $hasilAi;
        }

        // Jika dua-duanya gagal
        return [
            'valid'            => false,
            'confidence'       => 0,
            'pesan'            => 'Foto identitas tidak dapat diverifikasi.',
            'perlu_manual'     => false,
            'jenis_terdeteksi' => null,
            'sesuai_jenis'     => false,
        ];
    }

    /**
     * =========================================================
     * CROSS CHECK
     * =========================================================
     */
    private function crossCheck(string $normalizedText, string $jenisIdentitas): bool
    {
        $penandaEksklusif = [
            'KTP'     => ['nomor induk kependudukan', 'nik', 'kartu tanda penduduk'],
            'SIM'     => ['surat izin mengemudi', 'driving license'],
            'PELAJAR' => ['kartu pelajar', 'kartu tanda pelajar', 'nisn'],
        ];

        foreach ($penandaEksklusif as $jenis => $penanda) {
            if ($jenis === $jenisIdentitas) {
                continue;
            }
            foreach ($penanda as $kata) {
                if ($this->cocokKataUtuh($normalizedText, $kata)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * =========================================================
     * WORD MATCH
     * =========================================================
     */
    private function cocokKataUtuh(string $teks, string $keyword): bool
    {
        // exact match
        if (str_contains($teks, $keyword)) {
            return true;
        }

        // toleransi typo OCR ringan
        $words = explode(' ', $teks);

        foreach ($words as $word) {

            // skip kata pendek
            if (strlen($word) < 4 || strlen($keyword) < 4) {
                continue;
            }

            similar_text($word, $keyword, $percent);

            // naikkan threshold agar tidak false positive
            if ($percent >= 90) {
                return true;
            }
        }

        return false;
    }

    /**
     * =========================================================
     * NORMALIZE
     * =========================================================
     */
    private function normalisasiTeks(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9\s]/u', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * =========================================================
     * MATCH SCORE
     * =========================================================
     */
    private function hitungKecocokan(string $normalizedText, string $jenisIdentitas): array
    {
        $groups      = self::KEYWORDS[$jenisIdentitas];
        $totalGroups = count($groups);
        $matched     = 0;

        foreach ($groups as $group) {
            foreach ($group as $keyword) {
                if ($this->cocokKataUtuh($normalizedText, $keyword)) {
                    $matched++;
                    break;
                }
            }
        }

        $confidence = $totalGroups > 0
            ? (int) round(($matched / $totalGroups) * 100)
            : 0;

        return [$matched, $confidence];
    }

    /**
     * =========================================================
     * FALLBACK MANUAL
     * =========================================================
     */
    private function fallbackManual(
        string $jenisIdentitas = '',
        string $message = 'Perlu verifikasi manual.'
    ): array {
        return $this->hasil(
            valid: false,
            confidence: 0,
            message: $message,
            requiresManual: true,
            jenisTerdeteksi: null,
            sesuaiJenis: false
        );
    }

    /**
     * =========================================================
     * RESPONSE FORMAT — satu-satunya tempat key didefinisikan
     * =========================================================
     *
     * Key yang dikembalikan (konsisten, dipakai CheckoutController):
     *   valid, confidence, pesan, perlu_manual, jenis_terdeteksi, sesuai_jenis
     */
    private function hasil(
        bool    $valid,
        int     $confidence,
        string  $message,
        bool    $requiresManual  = false,
        ?string $jenisTerdeteksi = null,
        bool    $sesuaiJenis     = false
    ): array {
        return [
            'valid'            => $valid,
            'confidence'       => $confidence,
            'pesan'            => $message,
            'perlu_manual'     => $requiresManual,
            'jenis_terdeteksi' => $jenisTerdeteksi,
            'sesuai_jenis'     => $sesuaiJenis,
        ];
    }
}
