<?php

namespace App\Filament\Resources\Barangs\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateBarangAction
{
    /**
     * Konfigurasi di .env:
     *   AI_PROVIDER=groq    → cloud gratis, butuh internet (production/hosting)
     *   AI_PROVIDER=ollama  → lokal gratis, tanpa internet (local dev)
     */
    public static function make(): Action
    {
        return Action::make('generateAI')
            ->label('Auto Generate')
            ->icon('heroicon-o-sparkles')
            ->color('warning')
            ->tooltip('Generate deskripsi & spesifikasi otomatis menggunakan AI')
            ->action(function ($get, $set) {

                $namaBarang = trim($get('nama') ?? '');

                if (blank($namaBarang)) {
                    Notification::make()
                        ->title('Nama barang belum diisi')
                        ->body('Isi kolom "Nama Barang" terlebih dahulu.')
                        ->warning()
                        ->send();
                    return;
                }

                $provider = config('services.ai.provider', 'groq');

                try {
                    $result = match ($provider) {
                        'ollama' => self::callOllama($namaBarang),
                        default  => self::callGroq($namaBarang),
                    };

                    if (!$result) {
                        Notification::make()
                            ->title('Generate gagal')
                            ->body('AI mengembalikan format yang tidak dikenali. Coba lagi.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $set('deskripsi', $result['deskripsi']);
                    $set('spesifikasi', $result['spesifikasi']);

                    Notification::make()
                        ->title('Berhasil di-generate! ✨')
                        ->body('Deskripsi dan spesifikasi sudah terisi. Anda bisa mengeditnya.')
                        ->success()
                        ->duration(5000)
                        ->send();

                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    Log::error('GenerateBarangAction: connection error', [
                        'provider' => $provider,
                        'nama'     => $namaBarang,
                        'message'  => $e->getMessage(),
                    ]);

                    $hint = $provider === 'groq'
                        ? 'Groq tidak bisa diakses. Whitelist php.exe di antivirus, atau ganti AI_PROVIDER=ollama di .env.'
                        : 'Ollama tidak bisa diakses. Pastikan Ollama sudah berjalan (download di ollama.com).';

                    Notification::make()
                        ->title('Gagal terhubung ke AI')
                        ->body($hint)
                        ->danger()
                        ->persistent()
                        ->send();

                } catch (\Exception $e) {
                    Log::error('GenerateBarangAction: unexpected error', [
                        'provider' => $provider,
                        'nama'     => $namaBarang,
                        'message'  => $e->getMessage(),
                    ]);

                    Notification::make()
                        ->title('Terjadi kesalahan')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    // =========================================================================
    // GROQ — Cloud gratis (~14.400 req/hari), butuh internet
    // =========================================================================
    private static function callGroq(string $namaBarang): ?array
    {
        $apiKey = config('services.ai.groq_api_key');
        $model  = config('services.ai.groq_model', 'llama-3.3-70b-versatile');

        if (blank($apiKey)) {
            throw new \RuntimeException(
                'GROQ_API_KEY belum diset di .env — daftar gratis di https://console.groq.com'
            );
        }

        Log::info('GenerateBarangAction: calling Groq', [
            'nama'  => $namaBarang,
            'model' => $model,
        ]);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type'  => 'application/json',
        ])
        ->timeout(30)
        ->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'       => $model,
            'temperature' => 0.7,
            'max_tokens'  => 1024,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => implode(' ', [
                        'Kamu adalah asisten toko penyewaan peralatan outdoor Indonesia.',
                        'Balas HANYA dengan JSON valid.',
                        'PENTING: Dalam nilai string JSON, gunakan karakter \n (backslash-n) untuk baris baru — JANGAN gunakan Enter/newline literal.',
                        'Jangan tambahkan markdown, backtick, atau teks apapun di luar JSON.',
                    ]),
                ],
                [
                    'role'    => 'user',
                    'content' => self::buildPrompt($namaBarang),
                ],
            ],
        ]);

        if (!$response->successful()) {
            $errorMsg = $response->json('error.message') ?? ('HTTP ' . $response->status());
            throw new \RuntimeException("Groq API error: {$errorMsg}");
        }

        $text = $response->json('choices.0.message.content') ?? null;

        Log::info('GenerateBarangAction: Groq raw response', ['raw' => $text]);

        return self::parseJsonResponse($text);
    }

    // =========================================================================
    // OLLAMA — Lokal gratis, tanpa internet
    // =========================================================================
    private static function callOllama(string $namaBarang): ?array
    {
        $baseUrl = config('services.ai.ollama_url', 'http://localhost:11434');
        $model   = config('services.ai.ollama_model', 'llama3.2');

        Log::info('GenerateBarangAction: calling Ollama', [
            'nama'  => $namaBarang,
            'model' => $model,
        ]);

        $response = Http::timeout(120)
            ->post("{$baseUrl}/api/generate", [
                'model'  => $model,
                'prompt' => self::buildPrompt($namaBarang),
                'stream' => false,
                'format' => 'json',
                'options' => [
                    'temperature' => 0.7,
                    'num_predict' => 1024,
                ],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                "Ollama tidak merespons (HTTP {$response->status()}). " .
                "Pastikan Ollama sudah berjalan — download di https://ollama.com/download"
            );
        }

        $text = $response->json('response') ?? null;

        Log::info('GenerateBarangAction: Ollama raw response', ['raw' => $text]);

        return self::parseJsonResponse($text);
    }

    // =========================================================================
    // SHARED
    // =========================================================================

    private static function buildPrompt(string $namaBarang): string
    {
        return <<<PROMPT
Buat konten produk penyewaan outdoor untuk: "{$namaBarang}".

WAJIB balas HANYA JSON ini. Untuk baris baru di dalam nilai string, gunakan \n (dua karakter: backslash dan huruf n), BUKAN Enter literal:

{"deskripsi":"2-3 kalimat bahasa Indonesia yang menarik tentang kegunaan dan keunggulan barang ini untuk outdoor.","spesifikasi":"- Material: ...\n- Ukuran: ...\n- Berat: ...\n- Kapasitas: ...\n- Fitur utama: ..."}
PROMPT;
    }

    /**
     * ✅ FIX UTAMA: Parse JSON dengan sanitasi karakter kontrol.
     *
     * Bug sebelumnya: Groq kadang mengembalikan literal newline di dalam
     * string JSON (karakter ASCII 10), yang membuat json_decode() gagal
     * dengan error "Control character error, possibly incorrectly encoded".
     *
     * Fix: karakter-per-karakter, ganti literal newline/tab di dalam
     * string JSON dengan escape sequence yang valid (\n, \t, \r).
     */
    private static function parseJsonResponse(?string $text): ?array
    {
        if (blank($text)) {
            Log::warning('GenerateBarangAction: response kosong');
            return null;
        }

        // Bersihkan markdown backtick jika ada
        $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
        $text = preg_replace('/```\s*$/', '', $text);
        $text = trim($text);

        // Ekstrak bagian { ... } jika ada teks di luar JSON
        if (!str_starts_with($text, '{')) {
            preg_match('/\{.*\}/s', $text, $matches);
            $text = $matches[0] ?? $text;
        }

        // ── FIX: Sanitasi literal control characters di dalam string JSON ──
        // Iterasi karakter-per-karakter untuk mengganti newline/tab literal
        // yang ada di dalam string value JSON menjadi escape sequence yang valid.
        $text = self::fixJsonControlChars($text);

        $parsed = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('GenerateBarangAction: JSON parse gagal setelah sanitasi', [
                'error'     => json_last_error_msg(),
                'sanitized' => $text,
            ]);
            return null;
        }

        if (!isset($parsed['deskripsi'], $parsed['spesifikasi'])) {
            Log::warning('GenerateBarangAction: key JSON tidak lengkap', ['parsed' => $parsed]);
            return null;
        }

        return [
            'deskripsi'   => trim($parsed['deskripsi']),
            'spesifikasi' => trim($parsed['spesifikasi']),
        ];
    }

    /**
     * Perbaiki literal control characters (newline, tab, carriage return)
     * yang ada di dalam nilai string JSON.
     *
     * JSON spec: karakter kontrol di dalam string HARUS di-escape sebagai
     * \n, \t, \r, dll. Tapi beberapa model AI kadang menaruh karakter literal.
     *
     * Cara kerja: track apakah sedang "di dalam string JSON", jika ya dan
     * ketemu karakter kontrol literal → ganti dengan escape sequence-nya.
     */
    private static function fixJsonControlChars(string $json): string
    {
        $result    = '';
        $inString  = false;
        $escaped   = false;
        $len       = strlen($json);

        for ($i = 0; $i < $len; $i++) {
            $char = $json[$i];

            // Jika karakter sebelumnya adalah backslash, ini adalah escaped char
            if ($escaped) {
                $result  .= $char;
                $escaped  = false;
                continue;
            }

            // Deteksi backslash
            if ($char === '\\') {
                $escaped = true;
                $result .= $char;
                continue;
            }

            // Toggle status "di dalam string"
            if ($char === '"') {
                $inString = !$inString;
                $result  .= $char;
                continue;
            }

            // Jika di dalam string dan ketemu literal control character → escape
            if ($inString) {
                if ($char === "\n") {
                    $result .= '\\n';
                    continue;
                }
                if ($char === "\r") {
                    $result .= '\\r';
                    continue;
                }
                if ($char === "\t") {
                    $result .= '\\t';
                    continue;
                }
            }

            $result .= $char;
        }

        return $result;
    }
}
