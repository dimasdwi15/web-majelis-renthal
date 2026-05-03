<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OcrSpaceService
{
    // OCR.space free plan: maks ~1MB per request
    private const MAX_SIZE_BYTES = 900_000; // 900KB, beri sedikit margin
    private const MAX_DIMENSION  = 1500;    // resize jika lebar/tinggi > 1500px

    public function ekstrakTeks(UploadedFile $file): array
    {
        $apiKey = config('services.ocr_space.api_key');

        if (empty($apiKey)) {
            Log::warning('[OCR.space] API key tidak dikonfigurasi.');
            return ['status' => 'error', 'teks' => ''];
        }

        // Siapkan gambar — kompres & resize jika perlu
        $base64Image = $this->siapkanBase64($file);

        if ($base64Image === null) {
            Log::warning('[OCR.space] Gagal memproses file gambar.');
            return ['status' => 'error', 'teks' => ''];
        }

        $ukuranKb = round(strlen($base64Image) / 1024, 1);
        Log::info('[OCR.space] Ukuran base64 yang akan dikirim.', ['ukuran_kb' => $ukuranKb]);

        $maxPercobaan = 2;

        for ($percobaan = 1; $percobaan <= $maxPercobaan; $percobaan++) {
            try {
                Log::info("[OCR.space] Percobaan ke-{$percobaan}");

                $response = Http::timeout(20)
                    ->asForm()
                    ->post(config('services.ocr_space.endpoint'), [
                        'apikey'            => $apiKey,
                        'base64Image'       => $base64Image,
                        'isOverlayRequired' => 'false',
                        'detectOrientation' => 'true',
                        'scale'             => 'true',
                        'OCREngine'         => '2',
                    ]);

                if (!$response->successful()) {
                    Log::warning('[OCR.space] HTTP error', [
                        'status'    => $response->status(),
                        'percobaan' => $percobaan,
                    ]);
                    if ($percobaan < $maxPercobaan) continue;
                    return ['status' => 'error', 'teks' => ''];
                }

                $data = $response->json();

                if (($data['IsErroredOnProcessing'] ?? false) === true) {
                    Log::warning('[OCR.space] Error processing', [
                        'message'   => $data['ErrorMessage'] ?? 'Unknown',
                        'percobaan' => $percobaan,
                    ]);
                    return ['status' => 'error', 'teks' => ''];
                }

                $allText = '';
                foreach ($data['ParsedResults'] ?? [] as $result) {
                    $allText .= ($result['ParsedText'] ?? '') . ' ';
                }

                $trimmed = trim($allText);

                if (empty($trimmed)) {
                    Log::info('[OCR.space] Teks OCR kosong — gambar tidak mengandung teks.');
                    return ['status' => 'kosong', 'teks' => ''];
                }

                Log::info('[OCR.space] Berhasil.', [
                    'percobaan'    => $percobaan,
                    'panjang_teks' => strlen($trimmed),
                    'preview'      => mb_substr($trimmed, 0, 100),
                ]);

                return ['status' => 'ok', 'teks' => $trimmed];

            } catch (\Exception $e) {
                Log::warning("[OCR.space] Percobaan ke-{$percobaan} gagal: {$e->getMessage()}");

                if ($percobaan < $maxPercobaan) {
                    sleep(1);
                    continue;
                }

                Log::error('[OCR.space] Semua percobaan gagal.', ['error' => $e->getMessage()]);
                return ['status' => 'error', 'teks' => ''];
            }
        }

        return ['status' => 'error', 'teks' => ''];
    }

    /**
     * Siapkan gambar sebagai base64.
     * - Resize jika dimensi terlalu besar
     * - Kompres jika ukuran file melebihi batas
     * - Return format: "data:image/jpeg;base64,..."
     */
    private function siapkanBase64(UploadedFile $file): ?string
    {
        $path = $file->getPathname();

        // Cek apakah GD tersedia
        if (!extension_loaded('gd')) {
            // GD tidak ada — kirim file mentah tanpa compress
            $raw = file_get_contents($path);
            if ($raw === false) return null;

            $ekstensi = strtolower($file->getClientOriginalExtension());
            $mime = match($ekstensi) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png'         => 'image/png',
                'webp'        => 'image/webp',
                default       => 'image/jpeg',
            };

            return "data:{$mime};base64," . base64_encode($raw);
        }

        try {
            // Deteksi tipe gambar
            $info = @getimagesize($path);
            if (!$info) {
                // Bukan gambar valid
                return null;
            }

            [$lebar, $tinggi, $tipe] = $info;

            // Buat GD resource dari file
            $src = match($tipe) {
                IMAGETYPE_JPEG => imagecreatefromjpeg($path),
                IMAGETYPE_PNG  => imagecreatefrompng($path),
                IMAGETYPE_WEBP => imagecreatefromwebp($path),
                IMAGETYPE_GIF  => imagecreatefromgif($path),
                IMAGETYPE_BMP  => imagecreatefrombmp($path),
                default        => null,
            };

            if (!$src) return null;

            // Resize jika dimensi terlalu besar
            if ($lebar > self::MAX_DIMENSION || $tinggi > self::MAX_DIMENSION) {
                $ratio      = min(self::MAX_DIMENSION / $lebar, self::MAX_DIMENSION / $tinggi);
                $lebarBaru  = (int) round($lebar * $ratio);
                $tinggiBaru = (int) round($tinggi * $ratio);

                $dst = imagecreatetruecolor($lebarBaru, $tinggiBaru);

                // Pertahankan transparansi untuk PNG
                if ($tipe === IMAGETYPE_PNG) {
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                }

                imagecopyresampled($dst, $src, 0, 0, 0, 0, $lebarBaru, $tinggiBaru, $lebar, $tinggi);
                imagedestroy($src);
                $src = $dst;

                Log::info('[OCR.space] Gambar di-resize.', [
                    'dari' => "{$lebar}x{$tinggi}",
                    'ke'   => "{$lebarBaru}x{$tinggiBaru}",
                ]);
            }

            // Kompres ke JPEG dengan kualitas turun bertahap hingga di bawah batas ukuran
            $kualitas = 85;
            do {
                ob_start();
                imagejpeg($src, null, $kualitas);
                $output = ob_get_clean();

                if (strlen($output) <= self::MAX_SIZE_BYTES || $kualitas <= 40) {
                    break;
                }

                $kualitas -= 10;
            } while (true);

            imagedestroy($src);

            Log::info('[OCR.space] Gambar dikompres.', [
                'kualitas'    => $kualitas,
                'ukuran_kb'   => round(strlen($output) / 1024, 1),
            ]);

            return 'data:image/jpeg;base64,' . base64_encode($output);

        } catch (\Exception $e) {
            Log::warning('[OCR.space] Gagal kompres gambar: ' . $e->getMessage());

            // Fallback — kirim file mentah
            $raw = file_get_contents($path);
            if ($raw === false) return null;
            return 'data:image/jpeg;base64,' . base64_encode($raw);
        }
    }
}
