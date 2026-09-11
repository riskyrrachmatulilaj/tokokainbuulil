<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default WhatsApp Gateway Driver
    |--------------------------------------------------------------------------
    |
    | Driver gateway yang aktif: 'fonnte', 'generic', 'log'
    |
    */
    'default' => env('WHATSAPP_GATEWAY', 'fonnte'),

    'gateways' => [
        'fonnte' => [
            'api_token' => env('WHATSAPP_API_TOKEN', ''),
            'api_url' => env('WHATSAPP_API_URL', 'https://api.fonnte.com/send'),
        ],
        'generic' => [
            'api_token' => env('WHATSAPP_API_TOKEN', ''),
            'api_url' => env('WHATSAPP_API_URL', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Verification Secret
    |--------------------------------------------------------------------------
    |
    | Secret token opsional untuk memvalidasi request webhook yang masuk.
    |
    */
    'webhook_secret' => env('WHATSAPP_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Authorized Phone Numbers (Whitelist)
    |--------------------------------------------------------------------------
    |
    | Daftar nomor WhatsApp yang diizinkan mengakses data toko.
    | Format di .env: WHATSAPP_AUTHORIZED_NUMBERS=08123456789,08987654321
    |
    */
    'authorized_numbers' => array_filter(array_map('trim', explode(',', env('WHATSAPP_AUTHORIZED_NUMBERS', '')))),
];
