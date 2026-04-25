<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // =========================================================================
    // Groq AI — Gratis ~14.400 request/hari
    // Daftar API Key di: https://console.groq.com → API Keys
    // =========================================================================
    'ai' => [
        'provider'     => env('AI_PROVIDER', 'groq'),
        'groq_api_key' => env('GROQ_API_KEY'),
        'groq_model'   => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'ollama_url'   => env('OLLAMA_URL', 'http://localhost:11434'),
        'ollama_model' => env('OLLAMA_MODEL', 'llama3.2'),
    ],

    // UNTUK API WEATHER
     'openweather' => [
        'key' => env('OPENWEATHER_API_KEY', ''),
    ],

];
