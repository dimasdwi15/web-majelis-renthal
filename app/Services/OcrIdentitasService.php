<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class OcrIdentitasService
{
    private const KEYWORDS = [
        'KTP' => [
            // Group 1 — nomor unik KTP
            ['nik', 'nomor induk kependudukan'],
            // Group 2 — wilayah administratif
            ['provinsi', 'kabupaten', 'kecamatan', 'kelurahan', 'desa'],
            // Group 3 — identitas dokumen
            ['kartu tanda penduduk', 'republik indonesia'],
        ],
        'SIM' => [
            // Group 1 — nama dokumen
            ['surat izin mengemudi'],
            // Group 2 — institusi penerbit
            ['kepolisian', 'polri', 'korlantas'],
            // Group 3 — konten spesifik SIM
            ['golongan', 'mengemudi'],
        ],
        'PELAJAR' => [
            // Group 1 — nama dokumen (frasa panjang, tidak ambigu)
            ['kartu pelajar', 'kartu tanda pelajar', 'kartu identitas pelajar'],
            // Group 2 — nomor unik pelajar (kata utuh, bukan substring)
            ['nisn', 'nomor induk siswa'],
            // Group 3 — nama institusi spesifik (frasa, bukan singkatan)
            ['sekolah menengah', 'sekolah dasar', 'madrasah', 'pesantren',
             'smk negeri', 'sma negeri', 'smp negeri', 'smk swasta', 'sma swasta'],
        ],
    ];

    // Naikkan minimum match untuk PELAJAR agar lebih ketat
    private const MIN_MATCH_GROUPS = [
        'KTP'     => 2,
        'SIM'     => 2,
        'PELAJAR' => 2, // harus cocok minimal 2 dari 3 group
    ];

    public function __construct(
        private readonly OcrSpaceService $ocrSpaceService
    ) {}

    public function validasi(UploadedFile $file, string $jenisIdentitas): array
    {
        $jenisIdentitas = strtoupper(trim($jenisIdentitas));

        if (!array_key_exists($jenisIdentitas, self::KEYWORDS)) {
            return $this->hasil(
                valid: false,
                confidence: 0,
                rawText: '',
                matchedGroups: 0,
                message: 'Jenis identitas tidak dikenali.',
                requiresManual: false
            );
        }

        if (!config('services.ocr_space.enabled', true)) {
            Log::warning('[OCR] OCR.space dinonaktifkan via config.');
            return $this->fallbackManual($jenisIdentitas);
        }

        $hasil = $this->ocrSpaceService->ekstrakTeks($file);

        return match ($hasil['status']) {
            'ok'     => $this->analisisTeks($hasil['teks'], $jenisIdentitas),
            'kosong' => $this->hasil(
                valid: false,
                confidence: 0,
                rawText: '',
                matchedGroups: 0,
                message: 'Gambar tidak mengandung teks yang terbaca. Pastikan foto identitas jelas dan tidak buram.',
                requiresManual: false
            ),
            'error'  => config('services.ocr.fallback_manual', false)
                ? $this->fallbackManual($jenisIdentitas)
                : $this->hasil(
                    valid: false,
                    confidence: 0,
                    rawText: '',
                    matchedGroups: 0,
                    message: 'Sistem verifikasi identitas sedang tidak tersedia. Silakan coba lagi.',
                    requiresManual: false
                ),
            default  => $this->fallbackManual($jenisIdentitas),
        };
    }

    private function fallbackManual(string $jenisIdentitas = ''): array
    {
        Log::warning('[OCR] Fallback manual.', ['jenis' => $jenisIdentitas]);
        return $this->hasil(
            valid: true,
            confidence: 0,
            rawText: '',
            matchedGroups: 0,
            message: 'Sistem OCR tidak tersedia. Identitas akan dicek admin saat pengambilan.',
            requiresManual: true
        );
    }

    private function analisisTeks(string $rawText, string $jenisIdentitas): array
    {
        $normalizedText = $this->normalisasiTeks($rawText);

        if (empty($normalizedText)) {
            return $this->hasil(
                valid: false,
                confidence: 0,
                rawText: $rawText,
                matchedGroups: 0,
                message: 'Teks tidak dapat diproses. Pastikan foto identitas jelas.',
                requiresManual: false
            );
        }

        [$matchedGroups, $confidence] = $this->hitungKecocokan($normalizedText, $jenisIdentitas);

        // Cek silang — pastikan dokumen lain tidak ikut cocok
        $crossCheckGagal = $this->crossCheck($normalizedText, $jenisIdentitas);

        $minMatch = self::MIN_MATCH_GROUPS[$jenisIdentitas] ?? 2;
        $valid    = $matchedGroups >= $minMatch && !$crossCheckGagal;

        $message = $valid
            ? "Identitas {$jenisIdentitas} berhasil diverifikasi."
            : ($crossCheckGagal
                ? "Foto terdeteksi sebagai dokumen lain, bukan {$jenisIdentitas}. Pastikan foto sesuai jenis yang dipilih."
                : "Foto tidak terdeteksi sebagai {$jenisIdentitas} yang valid. Pastikan foto jelas dan sesuai jenis yang dipilih."
            );

        Log::info('[OCR] Hasil analisis', [
            'jenis'          => $jenisIdentitas,
            'valid'          => $valid,
            'confidence'     => $confidence,
            'matched_groups' => $matchedGroups,
            'cross_check'    => $crossCheckGagal ? 'GAGAL' : 'OK',
            'preview_teks'   => mb_substr($normalizedText, 0, 200),
        ]);

        return $this->hasil(
            valid: $valid,
            confidence: $confidence,
            rawText: $rawText,
            matchedGroups: $matchedGroups,
            message: $message,
            requiresManual: false
        );
    }

    /**
     * Cross-check: pastikan teks tidak mengandung penanda dokumen LAIN
     * yang lebih kuat dari dokumen yang diklaim user.
     *
     * Return true jika terdeteksi dokumen lain (checkout harus ditolak).
     */
    private function crossCheck(string $normalizedText, string $jenisIdentitas): bool
    {
        // Penanda eksklusif tiap dokumen — kalau ada ini, pasti bukan dokumen lain
        $penandaEksklusif = [
            'KTP'     => ['nomor induk kependudukan', 'nik', 'kartu tanda penduduk'],
            'SIM'     => ['surat izin mengemudi', 'driving license'],
            'PELAJAR' => ['kartu pelajar', 'kartu tanda pelajar', 'kartu identitas pelajar', 'nisn'],
        ];

        foreach ($penandaEksklusif as $jenis => $penanda) {
            // Skip dokumen yang diklaim user
            if ($jenis === $jenisIdentitas) continue;

            foreach ($penanda as $kata) {
                if ($this->cocokKataUtuh($normalizedText, $kata)) {
                    Log::warning('[OCR] Cross-check gagal — teks cocok dengan dokumen lain', [
                        'diklaim'    => $jenisIdentitas,
                        'terdeteksi' => $jenis,
                        'kata_kunci' => $kata,
                    ]);
                    return true; // Terdeteksi sebagai dokumen lain
                }
            }
        }

        return false;
    }

    /**
     * Cocokkan keyword sebagai kata utuh (word boundary).
     * Mencegah "ma" cocok dengan "nama", "sd" cocok dengan "selatan", dll.
     */
    private function cocokKataUtuh(string $teks, string $keyword): bool
    {
        // Frasa panjang (> 3 karakter) — cukup str_contains, tidak mungkin false positive
        if (mb_strlen($keyword) > 3) {
            return str_contains($teks, $keyword);
        }

        // Kata pendek (≤ 3 karakter) — harus cocok sebagai kata utuh
        $pattern = '/\b' . preg_quote($keyword, '/') . '\b/u';
        return (bool) preg_match($pattern, $teks);
    }

    private function normalisasiTeks(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9\s]/u', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

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

    private function hasil(
        bool   $valid,
        int    $confidence,
        string $rawText,
        int    $matchedGroups,
        string $message,
        bool   $requiresManual = false
    ): array {
        return [
            'valid'          => $valid,
            'confidence'     => $confidence,
            'rawText'        => $rawText,
            'matchedGroups'  => $matchedGroups,
            'message'        => $message,
            'requiresManual' => $requiresManual,
        ];
    }
}
