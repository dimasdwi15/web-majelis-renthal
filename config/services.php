<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // =========================================================================
    // Groq AI — ~14.400 request/hari gratis
    // Daftar API Key di: https://console.groq.com → API Keys
    //
    // Vision model: llama-3.2-11b-vision-preview (mendukung analisis gambar)
    // Chat model  : llama-3.3-70b-versatile       (untuk chat/teks)
    // =========================================================================
    'ai' => [
        'provider'           => env('AI_PROVIDER', 'groq'),
        'groq_api_key'       => env('GROQ_API_KEY'),
        'groq_model'         => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'groq_vision_model'  => env('GROQ_VISION_MODEL', 'llama-3.2-11b-vision-preview'),
        'ollama_url'         => env('OLLAMA_URL', 'http://localhost:11434'),
        'ollama_model'       => env('OLLAMA_MODEL', 'llama3.2'),
    ],

    // ── OpenWeather ───────────────────────────────────────────────────────────
    'openweather' => [
        'key' => env('OPENWEATHER_API_KEY', ''),
    ],

    // ── Tesseract OCR ─────────────────────────────────────────────────────────
    'tesseract' => [
        'enabled' => env('TESSERACT_ENABLED', false),
        'binary'  => env('TESSERACT_PATH', '/usr/bin/tesseract'),
        'lang'    => env('OCR_LANG', 'ind+eng'),
        'timeout' => env('OCR_TIMEOUT', 30),
    ],

    // ── OCR Space ────────────────────────────────────────────────────────────
    'ocr_space' => [
        'enabled'  => env('OCR_SPACE_ENABLED', true),
        'api_key'  => env('OCR_SPACE_API_KEY'),
        'endpoint' => 'https://api.ocr.space/parse/image',
        'timeout'  => 30,
    ],

    'ocr' => [
        'fallback_manual' => env('OCR_FALLBACK_MANUAL', true),
    ],

    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'credentials' => env('FIREBASE_CREDENTIALS'),
    ],

];
