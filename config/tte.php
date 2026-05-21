<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tanda Tangan Elektronik (TTE) Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk sistem penandatanganan elektronik (TTE) SPPD
    |
    */

    'default_provider' => env('E_SIGN_PROVIDER', 'local_proxy'),

    'providers' => [
        'local_proxy' => [
            'name' => 'Local Proxy',
            'driver' => 'local_proxy',
            'endpoint' => env('E_SIGN_API_ENDPOINT', 'http://103.85.5.99'),
            'basic_auth' => env('E_SIGN_AUTH_BASIC', 'ZXNwcGQ6TUEzNDVnRmJCR0hUTWRkeDdlVXI='),
            'timeout' => env('E_SIGN_TIMEOUT', 30),
        ],

        'bssn' => [
            'name' => 'BSSN e-Sign',
            'driver' => 'bssn',
            'endpoint' => env('E_SIGN_BSSN_ENDPOINT', 'https://esign-bsre.bssn.go.id'),
            'client_id' => env('E_SIGN_BSSN_CLIENT_ID'),
            'client_secret' => env('E_SIGN_BSSN_CLIENT_SECRET'),
            'timeout' => env('E_SIGN_TIMEOUT', 30),
        ],
    ],

    'storage' => [
        'disk' => env('PDF_STORAGE_DISK', 'public'),

        'paths' => [
            'draft' => 'doc_dummy',          // Draft PDF sebelum sign
            'signed' => 'doc_tte',           // PDF sudah di-sign
            'signatures' => 'tanda_tangan',  // Gambar tanda tangan
            'qr_codes' => 'qr_codes',        // QR code files
        ],
    ],

    'queue' => [
        'connection' => env('QUEUE_CONNECTION', 'database'),
        'driver' => 'database',
        'retry_attempts' => env('TTE_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('TTE_RETRY_DELAY', 300), // detik
    ],

    'pdf' => [
        'format' => 'A4',
        'margin_top' => 10,
        'margin_bottom' => 10,
        'margin_left' => 10,
        'margin_right' => 10,
        'font_size' => 10,
        'font_family' => 'Arial',
    ],

    'signature' => [
        'qr_code_enabled' => env('TTE_QR_CODE_ENABLED', true),
        'qr_code_size' => 15, // mm
        'signature_box_width' => 50, // mm
        'signature_box_height' => 20, // mm
        'visible_signature' => true, // Tampilkan tanda tangan di PDF
    ],

    'logging' => [
        'enabled' => env('TTE_LOGGING_ENABLED', true),
        'channel' => 'single',
        'level' => env('TTE_LOG_LEVEL', 'info'),
    ],
];
